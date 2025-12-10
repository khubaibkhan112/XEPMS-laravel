<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Reservation;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use App\Services\TaxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService,
        protected PricingService $pricingService,
        protected TaxService $taxService
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

            // Calculate total amount using pricing engine
            $roomType = $property->roomTypes()->where('is_active', true)->find($request->room_type_id);
            
            if (!$roomType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected room type not found or not available',
                ], 404);
            }

            $pricing = $this->pricingService->calculateTotalPrice(
                propertyId: $property->id,
                roomTypeId: $request->room_type_id,
                checkIn: $checkIn,
                checkOut: $checkOut,
                adultCount: $request->adult_count,
                childCount: $request->child_count ?? 0,
                rateType: 'default'
            );

            $totalAmount = $pricing['total_amount'];
            $taxAmount = $pricing['total_tax'] ?? 0;
            $subtotal = $pricing['subtotal'] ?? $totalAmount;

            // Get available room for this room type (if any)
            $roomId = null;
            if (isset($availability['available_rooms']) && count($availability['available_rooms']) > 0) {
                $roomId = $availability['available_rooms'][0]['id'];
            }

            // Find or create guest
            $guest = null;
            if ($request->guest_email || $request->guest_phone) {
                if ($request->guest_email) {
                    $guest = \App\Models\Guest::byEmail($request->guest_email)->first();
                }
                if (!$guest && $request->guest_phone) {
                    $guest = \App\Models\Guest::byPhone($request->guest_phone)->first();
                }
                if (!$guest) {
                    $guest = \App\Models\Guest::create([
                        'first_name' => $request->guest_first_name,
                        'last_name' => $request->guest_last_name,
                        'email' => $request->guest_email,
                        'phone' => $request->guest_phone,
                    ]);
                } else {
                    // Update guest information if provided
                    $guest->update([
                        'first_name' => $request->guest_first_name,
                        'last_name' => $request->guest_last_name,
                        'email' => $request->guest_email ?: $guest->email,
                        'phone' => $request->guest_phone ?: $guest->phone,
                    ]);
                }
            }

            // Create reservation
            $reservation = Reservation::create([
                'guest_id' => $guest?->id,
                'property_id' => $request->property_id,
                'room_type_id' => $request->room_type_id,
                'room_id' => $roomId ?? 0, // 0 or null if no specific room assigned yet
                'channel_connection_id' => 0, // 0 for direct bookings
                'guest_first_name' => $request->guest_first_name,
                'guest_last_name' => $request->guest_last_name,
                'guest_email' => $request->guest_email,
                'guest_phone' => $request->guest_phone,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'adult_count' => $request->adult_count,
                'child_count' => $request->child_count ?? 0,
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount,
                'currency' => $property->currency ?? 'GBP',
                'status' => 'pending', // Pending confirmation
                'source' => 'direct', // Direct booking from website
                'payment_status' => 'pending',
                'notes' => $request->notes,
                'tax_breakdown' => $pricing['tax_breakdown'] ?? [],
                'pricing_breakdown' => $pricing['pricing_breakdown'] ?? null,
            ]);

            // Create booking history entry
            if ($guest) {
                \App\Models\GuestBookingHistory::create([
                    'guest_id' => $guest->id,
                    'reservation_id' => $reservation->id,
                    'property_id' => $property->id,
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'nights' => $reservation->nights,
                    'total_amount' => $totalAmount,
                    'paid_amount' => 0,
                    'currency' => $property->currency ?? 'GBP',
                    'status' => 'pending',
                    'payment_status' => 'pending',
                ]);

                // Update guest's last stay
                $guest->update(['last_stay_at' => now()]);
                
                // Update loyalty status
                $guest->updateLoyaltyStatus();
            }

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

