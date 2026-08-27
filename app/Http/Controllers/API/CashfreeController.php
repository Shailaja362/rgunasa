<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Payment;
use App\Models\StudentEventRegistration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CashfreeController extends Controller
{
    private function baseUrl()
    {
        return config('services.cashfree.env') === 'PROD'
            ? 'https://api.cashfree.com/pg'
            : 'https://sandbox.cashfree.com/pg';
    }

    private function headers(?string $idempotencyKey = null)
    {
        $headers = [
            'x-client-id'     => config('services.cashfree.app_id'),
            'x-client-secret' => config('services.cashfree.secret_key'),
            'x-api-version'   => '2023-08-01',
            'Content-Type'    => 'application/json',
        ];

        if ($idempotencyKey) {
            $headers['x-idempotency-key'] = $idempotencyKey;
        }

        return $headers;
    }

    private function normalizeAmount($rawAmount): float
    {
        $amount = (float) preg_replace('/[^0-9.]/', '', (string) $rawAmount);
        return round($amount, 2);
    }


    public function createOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_id'    => 'required|exists:events,id',
            'schedule_id' => 'required|exists:event_schedules,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $student  = auth('student-api')->user();
        $event    = Event::findOrFail($request->event_id);
        $schedule = EventSchedule::findOrFail($request->schedule_id);

        $error = $this->checkEligibility($student, $event, $schedule);
        if ($error) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        $orderAmount = $this->normalizeAmount($event->price);

        if ($orderAmount <= 0) {
            Log::error('Cashfree order_amount_invalid guard triggered', [
                'event_id'  => $event->id,
                'raw_price' => $event->price,
            ]);
            return response()->json(['success' => false, 'message' => 'This event has an invalid price configured.'], 422);
        }

        if ($orderAmount < 1) {
            return response()->json(['success' => false, 'message' => 'Order amount must be at least ₹1.'], 422);
        }

        $cfOrderId = 'EVT_' . $event->id . '_' . $student->id . '_' . Str::random(8);

        $payload = [
            'order_id'       => $cfOrderId,
            'order_amount'   => $orderAmount,
            'order_currency' => 'INR',
            'customer_details' => [
                'customer_id'    => (string) $student->id,
                'customer_name'  => $student->name,
                'customer_email' => $student->email,
                'customer_phone' => $student->mobile_number ?? '9999999999',
            ],
            // No return_url/notify_url needed: this build is fully client-driven —
            // the frontend calls verifyPayment + registerEvent itself after checkout,
            // instead of relying on a browser redirect or a server webhook.
            'order_meta' => [
                "notify_url" => url('/api/user/cashfree/webhook'),
            ],
        ];

        Log::info('Cashfree create order payload', ['payload' => $payload]);

        $response = Http::withHeaders($this->headers($cfOrderId))
            ->post($this->baseUrl() . '/orders', $payload);

        if (!$response->successful()) {
            Log::error('Cashfree order creation failed', [
                'status'  => $response->status(),
                'body'    => $response->body(),
                'payload' => $payload,
            ]);
            return response()->json(['success' => false, 'message' => 'Unable to initiate payment.'], 500);
        }

        $data = $response->json();

        Payment::create([
            'student_id'        => $student->id,
            'event_id'          => $event->id,
            'event_schedule_id' => $schedule->id,
            'cf_order_id'       => $cfOrderId,
            'amount'            => $orderAmount,
            'status'            => 'PENDING',
        ]);

        return response()->json([
            'success'             => true,
            'payment_session_id' => $data['payment_session_id'],
            'order_id'            => $cfOrderId,
        ]);
    }

    public function verifyPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $payment = Payment::where('cf_order_id', $request->order_id)->first();
        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'No matching payment found for this order.'], 404);
        }

        $student = auth('student-api')->user();
        if ($student && $payment->student_id !== $student->id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        if ($payment->status === 'PAID') {
            return response()->json([
                'success' => true,
                'status'  => 'PAID',
                'message' => 'Payment already verified.',
            ]);
        }

        $orderResponse = Http::withHeaders($this->headers())
            ->get($this->baseUrl() . '/orders/' . $payment->cf_order_id);

        if (!$orderResponse->successful()) {
            Log::error('Cashfree order fetch failed', [
                'cf_order_id' => $payment->cf_order_id,
                'status'      => $orderResponse->status(),
                'body'        => $orderResponse->body(),
            ]);
            return response()->json(['success' => false, 'message' => 'Could not verify payment status.'], 502);
        }

        $orderStatus = $orderResponse->json()['order_status'] ?? null;

        if ($orderStatus !== 'PAID') {
            $payment->update(['status' => $orderStatus === 'EXPIRED' ? 'FAILED' : 'PENDING']);
            return response()->json([
                'success' => false,
                'status'  => $payment->status,
                'message' => 'Payment was not completed.',
            ], 422);
        }

        // Fetch the actual transaction record for reference (cf_payment_id, method)
        $paymentsResponse = Http::withHeaders($this->headers())
            ->get($this->baseUrl() . '/orders/' . $payment->cf_order_id . '/payments');

        $cfPaymentId   = null;
        $paymentMethod = null;

        if ($paymentsResponse->successful()) {
            $successfulPayment = collect($paymentsResponse->json())->firstWhere('payment_status', 'SUCCESS');
            if ($successfulPayment) {
                $cfPaymentId   = $successfulPayment['cf_payment_id'] ?? null;
                $paymentMethod = array_key_first($successfulPayment['payment_method'] ?? []) ?: null;
            }
        }

        $payment->update([
            'status'         => 'PAID',
            'cf_payment_id'  => $cfPaymentId,
            'payment_method' => 'mobile',
        ]);

        return response()->json([
            'success' => true,
            'status'  => 'PAID',
            'message' => 'Payment verified successfully.',
        ]);
    }


    public function registerEvent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $payment = Payment::where('cf_order_id', $request->order_id)->first();
        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'No matching payment found for this order.'], 404);
        }

        $student = auth('student-api')->user();
        if ($student && $payment->student_id !== $student->id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        if ($payment->status !== 'PAID') {
            return response()->json([
                'success' => false,
                'message' => 'Payment must be verified before registering. Call verifyPayment first.',
            ], 422);
        }

        $lock = Cache::lock('cf_register_' . $payment->cf_order_id, 15);

        if (!$lock->get()) {
            return response()->json([
                'success' => false,
                'message' => 'Registration already in progress for this order, please retry shortly.',
            ], 409);
        }

        try {
            $result = DB::transaction(function () use ($payment) {
                $existing = StudentEventRegistration::where([
                    'student_id'        => $payment->student_id,
                    'event_id'          => $payment->event_id,
                    'event_schedule_id' => $payment->event_schedule_id,
                ])->where('registered_at', '>=', $payment->created_at)->first();

                if ($existing) {
                    return true; // already registered for this specific payment
                }

                $event    = Event::findOrFail($payment->event_id);
                $schedule = EventSchedule::findOrFail($payment->event_schedule_id);
                $student  = \App\Models\Student::findOrFail($payment->student_id);

                $error = $this->checkEligibility($student, $event, $schedule);
                if ($error) {
                    return $error;
                }

                StudentEventRegistration::create([
                    'student_id'        => $student->id,
                    'event_id'          => $event->id,
                    'event_schedule_id' => $schedule->id,
                    'status'            => 1,
                    'registered_at'     => Carbon::now(),
                ]);

                return true;
            });

            if ($result === true) {
                return response()->json([
                    'status' => 200,
                    'message' => 'You are registered for this event.',
                ]);
            }

            return response()->json([
                'status' => 422,
                'message' => $result,
            ], 422);
        } finally {
            $lock->release();
        }
    }

    private function checkEligibility($student, Event $event, EventSchedule $schedule)
    {
        $lastRegistration = StudentEventRegistration::where([
            'student_id'        => $student->id,
            'event_id'          => $event->id,
            'event_schedule_id' => $schedule->id,
        ])->orderBy('registered_at', 'desc')->first();

        if ($lastRegistration && (empty($event->duration_days) || $event->duration_days == 0)) {
            return 'You are already registered for this event.';
        }

        if ($lastRegistration && $event->duration_days > 0) {
            $nextAllowedDate = Carbon::parse($lastRegistration->registered_at)->addDays($event->duration_days);
            if (Carbon::now()->lt($nextAllowedDate)) {
                return 'You can register again after ' . $nextAllowedDate->format('d M Y');
            }
        }

        // seat_count is one shared pool for the whole schedule row (across every
        // batch/semester it's open to), so count every registration on this row.
        $registeredCount = StudentEventRegistration::where('event_schedule_id', $schedule->id)->count();

        if ($registeredCount >= $schedule->seat_count) {
            return 'This event seat is already full.';
        }

        return null;
    }
}
