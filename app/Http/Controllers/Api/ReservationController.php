<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Reservation;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use App\Services\TaxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReservationController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService,
        protected PricingService $pricingService,
        protected TaxService $taxService
    ) {
    }

    /**
     * Display a listing of reservations for a property.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|string|in:pending,confirmed,cancelled,checked_in,checked_out',
            'room_id' => 'nullable|exists:rooms,id',
            'search' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $property = Property::find($request->property_id);

        // Ensure user owns the property
        if ($property->user_id !== Auth::guard('web')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this property.',
            ], 403);
        }

        $query = Reservation::where('property_id', $request->property_id)
            ->with(['room', 'roomType', 'property']);

        // Filter by date range
        if ($request->start_date) {
            $query->where('check_out', '>=', Carbon::parse($request->start_date)->toDateString());
        }
        if ($request->end_date) {
            $query->where('check_in', '<=', Carbon::parse($request->end_date)->toDateString());
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        } else {
            // Exclude cancelled by default unless specifically requested
            $query->where('status', '!=', 'cancelled');
        }

        // Filter by room
        if ($request->room_id) {
            $query->where('room_id', $request->room_id);
        }

        // Search by guest name or email
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('guest_first_name', 'like', "%{$search}%")
                    ->orWhere('guest_last_name', 'like', "%{$search}%")
                    ->orWhere('guest_email', 'like', "%{$search}%")
                    ->orWhere('ota_reservation_code', 'like', "%{$search}%");
            });
        }

        $reservations = $query->orderBy('check_in')
            ->orderBy('check_out')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reservations,
        ]);
    }

    /**
     * Store a newly created reservation.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'property_id' => 'required|exists:properties,id',
                'room_id' => 'nullable|exists:rooms,id',
                'room_type_id' => 'nullable|exists:room_types,id',
                'channel_connection_id' => [
                    'nullable',
                    'integer',
                    function ($attribute, $value, $fail) {
                        if ($value !== null && $value > 0 && !\App\Models\ChannelConnection::where('id', $value)->exists()) {
                            $fail('The selected channel connection does not exist.');
                        }
                    },
                ],
                'guest_first_name' => 'required|string|max:255',
                'guest_last_name' => 'required|string|max:255',
                'guest_email' => 'nullable|email|max:255',
                'guest_phone' => 'nullable|string|max:50',
                'check_in' => 'required|date|after_or_equal:today',
                'check_out' => 'required|date|after:check_in',
                'adult_count' => 'nullable|integer|min:1',
                'child_count' => 'nullable|integer|min:0',
                'total_amount' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|size:3',
                'status' => 'nullable|string|in:pending,confirmed,cancelled,checked_in,checked_out',
                'source' => 'nullable|string|max:50',
                'notes' => 'nullable|string',
            ]);

            // Ensure at least room_id or room_type_id is provided
            if (!$request->room_id && !$request->room_type_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Either room_id or room_type_id must be provided.',
                    'errors' => [
                        'room_id' => ['Either room_id or room_type_id is required.'],
                        'room_type_id' => ['Either room_id or room_type_id is required.'],
                    ],
                ], 422);
            }

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $property = Property::find($request->property_id);

            // Ensure user owns the property
            if ($property->user_id !== Auth::guard('web')->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. You do not have access to this property.',
                ], 403);
            }

            // Check availability before creating reservation
            $checkIn = Carbon::parse($request->check_in);
            $checkOut = Carbon::parse($request->check_out);

            $availability = $this->availabilityService->checkAvailability(
                propertyId: $property->id,
                checkIn: $checkIn,
                checkOut: $checkOut,
                roomId: $request->room_id,
                roomTypeId: $request->room_type_id,
                adultCount: $request->adult_count,
                childCount: $request->child_count,
            );

            // Check if any rooms are available
            if (!($availability['available'] ?? $availability['available_rooms'] > 0)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No rooms available for the selected dates.',
                    'errors' => [
                        'availability' => ['Please select different dates or room type.']
                    ],
                ], 422);
            }

            // If specific room_id is provided, ensure it's available
            if ($request->room_id) {
                $roomAvailable = collect($availability['rooms'])->contains(function ($room) use ($request) {
                    return $room['room_id'] == $request->room_id && $room['is_available'];
                });

                if (!$roomAvailable) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The selected room is not available for the chosen dates.',
                        'errors' => [
                            'room_id' => ['This room is already booked or unavailable for these dates.']
                        ],
                    ], 422);
                }
            } elseif ($request->room_type_id) {
                // If only room_type_id is provided, auto-assign first available room
                $availableRoom = collect($availability['rooms'])
                    ->where('is_available', true)
                    ->first();

                if (!$availableRoom) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No rooms available for the selected room type and dates.',
                        'errors' => [
                            'room_type_id' => ['No available rooms found for this room type.']
                        ],
                    ], 422);
                }

                // Auto-assign the first available room
                $request->merge(['room_id' => $availableRoom['room_id']]);
            }

            // Calculate pricing if not provided
            $totalAmount = $request->total_amount;
            $taxAmount = $request->tax_amount ?? 0;
            $taxBreakdown = $request->tax_breakdown ?? [];

            if (!$totalAmount && $request->room_type_id) {
                $pricing = $this->pricingService->calculateTotalPrice(
                    propertyId: $property->id,
                    roomTypeId: $request->room_type_id,
                    checkIn: $checkIn,
                    checkOut: $checkOut,
                    adultCount: $request->adult_count ?? 1,
                    childCount: $request->child_count ?? 0,
                    rateType: 'default'
                );
                $totalAmount = $pricing['total_amount'];
                $taxAmount = $pricing['total_tax'] ?? 0;
                $taxBreakdown = $pricing['tax_breakdown'] ?? [];
            }

            // Ensure room_type_id is set if not provided (get from room)
            if (!$request->room_type_id && $request->room_id) {
                $room = \App\Models\Room::find($request->room_id);
                if ($room) {
                    $request->merge(['room_type_id' => $room->room_type_id]);
                }
            }

            // For direct bookings, channel_connection_id can be 0 or null
            // But the database requires a value, so use 0 for direct bookings
            $channelConnectionId = $request->channel_connection_id ?? 0;

            $reservation = Reservation::create([
                'property_id' => $request->property_id,
                'room_id' => $request->room_id,
                'room_type_id' => $request->room_type_id,
                'channel_connection_id' => $channelConnectionId,
                'guest_first_name' => $request->guest_first_name,
                'guest_last_name' => $request->guest_last_name,
                'guest_email' => $request->guest_email,
                'guest_phone' => $request->guest_phone,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'adult_count' => $request->adult_count ?? 1,
                'child_count' => $request->child_count ?? 0,
                'total_amount' => $totalAmount ?? 0,
                'tax_amount' => $taxAmount,
                'currency' => $request->currency ?? $property->currency ?? 'GBP',
                'status' => $request->status ?? 'confirmed',
                'source' => $request->source ?? 'direct',
                'payment_status' => 'pending',
                'notes' => $request->notes,
                'tax_breakdown' => $taxBreakdown,
            ]);

            $reservation->load(['room', 'roomType', 'property']);

            return response()->json([
                'success' => true,
                'message' => 'Reservation created successfully',
                'data' => $reservation,
            ], 201);
        } catch (\Exception $e) {
            // Log the actual error for debugging
            \Log::error('Reservation creation error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the reservation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Please try again later or contact support if the problem persists.',
                'errors' => [
                    'server' => [config('app.debug') ? $e->getMessage() : 'Please try again later or contact support if the problem persists.']
                ],
            ], 500);
        }
    }

    /**
     * Display the specified reservation.
     */
    public function show(string $id): JsonResponse
    {
        $reservation = Reservation::with(['room', 'roomType', 'property', 'bookingDetails'])
            ->find($id);

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Reservation not found',
            ], 404);
        }

        // Ensure user owns the property that owns this reservation
        if ($reservation->property->user_id !== Auth::guard('web')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this reservation.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $reservation,
        ]);
    }

    /**
     * Update the specified reservation.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $reservation = Reservation::with(['property', 'room'])->find($id);

            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reservation not found',
                ], 404);
            }

            // Ensure user owns the property that owns this reservation
            if ($reservation->property->user_id !== Auth::guard('web')->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. You do not have access to this reservation.',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'room_id' => 'nullable|exists:rooms,id',
                'room_type_id' => 'nullable|exists:room_types,id',
                'guest_first_name' => 'sometimes|required|string|max:255',
                'guest_last_name' => 'sometimes|required|string|max:255',
                'guest_email' => 'nullable|email|max:255',
                'guest_phone' => 'nullable|string|max:50',
                'check_in' => 'sometimes|required|date',
                'check_out' => 'sometimes|required|date|after:check_in',
                'adult_count' => 'nullable|integer|min:1',
                'child_count' => 'nullable|integer|min:0',
                'total_amount' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|size:3',
                'status' => 'nullable|string|in:pending,confirmed,cancelled,checked_in,checked_out',
                'payment_status' => 'nullable|string|in:pending,paid,partial,refunded',
                'source' => 'nullable|string|max:50',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // If dates or room changed, check availability (excluding this reservation)
            $checkIn = $request->has('check_in') ? Carbon::parse($request->check_in) : $reservation->check_in;
            $checkOut = $request->has('check_out') ? Carbon::parse($request->check_out) : $reservation->check_out;
            $roomId = $request->room_id ?? $reservation->room_id;

            if ($request->has('check_in') || $request->has('check_out') || $request->has('room_id')) {
                // Check if new dates/room conflict with other reservations
                $conflictingReservation = Reservation::where('property_id', $reservation->property_id)
                    ->where('id', '!=', $id)
                    ->where('status', '!=', 'cancelled')
                    ->where(function ($query) use ($checkIn, $checkOut, $roomId) {
                        $query->where(function ($q) use ($checkIn, $checkOut) {
                            // Check if dates overlap
                            $q->where(function ($dateQuery) use ($checkIn, $checkOut) {
                                $dateQuery->whereBetween('check_in', [$checkIn, $checkOut])
                                    ->orWhereBetween('check_out', [$checkIn, $checkOut])
                                    ->orWhere(function ($overlap) use ($checkIn, $checkOut) {
                                        $overlap->where('check_in', '<=', $checkIn)
                                            ->where('check_out', '>=', $checkOut);
                                    });
                            });
                        });

                        if ($roomId) {
                            $query->where('room_id', $roomId);
                        }
                    })
                    ->first();

                if ($conflictingReservation) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The selected dates or room conflict with an existing reservation.',
                        'errors' => [
                            'availability' => ['Please select different dates or room.']
                        ],
                    ], 422);
                }
            }

            $reservation->update($request->only([
                'room_id',
                'room_type_id',
                'guest_first_name',
                'guest_last_name',
                'guest_email',
                'guest_phone',
                'check_in',
                'check_out',
                'adult_count',
                'child_count',
                'total_amount',
                'currency',
                'status',
                'payment_status',
                'source',
                'notes',
            ]));

            $reservation->load(['room', 'roomType', 'property']);

            return response()->json([
                'success' => true,
                'message' => 'Reservation updated successfully',
                'data' => $reservation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the reservation.',
                'errors' => [
                    'server' => ['Please try again later or contact support if the problem persists.']
                ],
            ], 500);
        }
    }

    /**
     * Remove (cancel) the specified reservation.
     */
    public function destroy(string $id): JsonResponse
    {
        $reservation = Reservation::with('property')->find($id);

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Reservation not found',
            ], 404);
        }

        // Ensure user owns the property that owns this reservation
        if ($reservation->property->user_id !== Auth::guard('web')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this reservation.',
            ], 403);
        }

        // Instead of deleting, cancel the reservation
        $reservation->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reservation cancelled successfully',
            'data' => $reservation->fresh(),
        ]);
    }
}
