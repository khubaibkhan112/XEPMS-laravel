<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckOut;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Services\CheckOutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CheckOutController extends Controller
{
    public function __construct(
        protected CheckOutService $checkOutService
    ) {
    }

    /**
     * Process check-out for a reservation
     */
    public function process(Request $request, int $reservationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'additional_charges' => 'nullable|numeric|min:0',
            'damages' => 'nullable|numeric|min:0',
            'incidentals' => 'nullable|array',
            'incidentals.*.description' => 'required_with:incidentals|string',
            'incidentals.*.amount' => 'required_with:incidentals|numeric|min:0',
            'incidentals.*.quantity' => 'nullable|integer|min:1',
            'room_condition' => 'nullable|array',
            'key_return' => 'nullable|array',
            'departure_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $reservation = Reservation::findOrFail($reservationId);
            
            // Verify user has access
            if (Auth::check() && $reservation->property->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            $result = $this->checkOutService->processCheckOut(
                reservation: $reservation,
                additionalCharges: $request->additional_charges ?? 0,
                damages: $request->damages ?? 0,
                incidentals: $request->incidentals ?? [],
                roomCondition: $request->room_condition,
                keyReturn: $request->key_return,
                departureNotes: $request->departure_notes
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to process check-out',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing check-out',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Collect payment during check-out
     */
    public function collectPayment(Request $request, int $checkOutId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:stripe,paypal,bank_transfer,cash',
            'payment_intent_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $checkOut = CheckOut::findOrFail($checkOutId);
            
            // Verify user has access
            if (Auth::check() && $checkOut->property->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            $result = $this->checkOutService->collectPayment(
                checkOut: $checkOut,
                amount: (float)$request->amount,
                paymentMethod: $request->payment_method,
                paymentIntentId: $request->payment_intent_id
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to collect payment',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error collecting payment',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get check-out details for a reservation
     */
    public function show(int $reservationId): JsonResponse
    {
        try {
            $reservation = Reservation::findOrFail($reservationId);
            
            // Verify user has access
            if (Auth::check() && $reservation->property->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            $checkOut = $this->checkOutService->getCheckOutDetails($reservation);

            if (!$checkOut) {
                return response()->json([
                    'success' => false,
                    'message' => 'Check-out not found for this reservation',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $checkOut,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching check-out details',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * List check-outs for a property
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'status' => 'nullable|string|in:pending,completed,cancelled',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $property = \App\Models\Property::findOrFail($request->property_id);
            
            // Verify user has access
            if (Auth::check() && $property->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            $query = CheckOut::where('property_id', $request->property_id)
                ->with(['reservation', 'invoice', 'room']);

            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->date_from) {
                $query->whereDate('actual_check_out_at', '>=', $request->date_from);
            }

            if ($request->date_to) {
                $query->whereDate('actual_check_out_at', '<=', $request->date_to);
            }

            $checkOuts = $query->orderBy('actual_check_out_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $checkOuts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching check-outs',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get invoice for check-out
     */
    public function invoice(int $checkOutId): JsonResponse
    {
        try {
            $checkOut = CheckOut::with(['invoice', 'reservation', 'property'])->findOrFail($checkOutId);
            
            // Verify user has access
            if (Auth::check() && $checkOut->property->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            if (!$checkOut->invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'No invoice found for this check-out',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $checkOut->invoice->load(['reservation', 'property']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching invoice',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get upcoming check-outs
     */
    public function upcoming(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'days_ahead' => 'nullable|integer|min:1|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $property = \App\Models\Property::findOrFail($request->property_id);
            
            // Verify user has access
            if (Auth::check() && $property->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            $daysAhead = $request->days_ahead ?? 7;
            $endDate = now()->addDays($daysAhead)->toDateString();

            $reservations = Reservation::where('property_id', $request->property_id)
                ->where('status', Reservation::STATUS_CHECKED_IN)
                ->whereDate('check_out', '<=', $endDate)
                ->whereDate('check_out', '>=', now()->toDateString())
                ->with(['room', 'roomType', 'checkOut'])
                ->orderBy('check_out', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $reservations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching upcoming check-outs',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
