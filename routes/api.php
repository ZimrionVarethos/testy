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
use App\Http\Controllers\Api\LandingController;
use App\Http\Controllers\Api\WebAuthController;
use App\Http\Controllers\Api\AssetController;

Route::prefix('v1')->group(function () {

    // ════════════════════════════════════════════════════════════
    // PUBLIC — tidak perlu auth
    // ════════════════════════════════════════════════════════════

    // Data landing page (dipakai WelcomeController via Http::)
    Route::get('landing', [LandingController::class, 'index'])->name('api.landing.index');
    Route::get('landing/available-vehicles', [LandingController::class, 'availableVehicles'])->name('api.landing.available-vehicles');

    Route::prefix('auth')->group(function () {
        Route::post('register',        [AuthController::class, 'register']);
        Route::post('login',           [AuthController::class, 'login']);       // admin diblokir di dalam method
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::get('verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
             ->middleware(['signed', 'throttle:6,1'])
             ->name('api.auth.verify-email');
    });

    Route::prefix('web/auth')->group(function () {
        Route::post('login', [WebAuthController::class, 'login']);
    });

    // Midtrans webhook — public, diverifikasi via signature key di dalam method
    Route::post('payments/notification', [PaymentController::class, 'notification'])
         ->name('api.payments.notification');

    // ════════════════════════════════════════════════════════════
    // PROTECTED — semua route di bawah butuh token Sanctum
    // ════════════════════════════════════════════════════════════
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('web/auth')->group(function () {
            Route::get('me', [WebAuthController::class, 'me']);
            Route::post('logout', [WebAuthController::class, 'logout']);
        });

        // ── Auth ─────────────────────────────────────────────────
        Route::prefix('auth')->group(function () {
            Route::post('logout',  [AuthController::class, 'logout']);
            Route::get('me',       [AuthController::class, 'me']);
            Route::put('profile',  [AuthController::class, 'updateProfile']);
            Route::post('avatar',  [AuthController::class, 'uploadAvatar']);
            Route::delete('avatar',[AuthController::class, 'deleteAvatar']);
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

        // ── Chat list (semua booking dengan chat aktif) ──────────
        Route::get('chats', [BookingController::class, 'chatList']);

        // ── Booking ──────────────────────────────────────────────
        Route::prefix('bookings')->group(function () {
            Route::get('/',    [BookingController::class, 'index']);   // pengguna: milik sendiri | driver: yang di-assign
            Route::post('/',   [BookingController::class, 'store']);   // pengguna buat booking baru
            Route::get('{id}', [BookingController::class, 'show']);    // detail booking

            // Pengguna / Admin
            Route::post('{id}/cancel', [BookingController::class, 'cancel']);

            // Status payment untuk booking tertentu (dipakai mobile setelah Snap)
            Route::get('{id}/payment-status', [BookingController::class, 'paymentStatus']);

            // Generate snap token untuk mobile payment
            Route::post('{id}/snap', [PaymentController::class, 'createSnap']);

            // Driver: konfirmasi penjemputan → confirmed → ongoing
            Route::middleware('role:driver')
                 ->post('{id}/pickup', [BookingController::class, 'pickup']);

            // Chat per booking
            Route::get('{id}/messages',  [ChatController::class, 'index']);
            Route::post('{id}/messages', [ChatController::class, 'store']);

            // Rating (pengguna setelah booking completed)
            Route::post('{id}/rating', [BookingController::class, 'storeRating']);

            // Admin: lihat driver tersedia & assign driver
            Route::middleware('role:admin')->group(function () {
                Route::get('{id}/available-drivers', [BookingController::class, 'availableDrivers']);
                Route::post('{id}/assign-driver',    [BookingController::class, 'assignDriver']);
            });
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
        Route::prefix('payments')->group(function () {
            Route::get('/', [PaymentController::class, 'index']);
            Route::get('{id}', [PaymentController::class, 'show']);
        });

        // ── Dashboard ────────────────────────────────────────────
        Route::prefix('dashboard')->group(function () {
            Route::get('driver',   [DashboardController::class, 'driver']);
            Route::get('pengguna', [DashboardController::class, 'pengguna']);
            Route::middleware('role:admin')->group(function () {
                Route::get('/',                   [DashboardController::class, 'index']);
                Route::get('reports',             [DashboardController::class, 'reports']);
                Route::delete('reports/cleanup',  [DashboardController::class, 'deleteOld']);
            });
        });

        // ── Users (Admin only) ───────────────────────────────────
        Route::middleware('role:admin')->prefix('users')->group(function () {
            Route::get('/',            [UserController::class, 'index']);
            Route::get('{id}',         [UserController::class, 'show']);
            Route::post('{id}/toggle', [UserController::class, 'toggle']);
        });

        // ── Notifikasi ───────────────────────────────────────────
        Route::prefix('notifications')->group(function () {
            Route::get('/',                  [NotificationController::class, 'index']);
            Route::post('read-all',          [NotificationController::class, 'readAll']);
            Route::delete('/',               [NotificationController::class, 'destroyAll']);
            Route::post('delete-selected',   [NotificationController::class, 'destroySelected']);
            Route::post('{id}/read',         [NotificationController::class, 'markRead']);
            Route::delete('{id}',            [NotificationController::class, 'destroy']);
        });

        // ── Tiket Bantuan ────────────────────────────────────────
        Route::prefix('tickets')->group(function () {
            Route::get('/',           [TicketController::class, 'index']);
            Route::post('/',          [TicketController::class, 'store']);
            Route::get('{id}',        [TicketController::class, 'show']);
            Route::post('{id}/reply', [TicketController::class, 'reply']);
        });

        // ── Tiket (Admin) ─────────────────────────────────────────
        Route::middleware('role:admin')->prefix('admin/tickets')->group(function () {
            Route::get('/',                    [TicketController::class, 'adminIndex']);
            Route::get('{id}',                 [TicketController::class, 'adminShow']);
            Route::post('{id}/reply',          [TicketController::class, 'adminReply']);
            Route::put('{id}/status',          [TicketController::class, 'adminUpdateStatus']);
        });

        Route::middleware('role:admin')->prefix('admin/maps')->group(function () {
            Route::get('/', [DashboardController::class, 'maps']);
            Route::get('{vehicleId}', [DashboardController::class, 'mapShow']);
        });

        Route::middleware('role:admin')->prefix('admin/assets')->group(function () {
            Route::get('/', [AssetController::class, 'index']);
            Route::post('/', [AssetController::class, 'store']);
            Route::delete('/', [AssetController::class, 'destroyBulk']);
            Route::get('picker', [AssetController::class, 'picker']);
            Route::get('usage', [AssetController::class, 'usage']);
            Route::delete('{id}', [AssetController::class, 'destroy']);
        });

        Route::middleware('role:admin')->prefix('admin/landing')->group(function () {
            Route::get('/', [LandingController::class, 'adminIndex']);
            Route::post('slides', [LandingController::class, 'adminStoreSlide']);
            Route::put('{key}', [LandingController::class, 'adminUpdate']);
            Route::delete('{key}', [LandingController::class, 'adminDestroy']);
        });

        Route::middleware('role:admin')->prefix('admin/vehicles')->group(function () {
            Route::post('/', [VehicleController::class, 'adminStore']);
            Route::put('{id}', [VehicleController::class, 'adminUpdate']);
            Route::delete('{id}', [VehicleController::class, 'adminDestroy']);
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
