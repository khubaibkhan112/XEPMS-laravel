<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\CheckIn;
use App\Models\CheckOut;
use App\Models\Guest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Get reservation statistics
     */
    public function reservations(Request $request): JsonResponse
    {
        $validator = \Validator::make($request->all(), [
            'property_id' => 'nullable|exists:properties,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|string|in:confirmed,pending,cancelled,checked_in,checked_out',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $query = Reservation::query();

            if ($request->property_id) {
                $query->where('property_id', $request->property_id);
            }

            if ($request->start_date) {
                $query->where('check_in', '>=', $request->start_date);
            }

            if ($request->end_date) {
                $query->where('check_out', '<=', $request->end_date);
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            $reservations = $query->get();

            $stats = [
                'total' => $reservations->count(),
                'confirmed' => $reservations->where('status', 'confirmed')->count(),
                'pending' => $reservations->where('status', 'pending')->count(),
                'cancelled' => $reservations->where('status', 'cancelled')->count(),
                'checked_in' => $reservations->where('status', 'checked_in')->count(),
                'checked_out' => $reservations->where('status', 'checked_out')->count(),
                'total_revenue' => $reservations->sum('total_amount'),
                'total_paid' => $reservations->sum('paid_amount'),
                'total_balance' => $reservations->sum('balance_due'),
                'total_nights' => $reservations->sum('nights'),
                'average_stay_length' => $reservations->count() > 0 
                    ? round($reservations->sum('nights') / $reservations->count(), 2) 
                    : 0,
                'average_daily_rate' => $reservations->sum('nights') > 0
                    ? round($reservations->sum('total_amount') / $reservations->sum('nights'), 2)
                    : 0,
            ];

            // Group by status
            $byStatus = $reservations->groupBy('status')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'revenue' => $group->sum('total_amount'),
                ];
            });

            // Group by source
            $bySource = $reservations->groupBy('source')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'revenue' => $group->sum('total_amount'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $stats,
                    'by_status' => $byStatus,
                    'by_source' => $bySource,
                    'reservations' => $reservations->take(100)->values(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating reservation report',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get revenue statistics
     */
    public function revenue(Request $request): JsonResponse
    {
        $validator = \Validator::make($request->all(), [
            'property_id' => 'nullable|exists:properties,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'group_by' => 'nullable|string|in:day,week,month,year',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $query = Reservation::whereIn('status', ['confirmed', 'checked_in', 'checked_out']);

            if ($request->property_id) {
                $query->where('property_id', $request->property_id);
            }

            $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
            $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

            $query->whereBetween('check_in', [$startDate, $endDate]);

            $groupBy = $request->group_by ?? 'day';

            $revenue = $query->selectRaw(
                match ($groupBy) {
                    'day' => "DATE(check_in) as period",
                    'week' => "DATE_FORMAT(check_in, '%Y-%u') as period",
                    'month' => "DATE_FORMAT(check_in, '%Y-%m') as period",
                    'year' => "YEAR(check_in) as period",
                    default => "DATE(check_in) as period",
                },
                'SUM(total_amount) as total_revenue',
                'SUM(paid_amount) as total_paid',
                'SUM(balance_due) as total_balance',
                'COUNT(*) as reservation_count'
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

            $payments = Payment::whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw(
                    match ($groupBy) {
                        'day' => "DATE(created_at) as period",
                        'week' => "DATE_FORMAT(created_at, '%Y-%u') as period",
                        'month' => "DATE_FORMAT(created_at, '%Y-%m') as period",
                        'year' => "YEAR(created_at) as period",
                        default => "DATE(created_at) as period",
                    },
                    'SUM(amount) as total_payments',
                    'COUNT(*) as payment_count'
                )
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            $summary = [
                'total_revenue' => $query->sum('total_amount'),
                'total_paid' => $query->sum('paid_amount'),
                'total_balance' => $query->sum('balance_due'),
                'total_payments' => Payment::whereBetween('created_at', [$startDate, $endDate])->sum('amount'),
                'reservation_count' => $query->count(),
                'average_transaction' => $query->count() > 0 
                    ? round($query->sum('total_amount') / $query->count(), 2) 
                    : 0,
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'revenue_by_period' => $revenue,
                    'payments_by_period' => $payments,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating revenue report',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get occupancy statistics
     */
    public function occupancy(Request $request): JsonResponse
    {
        $validator = \Validator::make($request->all(), [
            'property_id' => 'nullable|exists:properties,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $propertyId = $request->property_id;
            $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
            $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

            // Get total rooms
            $totalRoomsQuery = Room::where('is_active', true);
            if ($propertyId) {
                $totalRoomsQuery->where('property_id', $propertyId);
            }
            $totalRooms = $totalRoomsQuery->count();

            // Calculate occupancy for each day in range
            $dates = [];
            $currentDate = $startDate->copy();

            while ($currentDate <= $endDate) {
                $occupiedQuery = Reservation::whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
                    ->where('check_in', '<=', $currentDate->toDateString())
                    ->where('check_out', '>', $currentDate->toDateString());

                if ($propertyId) {
                    $occupiedQuery->where('property_id', $propertyId);
                }

                $occupiedRooms = $occupiedQuery->distinct()->count('room_id');
                $availableRooms = max(0, $totalRooms - $occupiedRooms);
                $occupancyRate = $totalRooms > 0 
                    ? round(($occupiedRooms / $totalRooms) * 100, 2) 
                    : 0;

                $dates[] = [
                    'date' => $currentDate->toDateString(),
                    'occupied_rooms' => $occupiedRooms,
                    'available_rooms' => $availableRooms,
                    'total_rooms' => $totalRooms,
                    'occupancy_rate' => $occupancyRate,
                ];

                $currentDate->addDay();
            }

            // Calculate average occupancy
            $avgOccupancy = count($dates) > 0
                ? round(collect($dates)->avg('occupancy_rate'), 2)
                : 0;

            // Group by room type
            $byRoomType = RoomType::when($propertyId, function ($q) use ($propertyId) {
                    $q->where('property_id', $propertyId);
                })
                ->withCount(['rooms as total_rooms' => function ($query) {
                    $query->where('is_active', true);
                }])
                ->get()
                ->map(function ($roomType) use ($startDate, $endDate) {
                    $occupiedNights = Reservation::where('room_type_id', $roomType->id)
                        ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
                        ->whereBetween('check_in', [$startDate, $endDate])
                        ->sum('nights');

                    $availableNights = $roomType->total_rooms * $startDate->diffInDays($endDate);
                    $occupancyRate = $availableNights > 0
                        ? round(($occupiedNights / $availableNights) * 100, 2)
                        : 0;

                    return [
                        'room_type_id' => $roomType->id,
                        'room_type_name' => $roomType->name,
                        'total_rooms' => $roomType->total_rooms,
                        'occupied_nights' => $occupiedNights,
                        'available_nights' => $availableNights,
                        'occupancy_rate' => $occupancyRate,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'total_rooms' => $totalRooms,
                    'average_occupancy_rate' => $avgOccupancy,
                    'daily_occupancy' => $dates,
                    'by_room_type' => $byRoomType,
                    'period' => [
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating occupancy report',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get guest statistics
     */
    public function guests(Request $request): JsonResponse
    {
        $validator = \Validator::make($request->all(), [
            'property_id' => 'nullable|exists:properties,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $query = Guest::query();

            if ($request->start_date) {
                $query->where('created_at', '>=', $request->start_date);
            }

            if ($request->end_date) {
                $query->where('created_at', '<=', $request->end_date);
            }

            $guests = $query->get();

            // Get guests with reservations in date range
            $reservationQuery = Reservation::whereIn('status', ['confirmed', 'checked_in', 'checked_out']);

            if ($request->property_id) {
                $reservationQuery->where('property_id', $request->property_id);
            }

            if ($request->start_date) {
                $reservationQuery->where('check_in', '>=', $request->start_date);
            }

            if ($request->end_date) {
                $reservationQuery->where('check_out', '<=', $request->end_date);
            }

            $guestIds = $reservationQuery->pluck('guest_id')->unique()->filter();

            $activeGuests = Guest::whereIn('id', $guestIds)->get();

            // Guest statistics
            $stats = [
                'total_guests' => $guests->count(),
                'new_guests' => $guests->where('created_at', '>=', Carbon::now()->startOfMonth())->count(),
                'active_guests' => $activeGuests->count(),
                'repeat_guests' => Guest::whereIn('id', $guestIds)
                    ->has('reservations', '>', 1)
                    ->count(),
            ];

            // Group by country
            $byCountry = $activeGuests->groupBy('country_code')
                ->map(function ($group) {
                    return [
                        'country_code' => $group->first()->country_code,
                        'count' => $group->count(),
                    ];
                })
                ->sortByDesc('count')
                ->values();

            // Top guests by spending
            $topGuests = Reservation::select('guest_id', DB::raw('SUM(total_amount) as total_spent'), DB::raw('COUNT(*) as reservation_count'))
                ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
                ->whereIn('guest_id', $guestIds)
                ->groupBy('guest_id')
                ->orderByDesc('total_spent')
                ->limit(10)
                ->with('guest:id,first_name,last_name,email')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $stats,
                    'by_country' => $byCountry,
                    'top_guests' => $topGuests,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating guest report',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get dashboard summary statistics
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $propertyId = $request->property_id;

            $today = Carbon::today();
            $thisMonth = Carbon::now()->startOfMonth();
            $lastMonth = Carbon::now()->subMonth()->startOfMonth();

            // Today's check-ins
            $todayCheckIns = CheckIn::whereDate('check_in_date', $today)
                ->when($propertyId, function ($q) use ($propertyId) {
                    $q->where('property_id', $propertyId);
                })
                ->count();

            // Today's check-outs
            $todayCheckOuts = CheckOut::whereDate('check_out_date', $today)
                ->when($propertyId, function ($q) use ($propertyId) {
                    $q->where('property_id', $propertyId);
                })
                ->count();

            // Current occupancy
            $currentOccupancy = Reservation::whereIn('status', ['confirmed', 'checked_in'])
                ->where('check_in', '<=', $today)
                ->where('check_out', '>', $today)
                ->when($propertyId, function ($q) use ($propertyId) {
                    $q->where('property_id', $propertyId);
                })
                ->count();

            // This month's revenue
            $thisMonthRevenue = Reservation::whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
                ->where('check_in', '>=', $thisMonth)
                ->when($propertyId, function ($q) use ($propertyId) {
                    $q->where('property_id', $propertyId);
                })
                ->sum('total_amount');

            // Last month's revenue
            $lastMonthRevenue = Reservation::whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
                ->where('check_in', '>=', $lastMonth)
                ->where('check_in', '<', $thisMonth)
                ->when($propertyId, function ($q) use ($propertyId) {
                    $q->where('property_id', $propertyId);
                })
                ->sum('total_amount');

            // Revenue change percentage
            $revenueChange = $lastMonthRevenue > 0
                ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 2)
                : 0;

            // Upcoming check-ins (next 7 days)
            $upcomingCheckIns = Reservation::whereIn('status', ['confirmed', 'pending'])
                ->whereBetween('check_in', [$today, $today->copy()->addDays(7)])
                ->when($propertyId, function ($q) use ($propertyId) {
                    $q->where('property_id', $propertyId);
                })
                ->count();

            // Pending reservations
            $pendingReservations = Reservation::where('status', 'pending')
                ->when($propertyId, function ($q) use ($propertyId) {
                    $q->where('property_id', $propertyId);
                })
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'today_check_ins' => $todayCheckIns,
                    'today_check_outs' => $todayCheckOuts,
                    'current_occupancy' => $currentOccupancy,
                    'this_month_revenue' => round($thisMonthRevenue, 2),
                    'last_month_revenue' => round($lastMonthRevenue, 2),
                    'revenue_change_percent' => $revenueChange,
                    'upcoming_check_ins' => $upcomingCheckIns,
                    'pending_reservations' => $pendingReservations,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating dashboard report',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

