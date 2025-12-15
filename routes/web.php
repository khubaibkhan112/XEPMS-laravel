<?php

use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\ChannelImportController;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\CheckOutController;
use App\Http\Controllers\Api\DiscountController;
use App\Http\Controllers\Api\GuestController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\RefundController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\RoomFeatureController;
use App\Http\Controllers\Api\RoomTypeController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Public\BookingController as PublicBookingController;
use App\Http\Controllers\Public\PricingController as PublicPricingController;
use App\Http\Controllers\Public\PropertyController as PublicPropertyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('customer.search');
});

Route::get('/login', function () {
    return view('login');
})->name('login');

// Customer-facing routes (public access)
Route::get('/properties', function () {
    return view('customer.search');
})->name('properties.search');

Route::get('/properties/{id}', function ($id) {
    return view('customer.property-detail', ['propertyId' => $id]);
})->name('properties.show');

Route::get('/booking/{id}', function ($id) {
    return view('customer.booking-confirmation', ['bookingId' => $id]);
})->name('booking.confirmation');

// Public API routes (no authentication required)
Route::prefix('public/api')->group(function () {
    // Property endpoints
    Route::get('/properties', [PublicPropertyController::class, 'index']);
    Route::get('/properties/search', [PublicPropertyController::class, 'search']);
    Route::get('/properties/{id}', [PublicPropertyController::class, 'show']);

    // Availability endpoint
    Route::get('/availability/check', [AvailabilityController::class, 'check']);

    // Pricing endpoint
    Route::get('/pricing/calculate', [PublicPricingController::class, 'calculate']);

    // Booking endpoints
    Route::post('/bookings', [PublicBookingController::class, 'store']);
    Route::get('/bookings/{id}', [PublicBookingController::class, 'show']);

    // Public Payment endpoints (for customer payments)
    Route::post('/payments/create-intent', [\App\Http\Controllers\Public\PaymentController::class, 'createIntent']);
    Route::post('/payments/confirm', [\App\Http\Controllers\Public\PaymentController::class, 'confirm']);
    Route::get('/payments/status', [\App\Http\Controllers\Public\PaymentController::class, 'status']);
});

// Protected admin routes (require authentication)
Route::middleware('auth:web')->group(function () {
Route::get('/admin/dashboard', function () {
    return view('dashboard');
});

Route::get('/admin/calendar', function () {
    return view('calendar');
});

Route::get('/admin/rooms', function () {
    return view('rooms');
});

Route::get('/admin/properties', function () {
    return view('properties');
});

Route::get('/admin/room-types', function () {
    return view('room-types');
    });

    Route::get('/admin/room-features', function () {
        return view('room-features');
    });

    Route::get('/admin/staff', function () {
        return view('staff');
    });

    Route::get('/admin/reports', function () {
        return view('reports');
    });
});

// Authentication route (must be outside auth middleware to allow login)
Route::post('/api/auth/login', [LoginController::class, 'login']);

// API Routes (protected routes - requires authentication)
Route::prefix('api')->middleware('auth:web')->group(function () {
    // User routes
    Route::get('/auth/user', [LoginController::class, 'user']);
    Route::post('/auth/logout', [LoginController::class, 'logout']);

    // Availability endpoints
    Route::get('/availability/check', [AvailabilityController::class, 'check']);
    Route::get('/availability/calendar', [AvailabilityController::class, 'calendar']);
    Route::post('/availability/block', [AvailabilityController::class, 'blockDates']);
    Route::post('/availability/unblock', [AvailabilityController::class, 'unblockDates']);

    // Property Management endpoints
    Route::get('/properties', [PropertyController::class, 'index']);
    Route::post('/properties', [PropertyController::class, 'store']);
    Route::get('/properties/{id}', [PropertyController::class, 'show']);
    Route::put('/properties/{id}', [PropertyController::class, 'update']);
    Route::patch('/properties/{id}', [PropertyController::class, 'update']);
    Route::delete('/properties/{id}', [PropertyController::class, 'destroy']);

    // Room Type Management endpoints
    Route::get('/room-types', [RoomTypeController::class, 'index']);
    Route::post('/room-types', [RoomTypeController::class, 'store']);
    Route::get('/room-types/{id}', [RoomTypeController::class, 'show']);
    Route::put('/room-types/{id}', [RoomTypeController::class, 'update']);
    Route::patch('/room-types/{id}', [RoomTypeController::class, 'update']);
    Route::delete('/room-types/{id}', [RoomTypeController::class, 'destroy']);

    // Room Management endpoints
    Route::get('/rooms', [RoomController::class, 'index']);
    Route::post('/rooms', [RoomController::class, 'store']);
    Route::get('/rooms/{id}', [RoomController::class, 'show']);
    Route::put('/rooms/{id}', [RoomController::class, 'update']);
    Route::patch('/rooms/{id}', [RoomController::class, 'update']);
    Route::delete('/rooms/{id}', [RoomController::class, 'destroy']);

    // Room Feature Management endpoints
    Route::get('/room-features', [RoomFeatureController::class, 'index']);
    Route::post('/room-features', [RoomFeatureController::class, 'store']);
    Route::get('/room-features/available', [RoomFeatureController::class, 'getAvailableFeatures']);
    Route::get('/room-features/{id}', [RoomFeatureController::class, 'show']);
    Route::put('/room-features/{id}', [RoomFeatureController::class, 'update']);
    Route::patch('/room-features/{id}', [RoomFeatureController::class, 'update']);
    Route::delete('/room-features/{id}', [RoomFeatureController::class, 'destroy']);

    // Discount Management endpoints
    Route::get('/discounts', [DiscountController::class, 'index']);
    Route::post('/discounts', [DiscountController::class, 'store']);
    Route::post('/discounts/validate', [DiscountController::class, 'validateCode']);
    Route::get('/discounts/{id}', [DiscountController::class, 'show']);
    Route::put('/discounts/{id}', [DiscountController::class, 'update']);
    Route::patch('/discounts/{id}', [DiscountController::class, 'update']);
    Route::delete('/discounts/{id}', [DiscountController::class, 'destroy']);

    // Reservation Management endpoints
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::get('/reservations/{id}', [ReservationController::class, 'show']);
    Route::put('/reservations/{id}', [ReservationController::class, 'update']);
    Route::patch('/reservations/{id}', [ReservationController::class, 'update']);
    Route::delete('/reservations/{id}', [ReservationController::class, 'destroy']);

    // Payment Management endpoints
    Route::get('/payments/methods', [PaymentController::class, 'getPaymentMethods']);
    Route::post('/payments/create-intent', [PaymentController::class, 'createIntent']);
    Route::post('/payments/confirm', [PaymentController::class, 'confirm']);
    Route::post('/payments/process', [PaymentController::class, 'process']);
    Route::get('/payments/status', [PaymentController::class, 'status']);
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/payments/{id}', [PaymentController::class, 'show']);
    Route::post('/payments/{id}/refund', [PaymentController::class, 'refund']);

    // Guest Management endpoints
    Route::get('/guests', [GuestController::class, 'index']);
    Route::post('/guests', [GuestController::class, 'store']);
    Route::post('/guests/find-or-create', [GuestController::class, 'findOrCreate']);
    Route::get('/guests/{id}', [GuestController::class, 'show']);
    Route::put('/guests/{id}', [GuestController::class, 'update']);
    Route::patch('/guests/{id}', [GuestController::class, 'update']);
    Route::delete('/guests/{id}', [GuestController::class, 'destroy']);
    Route::get('/guests/{id}/booking-history', [GuestController::class, 'bookingHistory']);
    Route::get('/guests/{id}/preferences', [GuestController::class, 'preferences']);
    Route::post('/guests/{id}/preferences', [GuestController::class, 'preferences']);
    Route::post('/guests/{id}/update-loyalty', [GuestController::class, 'updateLoyalty']);

    // Refund Management endpoints
    Route::get('/refunds/policies', [RefundController::class, 'policies']);
    Route::post('/refunds/policies', [RefundController::class, 'createPolicy']);
    Route::put('/refunds/policies/{id}', [RefundController::class, 'updatePolicy']);
    Route::patch('/refunds/policies/{id}', [RefundController::class, 'updatePolicy']);
    Route::delete('/refunds/policies/{id}', [RefundController::class, 'deletePolicy']);
    Route::get('/reservations/{id}/refund/calculate', [RefundController::class, 'calculate']);
    Route::post('/reservations/{id}/refund/process', [RefundController::class, 'process']);
    Route::get('/reservations/{id}/refund/history', [RefundController::class, 'history']);

    // Check-in Management endpoints
    Route::get('/check-ins', [CheckInController::class, 'index']);
    Route::get('/check-ins/upcoming', [CheckInController::class, 'upcoming']);
    Route::post('/check-ins', [CheckInController::class, 'store']);
    Route::get('/check-ins/{id}', [CheckInController::class, 'show']);
    Route::put('/check-ins/{id}', [CheckInController::class, 'update']);
    Route::patch('/check-ins/{id}', [CheckInController::class, 'update']);

    // Check-out Management endpoints
    Route::get('/check-outs', [CheckOutController::class, 'index']);
    Route::get('/check-outs/upcoming', [CheckOutController::class, 'upcoming']);
    Route::post('/reservations/{id}/check-out', [CheckOutController::class, 'process']);
    Route::get('/reservations/{id}/check-out', [CheckOutController::class, 'show']);
    Route::post('/check-outs/{id}/payment', [CheckOutController::class, 'collectPayment']);
    Route::get('/check-outs/{id}/invoice', [CheckOutController::class, 'invoice']);

    // Channel Import endpoints
    Route::post('/channel-import/import', [ChannelImportController::class, 'import']);
    Route::post('/channel-import/import-by-channel', [ChannelImportController::class, 'importByChannel']);

    // Staff Management endpoints
    Route::get('/staff', [StaffController::class, 'index']);
    Route::post('/staff', [StaffController::class, 'store']);
    Route::get('/staff/roles', [StaffController::class, 'roles']);
    Route::get('/staff/permissions', [StaffController::class, 'permissions']);
    Route::get('/staff/{id}', [StaffController::class, 'show']);
    Route::put('/staff/{id}', [StaffController::class, 'update']);
    Route::patch('/staff/{id}', [StaffController::class, 'update']);
    Route::delete('/staff/{id}', [StaffController::class, 'destroy']);
    Route::get('/staff/{id}/roles-permissions', [StaffController::class, 'getRolesAndPermissions']);
    Route::post('/staff/{id}/assign-role', [StaffController::class, 'assignRole']);
    Route::post('/staff/{id}/remove-role', [StaffController::class, 'removeRole']);

    // Reports endpoints
    Route::get('/reports/dashboard', [ReportController::class, 'dashboard']);
    Route::get('/reports/reservations', [ReportController::class, 'reservations']);
    Route::get('/reports/revenue', [ReportController::class, 'revenue']);
    Route::get('/reports/occupancy', [ReportController::class, 'occupancy']);
    Route::get('/reports/guests', [ReportController::class, 'guests']);
});
