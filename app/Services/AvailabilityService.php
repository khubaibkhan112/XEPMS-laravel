<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomAvailability;
use App\Models\RoomType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Check room availability for a date range
     */
    public function checkAvailability(
        int $propertyId,
        Carbon $checkIn,
        Carbon $checkOut,
        ?int $roomId = null,
        ?int $roomTypeId = null,
        ?int $adultCount = null,
        ?int $childCount = null,
    ): array {
        $nights = $checkIn->diffInDays($checkOut);
        $dateRange = $this->generateDateRange($checkIn, $checkOut);

        // Get active rooms
        $query = Room::where('property_id', $propertyId)
            ->where('is_active', true);

        if ($roomId) {
            $query->where('id', $roomId);
        }

        if ($roomTypeId) {
            $query->where('room_type_id', $roomTypeId);
        }

        if ($adultCount || $childCount) {
            $totalGuests = ($adultCount ?? 0) + ($childCount ?? 0);
            $query->where('max_occupancy', '>=', $totalGuests);
        }

        $rooms = $query->with('roomType')->get();

        $availableRooms = [];

        foreach ($rooms as $room) {
            $isAvailable = true;
            $conflicts = [];
            $blockedDates = [];

            // Check each date in the range
            foreach ($dateRange as $date) {
                // Check existing reservations
                $conflictingReservation = Reservation::where('room_id', $room->id)
                    ->where('status', '!=', Reservation::STATUS_CANCELLED)
                    ->where('check_in', '<=', $date->format('Y-m-d'))
                    ->where('check_out', '>', $date->format('Y-m-d'))
                    ->first();

                if ($conflictingReservation) {
                    $isAvailable = false;
                    $conflicts[] = [
                        'date' => $date->format('Y-m-d'),
                        'reservation_id' => $conflictingReservation->id,
                        'guest_name' => $conflictingReservation->guest_first_name . ' ' . $conflictingReservation->guest_last_name,
                    ];
                }

                // Check blocked dates
                $blocked = RoomAvailability::where('room_id', $room->id)
                    ->where('date', $date->format('Y-m-d'))
                    ->where('is_available', false)
                    ->first();

                if ($blocked) {
                    $isAvailable = false;
                    $blockedDates[] = $date->format('Y-m-d');
                }

                // Check restrictions
                $availability = RoomAvailability::where('room_id', $room->id)
                    ->where('date', $date->format('Y-m-d'))
                    ->first();

                if ($availability) {
                    // Check min stay
                    if ($availability->min_stay && $nights < $availability->min_stay) {
                        $isAvailable = false;
                    }

                    // Check max stay
                    if ($availability->max_stay && $nights > $availability->max_stay) {
                        $isAvailable = false;
                    }

                    // Check closed to arrival
                    if ($availability->closed_to_arrival && $date->isSameDay($checkIn)) {
                        $isAvailable = false;
                    }

                    // Check closed to departure
                    $departureDate = $checkOut->copy()->subDay();
                    if ($availability->closed_to_departure && $date->isSameDay($departureDate)) {
                        $isAvailable = false;
                    }
                }
            }

            $availableRooms[] = [
                'room_id' => $room->id,
                'room_name' => $room->name,
                'room_number' => $room->room_number,
                'room_type' => $room->roomType?->name,
                'max_occupancy' => $room->max_occupancy,
                'is_available' => $isAvailable,
                'conflicts' => $conflicts,
                'blocked_dates' => $blockedDates,
                'nights' => $nights,
            ];
        }

        return [
            'check_in' => $checkIn->format('Y-m-d'),
            'check_out' => $checkOut->format('Y-m-d'),
            'nights' => $nights,
            'total_rooms_checked' => $rooms->count(),
            'available_rooms' => collect($availableRooms)->where('is_available', true)->count(),
            'rooms' => $availableRooms,
        ];
    }

    /**
     * Get availability calendar for a date range
     */
    public function getAvailabilityCalendar(
        int $propertyId,
        Carbon $startDate,
        Carbon $endDate,
        ?int $roomTypeId = null,
    ): array {
        $dateRange = $this->generateDateRange($startDate, $endDate);

        $query = Room::where('property_id', $propertyId)
            ->where('is_active', true);

        if ($roomTypeId) {
            $query->where('room_type_id', $roomTypeId);
        }

        $rooms = $query->with('roomType')->get();
        $totalRooms = $rooms->count();

        $calendar = [];

        foreach ($dateRange as $date) {
            $availableCount = 0;
            $blockedCount = 0;
            $reservedCount = 0;

            foreach ($rooms as $room) {
                // Check if room is reserved
                $reserved = Reservation::where('room_id', $room->id)
                    ->where('status', '!=', Reservation::STATUS_CANCELLED)
                    ->where('check_in', '<=', $date->format('Y-m-d'))
                    ->where('check_out', '>', $date->format('Y-m-d'))
                    ->exists();

                if ($reserved) {
                    $reservedCount++;
                    continue;
                }

                // Check if room is blocked
                $blocked = RoomAvailability::where('room_id', $room->id)
                    ->where('date', $date->format('Y-m-d'))
                    ->where('is_available', false)
                    ->exists();

                if ($blocked) {
                    $blockedCount++;
                    continue;
                }

                $availableCount++;
            }

            $calendar[] = [
                'date' => $date->format('Y-m-d'),
                'total_rooms' => $totalRooms,
                'available' => $availableCount,
                'reserved' => $reservedCount,
                'blocked' => $blockedCount,
                'occupancy_rate' => $totalRooms > 0 ? round(($reservedCount / $totalRooms) * 100, 2) : 0,
            ];
        }

        return [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'total_rooms' => $totalRooms,
            'calendar' => $calendar,
        ];
    }

    /**
     * Block dates for maintenance or other reasons
     */
    public function blockDates(
        int $propertyId,
        Carbon $startDate,
        Carbon $endDate,
        ?int $roomId = null,
        ?int $roomTypeId = null,
        ?string $reason = null,
    ): array {
        $dateRange = $this->generateDateRange($startDate, $endDate);
        $blocked = [];

        $query = Room::where('property_id', $propertyId);

        if ($roomId) {
            $query->where('id', $roomId);
        }

        if ($roomTypeId) {
            $query->where('room_type_id', $roomTypeId);
        }

        $rooms = $query->get();

        foreach ($rooms as $room) {
            foreach ($dateRange as $date) {
                // Check if there are existing reservations
                $hasReservation = Reservation::where('room_id', $room->id)
                    ->where('status', '!=', Reservation::STATUS_CANCELLED)
                    ->where('check_in', '<=', $date->format('Y-m-d'))
                    ->where('check_out', '>', $date->format('Y-m-d'))
                    ->exists();

                if ($hasReservation) {
                    continue; // Skip dates with existing reservations
                }

                $availability = RoomAvailability::updateOrCreate(
                    [
                        'property_id' => $propertyId,
                        'room_id' => $room->id,
                        'room_type_id' => $room->room_type_id,
                        'date' => $date->format('Y-m-d'),
                    ],
                    [
                        'is_available' => false,
                        'available_count' => 0,
                        'restrictions' => [
                            'blocked_reason' => $reason ?? 'Maintenance',
                            'blocked_at' => now()->toIso8601String(),
                        ],
                    ]
                );

                $blocked[] = $availability;
            }
        }

        return [
            'blocked_count' => count($blocked),
            'dates' => $dateRange->map(fn ($date) => $date->format('Y-m-d'))->toArray(),
            'rooms' => $rooms->pluck('id')->toArray(),
        ];
    }

    /**
     * Unblock dates
     */
    public function unblockDates(
        int $propertyId,
        Carbon $startDate,
        Carbon $endDate,
        ?int $roomId = null,
        ?int $roomTypeId = null,
    ): array {
        $dateRange = $this->generateDateRange($startDate, $endDate);

        $query = RoomAvailability::where('property_id', $propertyId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->where('is_available', false);

        if ($roomId) {
            $query->where('room_id', $roomId);
        }

        if ($roomTypeId) {
            $query->where('room_type_id', $roomTypeId);
        }

        $blocked = $query->get();
        $unblockedCount = $blocked->count();

        // Delete blocked availability records
        $query->delete();

        return [
            'unblocked_count' => $unblockedCount,
            'dates' => $dateRange->map(fn ($date) => $date->format('Y-m-d'))->toArray(),
        ];
    }

    /**
     * Generate date range array
     */
    protected function generateDateRange(Carbon $start, Carbon $end): Collection
    {
        $dates = collect();
        $current = $start->copy();

        while ($current->lt($end)) {
            $dates->push($current->copy());
            $current->addDay();
        }

        return $dates;
    }
}

