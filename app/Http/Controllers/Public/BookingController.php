<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Reservation;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService
    ) {
    }

    /**
     * Create a public booking/reservation
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'property_id' => 'required|exists:properties,id',
                'room_type_id' => 'required|exists:room_types,id',
                'guest_first_name' => 'required|string|max:255',
                'guest_last_name' => 'required|string|max:255',
                'guest_email' => 'required|email|max:255',
                'guest_phone' => 'nullable|string|max:50',
                'check_in' => 'required|date|after_or_equal:today',
                'check_out' => 'required|date|after:check_in',
                'adult_count' => 'required|integer|min:1',
                'child_count' => 'nullable|integer|min:0',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $property = Property::where('is_active', true)->find($request->property_id);

            if (!$property) {
                return response()->json([
                    'success' => false,
                    'message' => 'Property not found or not available',
                ], 404);
            }

            // Check availability before creating reservation
            $checkIn = Carbon::parse($request->check_in);
            $checkOut = Carbon::parse($request->check_out);

            $availability = $this->availabilityService->checkAvailability(
                propertyId: $property->id,
                checkIn: $checkIn,
                checkOut: $checkOut,
                roomId: null,
                roomTypeId: $request->room_type_id,
                adultCount: $request->adult_count,
                childCount: $request->child_count ?? 0,
            );

            if (!$availability['available']) {
                return response()->json([
                    'success' => false,
                    'message' => 'No rooms available for the selected dates.',
                    'errors' => [
                        'availability' => ['Please select different dates or room type.']
                    ],
                ], 422);
            }

            // Calculate total amount (simplified - you may want to add pricing logic)
            $nights = $checkIn->diffInDays($checkOut);
            $roomType = $property->roomTypes()->where('is_active', true)->find($request->room_type_id);
            
            if (!$roomType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected room type not found or not available',
                ], 404);
            }
            
            $baseRate = $roomType->base_rate ?? 100; // Default rate if not set
            $totalAmount = $baseRate * $nights;

            // Create reservation
            $reservation = Reservation::create([
                'property_id' => $request->property_id,
                'room_type_id' => $request->room_type_id,
                'guest_first_name' => $request->guest_first_name,
                'guest_last_name' => $request->guest_last_name,
                'guest_email' => $request->guest_email,
                'guest_phone' => $request->guest_phone,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'adult_count' => $request->adult_count,
                'child_count' => $request->child_count ?? 0,
                'total_amount' => $totalAmount,
                'currency' => $property->currency ?? 'GBP',
                'status' => 'pending', // Pending confirmation
                'source' => 'direct', // Direct booking from website
                'payment_status' => 'pending',
                'notes' => $request->notes,
            ]);

            $reservation->load(['property', 'roomType']);
            
            // Generate a simple reservation code if not provided
            if (!$reservation->ota_reservation_code) {
                $reservation->ota_reservation_code = 'DIR-' . str_pad($reservation->id, 6, '0', STR_PAD_LEFT);
                $reservation->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Booking request submitted successfully',
                'data' => $reservation,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the booking.',
                'errors' => [
                    'server' => ['Please try again later or contact support if the problem persists.']
                ],
            ], 500);
        }
    }

    /**
     * Get booking details by reservation code or ID
     */
    public function show(string $id): JsonResponse
    {
        $reservation = Reservation::with(['property', 'roomType'])
            ->where(function ($query) use ($id) {
                $query->where('id', $id)
                    ->orWhere('ota_reservation_code', $id);
            })
            ->first();

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $reservation,
        ]);
    }
}

