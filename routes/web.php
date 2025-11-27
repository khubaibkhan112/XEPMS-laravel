<?php

use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\RoomTypeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Public\BookingController as PublicBookingController;
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

    // Booking endpoints
    Route::post('/bookings', [PublicBookingController::class, 'store']);
    Route::get('/bookings/{id}', [PublicBookingController::class, 'show']);
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

    // Reservation Management endpoints
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::get('/reservations/{id}', [ReservationController::class, 'show']);
    Route::put('/reservations/{id}', [ReservationController::class, 'update']);
    Route::patch('/reservations/{id}', [ReservationController::class, 'update']);
    Route::delete('/reservations/{id}', [ReservationController::class, 'destroy']);
});
