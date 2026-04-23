<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\BookingController;

Route::prefix('v1')->group(function () {

    // ──────────────────────────────────────────
    //  PUBLIC — tidak butuh token
    // ──────────────────────────────────────────
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login',    [AuthController::class, 'login']);

    // Kendaraan bisa dilihat tanpa login
    Route::get('/vehicles',      [VehicleController::class, 'index']);
    Route::get('/vehicles/{id}', [VehicleController::class, 'show']);   // ← FIX: ada di controller, tapi HILANG di routes

    // ──────────────────────────────────────────
    //  PROTECTED — butuh Bearer token (Sanctum)
    // ──────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/auth/logout',  [AuthController::class, 'logout']);
        Route::get('/auth/me',       [AuthController::class, 'me']);
        Route::put('/auth/profile',  [AuthController::class, 'updateProfile']); // ← FIX: HILANG

        // Bookings
        Route::get('/bookings',              [BookingController::class, 'index']);
        Route::post('/bookings',             [BookingController::class, 'store']);
        Route::get('/bookings/{id}',         [BookingController::class, 'show']);   // ← FIX: HILANG
        Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']); // ← FIX: HILANG
        Route::post('/bookings/{id}/accept', [BookingController::class, 'accept']); // ← FIX: HILANG (untuk driver)
        Route::post('/bookings/{id}/confirm',[BookingController::class, 'confirm']); // ← FIX: HILANG (untuk admin)
    });
});