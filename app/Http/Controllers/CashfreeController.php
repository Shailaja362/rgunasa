<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Payment;
use App\Models\StudentEventRegistration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CashfreeController extends Controller
{
    private function baseUrl()
    {
        return config('services.cashfree.env') === 'PROD'
            ? 'https://api.cashfree.com/pg'
            : 'https://sandbox.cashfree.com/pg';
    }

    private function headers()
    {
        return [
            'x-client-id'     => config('services.cashfree.app_id'),
            'x-client-secret' => config('services.cashfree.secret_key'),
            'x-api-version'   => '2023-08-01',
            'Content-Type'    => 'application/json',
        ];
    }

    public function createOrder(Request $request)
    {
        $request->validate([
            'event_id'    => 'required|exists:events,id',
            'schedule_id' => 'required|exists:event_schedules,id',
        ]);

        $student  = auth()->user()->student ?? $request->user(); // adjust to your auth
        $event    = Event::findOrFail($request->event_id);
        $schedule = EventSchedule::findOrFail($request->schedule_id);

        // Re-validate eligibility server-side (never trust the UI state)
        $error = $this->checkEligibility($student, $event, $schedule);
        if ($error) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        $cfOrderId = 'EVT_' . $event->id . '_' . $student->id . '_' . Str::random(8);

        $payload = [
            'order_id'        => $cfOrderId,
            'order_amount'    => (float) $event->price,
            'order_currency'  => 'INR',
            'customer_details' => [
                'customer_id'    => (string) $student->id,
                'customer_name'  => $student->name ?? auth()->user()->student->name,
                'customer_email' => $student->email ?? auth()->user()->student->email,
                'customer_phone' => $student->mobile_number ?? '9999999999',
            ],
            'order_meta' => [
                'return_url' => route('cashfree.return', ['cf_order_id' => $cfOrderId]) . '?order_id={order_id}',
            ],
        ];

        $response = Http::withHeaders($this->headers())
            ->post($this->baseUrl() . '/orders', $payload);

        if (!$response->successful()) {
            Log::error('Cashfree order creation failed', ['body' => $response->body()]);
            return response()->json(['success' => false, 'message' => 'Unable to initiate payment.'], 500);
        }

        $data = $response->json();

        Payment::create([
            'student_id'        => $student->id,
            'event_id'          => $event->id,
            'event_schedule_id' => $schedule->id,
            'cf_order_id'       => $cfOrderId,
            'amount'            => $event->price,
            'status'            => 'PENDING',
        ]);

        return response()->json([
            'success'           => true,
            'payment_session_id' => $data['payment_session_id'],
            'cf_order_id'       => $cfOrderId,
        ]);
    }

    public function handleReturn(Request $request, $cf_order_id)
    {
        $payment = Payment::where('cf_order_id', $cf_order_id)->firstOrFail();

        // Already processed (e.g. webhook got there first)
        if ($payment->status === 'PAID') {
            return redirect()->route('student_dashboard')->with('success', 'Registration successful.');
        }

        $verified = $this->verifyAndRegister($payment);

        if ($verified === true) {
            return redirect()->route('student_dashboard')->with('success', 'Payment successful. You are registered.');
        }

        return redirect()->route('student_dashboard')->with('error', $verified);
    }

    // Cashfree server-to-server webhook (recommended as the source of truth)
    public function webhook(Request $request)
    {
        $orderId = $request->input('data.order.order_id');
        if (!$orderId) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $payment = Payment::where('cf_order_id', $orderId)->first();
        if ($payment && $payment->status !== 'PAID') {
            $this->verifyAndRegister($payment);
        }

        return response()->json(['status' => 'ok'], 200);
    }

    private function verifyAndRegister(Payment $payment)
    {
        // Step 1: check order status
        $orderResponse = Http::withHeaders($this->headers())
            ->get($this->baseUrl() . '/orders/' . $payment->cf_order_id);

        if (!$orderResponse->successful()) {
            return 'Could not verify payment status.';
        }

        $orderStatus = $orderResponse->json()['order_status'] ?? null;

        if ($orderStatus !== 'PAID') {
            $payment->update(['status' => 'FAILED']);
            return 'Payment was not completed.';
        }

        // Step 2: fetch the actual payment/transaction record for this order
        $paymentsResponse = Http::withHeaders($this->headers())
            ->get($this->baseUrl() . '/orders/' . $payment->cf_order_id . '/payments');

        $cfPaymentId  = null;
        $paymentMethod = null;

        if ($paymentsResponse->successful()) {
            $payments = $paymentsResponse->json();
            // Cashfree returns an array of payment attempts; take the successful one
            $successfulPayment = collect($payments)->firstWhere('payment_status', 'SUCCESS');

            if ($successfulPayment) {
                $cfPaymentId   = $successfulPayment['cf_payment_id'] ?? null;
                $paymentMethod = array_key_first($successfulPayment['payment_method'] ?? []) ?: null;
            }
        }

        return DB::transaction(function () use ($payment, $cfPaymentId, $paymentMethod) {
            $payment = Payment::where('id', $payment->id)->lockForUpdate()->first();

            if ($payment->status === 'PAID') {
                return true; // already handled
            }

            $event    = Event::findOrFail($payment->event_id);
            $schedule = EventSchedule::findOrFail($payment->event_schedule_id);
            $student  = \App\Models\Student::findOrFail($payment->student_id);

            $error = $this->checkEligibility($student, $event, $schedule);
            if ($error) {
                $payment->update(['status' => 'FAILED']);
                return $error;
            }

            StudentEventRegistration::create([
                'student_id'        => $student->id,
                'event_id'          => $event->id,
                'event_schedule_id' => $schedule->id,
                'status'            => 1,
                'registered_at'     => Carbon::now(),
            ]);

            $payment->update([
                'status'         => 'PAID',
                'cf_payment_id'  => $cfPaymentId,
                'payment_method' => 'web app',
            ]);

            return true;
        });
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
