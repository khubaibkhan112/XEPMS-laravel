<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomKey;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CheckInController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService
    ) {
    }

    /**
     * Display a listing of check-ins.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'date' => 'nullable|date',
            'status' => 'nullable|string|in:completed,pending,cancelled',
            'upcoming' => 'nullable|boolean',
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

        $query = CheckIn::forProperty($request->property_id)
            ->with(['reservation', 'room', 'checkedInBy', 'keys']);

        if ($request->date) {
            $query->whereDate('checked_in_at', $request->date);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->upcoming) {
            $query->upcoming($request->days_ahead ?? 7);
        }

        $checkIns = $query->orderBy('checked_in_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $checkIns,
        ]);
    }

    /**
     * Get upcoming check-ins for today
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

        $property = Property::find($request->property_id);

        // Ensure user owns the property
        if ($property->user_id !== Auth::guard('web')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this property.',
            ], 403);
        }

        // Get reservations that need check-in
        $reservations = Reservation::where('property_id', $request->property_id)
            ->where('status', Reservation::STATUS_CONFIRMED)
            ->whereDate('check_in', '<=', Carbon::today()->addDays($request->days_ahead ?? 7))
            ->whereDate('check_in', '>=', Carbon::today())
            ->whereNull('checked_in_at')
            ->with(['room', 'roomType', 'property'])
            ->orderBy('check_in')
            ->orderBy('expected_arrival_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reservations,
        ]);
    }

    /**
     * Check-in a reservation
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|exists:reservations,id',
            'room_id' => 'nullable|exists:rooms,id',
            'guest_count' => 'nullable|integer|min:1',
            'adult_count' => 'nullable|integer|min:1',
            'child_count' => 'nullable|integer|min:0',
            'identification_type' => 'nullable|string|in:passport,driving_license,id_card',
            'identification_number' => 'nullable|string|max:100',
            'identification_issued_by' => 'nullable|string|max:255',
            'identification_expiry_date' => 'nullable|date',
            'vehicle_registration' => 'nullable|string|max:50',
            'parking_space' => 'nullable|string|max:50',
            'special_requests' => 'nullable|string',
            'notes' => 'nullable|string',
            'key_type' => 'nullable|string|in:physical,electronic,card,code',
            'key_identifier' => 'nullable|string|max:100',
            'key_code' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $reservation = Reservation::with('property')->find($request->reservation_id);

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Reservation not found',
            ], 404);
        }

        // Ensure user owns the property
        if ($reservation->property->user_id !== Auth::guard('web')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this reservation.',
            ], 403);
        }

        // Check if already checked in
        if ($reservation->status === Reservation::STATUS_CHECKED_IN) {
            return response()->json([
                'success' => false,
                'message' => 'Reservation is already checked in.',
            ], 422);
        }

        // Validate check-in date
        $checkInDate = Carbon::parse($reservation->check_in);
        if (Carbon::today()->lt($checkInDate)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot check in before the check-in date.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Assign room if provided and different from current
            $roomId = $request->room_id ?? $reservation->room_id;
            if ($roomId && $roomId !== $reservation->room_id) {
                // Verify room is available
                $room = Room::find($roomId);
                if (!$room || $room->property_id !== $reservation->property_id) {
                    throw new \Exception('Invalid room selected');
                }

                // Check if room is available for these dates
                $availability = $this->availabilityService->checkAvailability(
                    propertyId: $reservation->property_id,
                    checkIn: $checkInDate,
                    checkOut: Carbon::parse($reservation->check_out),
                    roomId: $roomId
                );

                if (!$availability['available']) {
                    throw new \Exception('Selected room is not available for the reservation dates');
                }

                $reservation->room_id = $roomId;
            }

            // Auto-assign room if not assigned
            if (!$reservation->room_id && $reservation->room_type_id) {
                $room = $this->assignAvailableRoom($reservation);
                if ($room) {
                    $reservation->room_id = $room->id;
                }
            }

            // Create check-in record
            $checkIn = CheckIn::create([
                'reservation_id' => $reservation->id,
                'property_id' => $reservation->property_id,
                'room_id' => $reservation->room_id,
                'checked_in_by' => Auth::guard('web')->id(),
                'checked_in_at' => now(),
                'expected_check_in_at' => $reservation->expected_arrival_time 
                    ? Carbon::parse($reservation->check_in->format('Y-m-d') . ' ' . $reservation->expected_arrival_time)
                    : Carbon::parse($reservation->check_in)->setTime(14, 0), // Default 2 PM
                'actual_check_in_at' => now(),
                'guest_count' => $request->guest_count ?? ($reservation->adult_count + $reservation->child_count),
                'adult_count' => $request->adult_count ?? $reservation->adult_count,
                'child_count' => $request->child_count ?? $reservation->child_count,
                'identification_type' => $request->identification_type,
                'identification_number' => $request->identification_number,
                'identification_issued_by' => $request->identification_issued_by,
                'identification_expiry_date' => $request->identification_expiry_date,
                'vehicle_registration' => $request->vehicle_registration,
                'parking_space' => $request->parking_space,
                'special_requests' => $request->special_requests,
                'notes' => $request->notes,
                'status' => CheckIn::STATUS_COMPLETED,
            ]);

            // Issue key if requested
            if ($request->key_type) {
                $key = RoomKey::create([
                    'property_id' => $reservation->property_id,
                    'room_id' => $reservation->room_id,
                    'reservation_id' => $reservation->id,
                    'check_in_id' => $checkIn->id,
                    'key_type' => $request->key_type,
                    'key_identifier' => $request->key_identifier,
                    'key_code' => $request->key_code,
                    'status' => RoomKey::STATUS_ISSUED,
                    'issued_at' => now(),
                    'issued_by' => Auth::guard('web')->id(),
                ]);
            }

            // Update reservation status
            $reservation->update([
                'status' => Reservation::STATUS_CHECKED_IN,
                'checked_in_at' => now(),
                'room_id' => $reservation->room_id, // Ensure room is saved
            ]);

            DB::commit();

            $checkIn->load(['reservation', 'room', 'checkedInBy', 'keys']);

            return response()->json([
                'success' => true,
                'message' => 'Check-in completed successfully',
                'data' => $checkIn,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Check-in failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified check-in.
     */
    public function show(string $id): JsonResponse
    {
        $checkIn = CheckIn::with(['reservation', 'room', 'checkedInBy', 'keys'])->find($id);

        if (!$checkIn) {
            return response()->json([
                'success' => false,
                'message' => 'Check-in not found',
            ], 404);
        }

        // Ensure user owns the property
        if ($checkIn->property->user_id !== Auth::guard('web')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this check-in.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $checkIn,
        ]);
    }

    /**
     * Update the specified check-in.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $checkIn = CheckIn::with('property')->find($id);

        if (!$checkIn) {
            return response()->json([
                'success' => false,
                'message' => 'Check-in not found',
            ], 404);
        }

        // Ensure user owns the property
        if ($checkIn->property->user_id !== Auth::guard('web')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have access to this check-in.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'room_id' => 'nullable|exists:rooms,id',
            'guest_count' => 'nullable|integer|min:1',
            'adult_count' => 'nullable|integer|min:1',
            'child_count' => 'nullable|integer|min:0',
            'identification_type' => 'nullable|string|in:passport,driving_license,id_card',
            'identification_number' => 'nullable|string|max:100',
            'vehicle_registration' => 'nullable|string|max:50',
            'parking_space' => 'nullable|string|max:50',
            'special_requests' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update room assignment if changed
        if ($request->room_id && $request->room_id != $checkIn->room_id) {
            $room = Room::find($request->room_id);
            if (!$room || $room->property_id !== $checkIn->property_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid room selected',
                ], 422);
            }

            $checkIn->room_id = $request->room_id;
            $checkIn->reservation->room_id = $request->room_id;
            $checkIn->reservation->save();
        }

        $checkIn->update($request->only([
            'guest_count',
            'adult_count',
            'child_count',
            'identification_type',
            'identification_number',
            'vehicle_registration',
            'parking_space',
            'special_requests',
            'notes',
        ]));

        $checkIn->load(['reservation', 'room', 'checkedInBy', 'keys']);

        return response()->json([
            'success' => true,
            'message' => 'Check-in updated successfully',
            'data' => $checkIn,
        ]);
    }

    /**
     * Assign an available room to a reservation
     */
    protected function assignAvailableRoom(Reservation $reservation): ?Room
    {
        $checkIn = Carbon::parse($reservation->check_in);
        $checkOut = Carbon::parse($reservation->check_out);

        // Find available rooms of the same type
        $availableRooms = Room::where('property_id', $reservation->property_id)
            ->where('room_type_id', $reservation->room_type_id)
            ->where('is_active', true)
            ->get()
            ->filter(function ($room) use ($checkIn, $checkOut, $reservation) {
                // Check if room is available for these dates
                $hasConflict = Reservation::where('room_id', $room->id)
                    ->where('id', '!=', $reservation->id)
                    ->where('status', '!=', Reservation::STATUS_CANCELLED)
                    ->where('status', '!=', Reservation::STATUS_CHECKED_OUT)
                    ->where(function ($query) use ($checkIn, $checkOut) {
                        $query->where(function ($q) use ($checkIn, $checkOut) {
                            $q->whereBetween('check_in', [$checkIn, $checkOut])
                                ->orWhereBetween('check_out', [$checkIn, $checkOut])
                                ->orWhere(function ($overlap) use ($checkIn, $checkOut) {
                                    $overlap->where('check_in', '<=', $checkIn)
                                        ->where('check_out', '>=', $checkOut);
                                });
                        });
                    })
                    ->exists();

                return !$hasConflict;
            });

        return $availableRooms->first();
    }
}

