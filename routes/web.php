<?php

use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\RoomTypeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('login');
});

Route::get('/admin/dashboard', function () {
    return view('dashboard');
});

Route::get('/admin/calendar', function () {
    return view('calendar');
});

Route::get('/admin/rooms', function () {
    return view('rooms');
});

// API Routes
Route::prefix('api')->group(function () {
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

    // Room Management endpoints (requires authentication)
    // TODO: Add auth middleware when authentication is implemented
    Route::get('/rooms', [RoomController::class, 'index']);
    Route::post('/rooms', [RoomController::class, 'store']);
    Route::get('/rooms/{id}', [RoomController::class, 'show']);
    Route::put('/rooms/{id}', [RoomController::class, 'update']);
    Route::patch('/rooms/{id}', [RoomController::class, 'update']);
    Route::delete('/rooms/{id}', [RoomController::class, 'destroy']);
});
