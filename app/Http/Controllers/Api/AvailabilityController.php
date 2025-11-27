<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomAvailability;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class AvailabilityController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService
    ) {
    }

    /**
     * Check room availability for a date range
     */
    public function check(Request $request): JsonResponse
    {
        
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'room_id' => 'nullable|exists:rooms,id',
            'room_type_id' => 'nullable|exists:room_types,id',
            'adult_count' => 'nullable|integer|min:1',
            'child_count' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);
        $propertyId = $request->property_id;

        $availability = $this->availabilityService->checkAvailability(
            propertyId: $propertyId,
            checkIn: $checkIn,
            checkOut: $checkOut,
            roomId: $request->room_id,
            roomTypeId: $request->room_type_id,
            adultCount: $request->adult_count,
            childCount: $request->child_count,
        );

        return response()->json([
            'success' => true,
            'data' => $availability,
        ]);
    }

    /**
     * Get availability calendar for a date range
     */
    public function calendar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'room_type_id' => 'nullable|exists:room_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $propertyId = $request->property_id;

        $calendar = $this->availabilityService->getAvailabilityCalendar(
            propertyId: $propertyId,
            startDate: $startDate,
            endDate: $endDate,
            roomTypeId: $request->room_type_id,
        );

        return response()->json([
            'success' => true,
            'data' => $calendar,
        ]);
    }

    /**
     * Block dates for maintenance or other reasons
     */
    public function blockDates(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'room_id' => 'nullable|exists:rooms,id',
            'room_type_id' => 'nullable|exists:room_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->availabilityService->blockDates(
            propertyId: $request->property_id,
            startDate: Carbon::parse($request->start_date),
            endDate: Carbon::parse($request->end_date),
            roomId: $request->room_id,
            roomTypeId: $request->room_type_id,
            reason: $request->reason,
        );

        return response()->json([
            'success' => true,
            'message' => 'Dates blocked successfully',
            'data' => $result,
        ]);
    }

    /**
     * Unblock dates
     */
    public function unblockDates(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'room_id' => 'nullable|exists:rooms,id',
            'room_type_id' => 'nullable|exists:room_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->availabilityService->unblockDates(
            propertyId: $request->property_id,
            startDate: Carbon::parse($request->start_date),
            endDate: Carbon::parse($request->end_date),
            roomId: $request->room_id,
            roomTypeId: $request->room_type_id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Dates unblocked successfully',
            'data' => $result,
        ]);
    }
}



