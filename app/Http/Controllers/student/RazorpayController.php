<?php

namespace App\Http\Controllers\student;

use App\Helpers\ActivityLog;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use App\Models\Event;
use App\Models\EventPayment;
use App\Models\StudentEventRegistration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RazorpayController extends Controller
{

    public function createOrder(Request $request)
    {
        $studentId = Auth::id();
        $event = Event::findOrFail($request->event_id);

        $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));

        // Amount in paise, as integer
        $amountPaise = (int) round($event->price * 100);

        try {
            $order = $api->order->create([
                'amount' => $amountPaise, // MUST be integer in paise
                'currency' => 'INR',
                'receipt' => 'event_' . $event->id . '_user_' . $studentId . '_' . time(),
                'payment_capture' => 1,
            ]);

            // Save order in DB
            $event_payment = new EventPayment();
            $event_payment->student_id = $studentId;
            $event_payment->event_id = $event->id;
            $event_payment->order_id = $order['id'];
            $event_payment->amount = $event->price;
            $event_payment->status = 'created';
            $event_payment->save();
            return response()->json($order->toArray());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to create order. Try again later.'], 500);
        }
    }

    public function paymentSuccess(Request $request)
    {

        $request->validate([
            'event_id' => 'required',
            'razorpay_order_id' => 'required',
            'razorpay_payment_id' => 'required',
            'razorpay_signature' => 'required',
            'schedule_id' =>  'required',
        ]);

         $payment = EventPayment::where('order_id', $request->razorpay_order_id)
            ->where('status', 'created')
            ->first();

        if (!$payment) {
            return response()->json(['error' => 'Invalid or already processed payment'], 400);
        }

        $api = new \Razorpay\Api\Api(config('services.razorpay.key'), config('services.razorpay.secret'));

        try {
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ]);

            DB::transaction(function () use ($payment, $request) {

                $update = EventPayment::where('order_id', $request->razorpay_order_id)
                    ->update([
                        'payment_id' => $request->razorpay_payment_id,
                        'signature' => $request->razorpay_signature,
                        'status' => 'paid',
                    ]);

                $register = new StudentEventRegistration();
                $register->student_id   = Auth::id();
                $register->event_id     = $payment->event_id;
                $register->event_schedule_id  = $request->schedule_id;
                $register->status       = 1;
                $register->save();
            });

            return response()->json(['message' => 'Payment successful. Event registration completed.']);
        } catch (\Razorpay\Api\Errors\SignatureVerificationError $e) {
            $payment->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
            return response()->json(['error' => 'Payment verification failed'], 400);
        } catch (\Exception $e) {
            $payment->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
            return response()->json(['error' => 'Payment failed. Please try again.'], 500);
        }
    }
}
