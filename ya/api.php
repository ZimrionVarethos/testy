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

/*
|--------------------------------------------------------------------------
| API Routes — Bening Rental
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ── Auth (Public) ────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('register',        [AuthController::class, 'register']);
        Route::post('login',           [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    });

    // ── Midtrans Webhook (Public — diverifikasi via signature key) ───────
    // PENTING: route ini harus dikecualikan dari CSRF di App\Http\Middleware\VerifyCsrfToken
    // atau pakai api middleware (sudah stateless di Laravel 11)
    Route::post('payments/notification', [PaymentController::class, 'notification'])
         ->name('api.payments.notification');

    // ── Protected Routes ─────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me',      [AuthController::class, 'me']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
        });

        // Vehicles (Customer: read-only)
        Route::prefix('vehicles')->group(function () {
            Route::get('/',       [VehicleController::class, 'index']);
            Route::get('{id}',    [VehicleController::class, 'show']);

            Route::middleware('role:admin')->group(function () {
                Route::post('/',      [VehicleController::class, 'store']);
                Route::put('{id}',    [VehicleController::class, 'update']);
                Route::delete('{id}', [VehicleController::class, 'destroy']);
            });
        });

        // Bookings
        Route::prefix('bookings')->group(function () {
            Route::get('/',              [BookingController::class, 'index']);
            Route::post('/',             [BookingController::class, 'store']);
            Route::get('{id}',           [BookingController::class, 'show']);
            Route::post('{id}/cancel',   [BookingController::class, 'cancel']);

            Route::middleware('role:admin')->group(function () {
                Route::post('{id}/confirm', [BookingController::class, 'confirm']);
            });

            Route::middleware('role:driver')->group(function () {
                Route::post('{id}/accept', [BookingController::class, 'accept']);
            });
        });

        // Drivers
        // routes/api.php — di dalam middleware auth:sanctum, bagian drivers
        Route::prefix('drivers')->group(function () {
            Route::get('/',       [DriverController::class, 'index']);
            Route::get('{id}',    [DriverController::class, 'show']);

            // TAMBAHKAN INI — update lokasi GPS driver
            Route::middleware('role:driver')
                 ->post('location', [DriverController::class, 'updateLocation']);

            Route::middleware('role:admin')->group(function () {
                Route::post('{id}/toggle', [DriverController::class, 'toggle']);
            });
        });

        // Payments — Admin: list semua; User: list milik sendiri
        Route::prefix('payments')->group(function () {
            Route::middleware('role:admin')->group(function () {
                Route::get('/', [PaymentController::class, 'index']);
            });
        });

        // Dashboard (Admin only)
        Route::middleware('role:admin')->group(function () {
            Route::get('dashboard', [DashboardController::class, 'index']);
            Route::get('reports',   [DashboardController::class, 'reports']);
        });

        // Users (Admin only)
        Route::middleware('role:admin')->prefix('users')->group(function () {
            Route::get('/',            [UserController::class, 'index']);
            Route::get('{id}',         [UserController::class, 'show']);
            Route::post('{id}/toggle', [UserController::class, 'toggle']);
        });

        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/',              [NotificationController::class, 'index']);
            Route::post('read-all',      [NotificationController::class, 'readAll']);
            Route::post('{id}/read',     [NotificationController::class, 'markRead']);
        });

    });

    // ── Debug (hapus di production) ──────────────────────────────────────
    Route::get('/debug', function () {
        return [
            'mongodb_extension' => extension_loaded('mongodb'),
            'db_connection'     => env('DB_CONNECTION'),
            'db_uri_set'        => !empty(env('DB_URI')),
        ];
    });

    Route::get('debug-sanctum-full', function () {
        return [
            'token_model'        => config('sanctum.personal_access_token_model'),
            'db_default'         => config('database.default'),
            'mongodb_connection' => config('database.connections.mongodb.driver'),
        ];
    });

    Route::get('debug-token-test', function () {
        $user = App\Models\User::first();
        if (!$user) return ['error' => 'no user'];

        try {
            $token = $user->createToken('test');
            return [
                'success'     => true,
                'token_class' => get_class($token->accessToken),
                'token_id'    => $token->accessToken->getKey(),
            ];
        } catch (\Throwable $e) {
            return [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => collect($e->getTrace())->take(5)
                               ->map(fn($t) => ($t['file'] ?? '') . ' :' . $t['line'])
                               ->toArray(),
            ];
        }
    });


});