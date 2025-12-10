<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\GuestPreference;
use App\Models\GuestBookingHistory;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class GuestController extends Controller
{
    /**
     * Display a listing of guests
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'nullable|exists:properties,id',
            'search' => 'nullable|string|max:255',
            'loyalty_status' => 'nullable|string|in:none,bronze,silver,gold,platinum',
            'guest_type' => 'nullable|string|in:individual,corporate,travel_agent,group',
            'verified' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $query = Guest::with(['reservations', 'preferences']);

            // Filter by property if specified (guests who have bookings at this property)
            if ($request->property_id) {
                $query->whereHas('reservations', function ($q) use ($request) {
                    $q->where('property_id', $request->property_id);
                });
            }

            // Search
            if ($request->search) {
                $query->search($request->search);
            }

            // Loyalty status filter
            if ($request->loyalty_status) {
                $query->loyaltyStatus($request->loyalty_status);
            }

            // Guest type filter
            if ($request->guest_type) {
                $query->guestType($request->guest_type);
            }

            // Verified filter
            if ($request->verified) {
                $query->verified();
            }

            $perPage = $request->per_page ?? 15;
            $guests = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $guests,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching guests',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store a newly created guest
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:guests,email',
            'phone' => 'nullable|string|max:50',
            'country_code' => 'nullable|string|size:2',
            'address_line1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|size:3',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female,other,prefer_not_to_say',
            'nationality' => 'nullable|string|size:3',
            'passport_number' => 'nullable|string|max:255',
            'passport_expiry' => 'nullable|date',
            'company_name' => 'nullable|string|max:255',
            'guest_type' => 'nullable|string|in:individual,corporate,travel_agent,group',
            'preferred_language' => 'nullable|string|max:10',
            'preferred_currency' => 'nullable|string|size:3',
            'notes' => 'nullable|string',
            'marketing_opt_in' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Check if guest already exists by email or phone
            $existingGuest = null;
            if ($request->email) {
                $existingGuest = Guest::byEmail($request->email)->first();
            } elseif ($request->phone) {
                $existingGuest = Guest::byPhone($request->phone)->first();
            }

            if ($existingGuest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Guest already exists',
                    'data' => $existingGuest,
                ], 409);
            }

            $guest = Guest::create($request->only([
                'first_name', 'last_name', 'email', 'phone', 'country_code',
                'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'country',
                'date_of_birth', 'gender', 'nationality',
                'passport_number', 'passport_expiry',
                'company_name', 'company_address', 'company_phone', 'company_email',
                'guest_type', 'preferred_language', 'preferred_currency',
                'notes', 'marketing_opt_in',
            ]));

            $guest->load(['reservations', 'preferences']);

            return response()->json([
                'success' => true,
                'message' => 'Guest created successfully',
                'data' => $guest,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating guest',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Display the specified guest
     */
    public function show(int $id): JsonResponse
    {
        try {
            $guest = Guest::with([
                'reservations.property',
                'reservations.roomType',
                'bookingHistory.property',
                'preferences.property',
            ])->findOrFail($id);

            // Add statistics
            $guest->statistics = [
                'total_bookings' => $guest->getTotalBookings(),
                'total_spent' => $guest->getTotalSpent(),
                'average_rating' => $guest->getAverageRating(),
                'loyalty_points' => $guest->loyalty_points,
            ];

            return response()->json([
                'success' => true,
                'data' => $guest,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Guest not found',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 404);
        }
    }

    /**
     * Update the specified guest
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255|unique:guests,email,' . $id,
            'phone' => 'nullable|string|max:50',
            'country_code' => 'nullable|string|size:2',
            'address_line1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|size:3',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female,other,prefer_not_to_say',
            'nationality' => 'nullable|string|size:3',
            'passport_number' => 'nullable|string|max:255',
            'passport_expiry' => 'nullable|date',
            'company_name' => 'nullable|string|max:255',
            'guest_type' => 'nullable|string|in:individual,corporate,travel_agent,group',
            'loyalty_status' => 'nullable|string|in:none,bronze,silver,gold,platinum',
            'loyalty_points' => 'nullable|integer|min:0',
            'preferred_language' => 'nullable|string|max:10',
            'preferred_currency' => 'nullable|string|size:3',
            'notes' => 'nullable|string',
            'marketing_opt_in' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $guest = Guest::findOrFail($id);
            $guest->update($request->only([
                'first_name', 'last_name', 'email', 'phone', 'country_code',
                'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'country',
                'date_of_birth', 'gender', 'nationality',
                'passport_number', 'passport_expiry',
                'company_name', 'company_address', 'company_phone', 'company_email',
                'guest_type', 'loyalty_status', 'loyalty_points',
                'preferred_language', 'preferred_currency',
                'notes', 'marketing_opt_in',
            ]));

            $guest->load(['reservations', 'preferences']);

            return response()->json([
                'success' => true,
                'message' => 'Guest updated successfully',
                'data' => $guest,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating guest',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Remove the specified guest
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $guest = Guest::findOrFail($id);
            $guest->delete();

            return response()->json([
                'success' => true,
                'message' => 'Guest deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting guest',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get guest booking history
     */
    public function bookingHistory(int $id, Request $request): JsonResponse
    {
        try {
            $guest = Guest::findOrFail($id);

            $query = $guest->bookingHistory()->with(['reservation', 'property']);

            if ($request->property_id) {
                $query->where('property_id', $request->property_id);
            }

            $history = $query->orderBy('check_in', 'desc')->paginate($request->per_page ?? 15);

            return response()->json([
                'success' => true,
                'data' => $history,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching booking history',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get or create guest preferences
     */
    public function preferences(int $id, Request $request): JsonResponse
    {
        try {
            $guest = Guest::findOrFail($id);
            $propertyId = $request->property_id;

            if ($request->isMethod('get')) {
                $query = $guest->preferences();
                if ($propertyId) {
                    $query->where(function ($q) use ($propertyId) {
                        $q->where('property_id', $propertyId)->orWhereNull('property_id');
                    });
                }
                $preferences = $query->orderBy('priority', 'desc')->get();

                return response()->json([
                    'success' => true,
                    'data' => $preferences,
                ]);
            }

            // POST - Create/update preferences
            $validator = Validator::make($request->all(), [
                'preferences' => 'required|array',
                'preferences.*.preference_type' => 'required|string',
                'preferences.*.preference_key' => 'required|string',
                'preferences.*.preference_value' => 'nullable|string',
                'preferences.*.notes' => 'nullable|string',
                'preferences.*.priority' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::transaction(function () use ($guest, $request, $propertyId) {
                foreach ($request->preferences as $pref) {
                    GuestPreference::updateOrCreate(
                        [
                            'guest_id' => $guest->id,
                            'property_id' => $propertyId,
                            'preference_type' => $pref['preference_type'],
                            'preference_key' => $pref['preference_key'],
                        ],
                        [
                            'preference_value' => $pref['preference_value'] ?? null,
                            'notes' => $pref['notes'] ?? null,
                            'priority' => $pref['priority'] ?? 0,
                            'is_active' => true,
                        ]
                    );
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Preferences updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error managing preferences',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update guest loyalty status
     */
    public function updateLoyalty(int $id): JsonResponse
    {
        try {
            $guest = Guest::findOrFail($id);
            $guest->updateLoyaltyStatus();

            return response()->json([
                'success' => true,
                'message' => 'Loyalty status updated',
                'data' => [
                    'loyalty_status' => $guest->loyalty_status,
                    'loyalty_points' => $guest->loyalty_points,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating loyalty status',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Find or create guest by email/phone
     */
    public function findOrCreate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $guest = null;

            if ($request->email) {
                $guest = Guest::byEmail($request->email)->first();
            }

            if (!$guest && $request->phone) {
                $guest = Guest::byPhone($request->phone)->first();
            }

            if (!$guest) {
                $guest = Guest::create([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $guest->wasRecentlyCreated ? 'Guest created' : 'Guest found',
                'data' => $guest,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error finding or creating guest',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
