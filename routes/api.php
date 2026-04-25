<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\FcmController;

Route::prefix('v1')->group(function () {

    // ════════════════════════════════════════════════════════════
    // PUBLIC — tidak perlu auth
    // ════════════════════════════════════════════════════════════

    Route::prefix('auth')->group(function () {
        Route::post('register',        [AuthController::class, 'register']);
        Route::post('login',           [AuthController::class, 'login']);       // admin diblokir di dalam method
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    });

    // Midtrans webhook — public, diverifikasi via signature key di dalam method
    Route::post('payments/notification', [PaymentController::class, 'notification'])
         ->name('api.payments.notification');

    // ════════════════════════════════════════════════════════════
    // PROTECTED — semua route di bawah butuh token Sanctum
    // ════════════════════════════════════════════════════════════
    Route::middleware('auth:sanctum')->group(function () {

        // ── Auth ─────────────────────────────────────────────────
        Route::prefix('auth')->group(function () {
            Route::post('logout',  [AuthController::class, 'logout']);
            Route::get('me',       [AuthController::class, 'me']);
            Route::put('profile',  [AuthController::class, 'updateProfile']);
        });

        // ── FCM Token (push notification) ────────────────────────
        // Panggil POST setelah login dan saat token refresh
        // Panggil DELETE saat logout
        Route::prefix('fcm')->group(function () {
            Route::post('token',   [FcmController::class, 'storeToken']);
            Route::delete('token', [FcmController::class, 'deleteToken']);
        });

        // ── Kendaraan ────────────────────────────────────────────
        Route::prefix('vehicles')->group(function () {
            Route::get('/',    [VehicleController::class, 'index']);
            Route::get('{id}', [VehicleController::class, 'show']);

            Route::middleware('role:admin')->group(function () {
                Route::post('/',      [VehicleController::class, 'store']);
                Route::put('{id}',    [VehicleController::class, 'update']);
                Route::delete('{id}', [VehicleController::class, 'destroy']);
            });
        });

        // ── Booking ──────────────────────────────────────────────
        Route::prefix('bookings')->group(function () {
            Route::get('/',    [BookingController::class, 'index']);   // pengguna: milik sendiri | driver: yang di-assign
            Route::post('/',   [BookingController::class, 'store']);   // pengguna buat booking baru
            Route::get('{id}', [BookingController::class, 'show']);    // detail booking

            // Pengguna / Admin
            Route::post('{id}/cancel', [BookingController::class, 'cancel']);

            // Status payment untuk booking tertentu (dipakai mobile setelah Snap)
            Route::get('{id}/payment-status', [BookingController::class, 'paymentStatus']);

            // Driver: konfirmasi penjemputan → confirmed → ongoing
            // MENGGANTIKAN: accept() dan confirm() yang dihapus
            Route::middleware('role:driver')
                 ->post('{id}/pickup', [BookingController::class, 'pickup']);

            // Chat per booking
            Route::get('{id}/messages',  [ChatController::class, 'index']);
            Route::post('{id}/messages', [ChatController::class, 'store']);
        });

        // ── Driver ───────────────────────────────────────────────
        Route::prefix('drivers')->group(function () {
            Route::get('/',    [DriverController::class, 'index']);
            Route::get('{id}', [DriverController::class, 'show']);

            Route::middleware('role:driver')
                 ->post('location', [DriverController::class, 'updateLocation']);

            Route::middleware('role:admin')
                 ->post('{id}/toggle', [DriverController::class, 'toggle']);
        });

        // ── Payment ──────────────────────────────────────────────
        Route::middleware('role:admin')
             ->get('payments', [PaymentController::class, 'index']);

        // ── Dashboard & Laporan (Admin only) ─────────────────────
        Route::middleware('role:admin')->group(function () {
            Route::get('dashboard', [DashboardController::class, 'index']);
            Route::get('reports',   [DashboardController::class, 'reports']);
        });

        // ── Users (Admin only) ───────────────────────────────────
        Route::middleware('role:admin')->prefix('users')->group(function () {
            Route::get('/',            [UserController::class, 'index']);
            Route::get('{id}',         [UserController::class, 'show']);
            Route::post('{id}/toggle', [UserController::class, 'toggle']);
        });

        // ── Notifikasi ───────────────────────────────────────────
        Route::prefix('notifications')->group(function () {
            Route::get('/',          [NotificationController::class, 'index']);
            Route::post('read-all',  [NotificationController::class, 'readAll']);
            Route::post('{id}/read', [NotificationController::class, 'markRead']);
        });

        // ── Tiket Bantuan ────────────────────────────────────────
        Route::prefix('tickets')->group(function () {
            Route::get('/',           [TicketController::class, 'index']);
            Route::post('/',          [TicketController::class, 'store']);
            Route::get('{id}',        [TicketController::class, 'show']);
            Route::post('{id}/reply', [TicketController::class, 'reply']);
        });
    });

    // ════════════════════════════════════════════════════════════
    // DEBUG — hapus di production
    // ════════════════════════════════════════════════════════════
    Route::get('debug', function () {
        return [
            'mongodb_extension' => extension_loaded('mongodb'),
            'db_connection'     => env('DB_CONNECTION'),
            'db_uri_set'        => !empty(env('DB_URI')),
        ];
    });
});