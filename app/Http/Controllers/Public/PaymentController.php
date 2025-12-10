<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {
    }

    /**
     * Create a payment intent for a reservation (public endpoint)
     */
    public function createIntent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|exists:reservations,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $reservation = Reservation::findOrFail($request->reservation_id);
            
            // Verify this is a direct booking (not OTA booking)
            if ($reservation->source !== 'direct') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment can only be processed for direct bookings',
                ], 403);
            }

            $amount = (float)$request->amount;
            $currency = $request->currency ?? $reservation->currency ?? 'GBP';

            $result = $this->paymentService->createPaymentIntent(
                reservation: $reservation,
                amount: $amount,
                currency: $currency,
                metadata: $request->metadata ?? []
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Failed to create payment intent',
                'error_code' => $result['error_code'] ?? null,
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the payment intent.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Confirm a payment (public endpoint)
     */
    public function confirm(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|exists:reservations,id',
            'payment_intent_id' => 'required|string',
            'amount' => 'nullable|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $reservation = Reservation::findOrFail($request->reservation_id);
            
            // Verify this is a direct booking
            if ($reservation->source !== 'direct') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment can only be processed for direct bookings',
                ], 403);
            }

            $amount = $request->amount ? (float)$request->amount : null;

            $result = $this->paymentService->confirmPayment(
                paymentIntentId: $request->payment_intent_id,
                reservation: $reservation,
                amount: $amount,
                paymentMethod: \App\Models\Payment::METHOD_STRIPE
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Failed to confirm payment',
                'error_code' => $result['error_code'] ?? null,
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while confirming the payment.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get payment status (public endpoint)
     */
    public function status(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->paymentService->getPaymentStatus($request->payment_intent_id);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking payment status.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
