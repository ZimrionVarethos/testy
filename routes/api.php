<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes — Bening Rental
|--------------------------------------------------------------------------
|
| Semua route API untuk aplikasi mobile Bening Rental.
| Base URL: /api/v1
|
*/

Route::prefix('v1')->group(function () {

    // ── Auth (Public) ────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login',    [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    });

    // ── Protected Routes ─────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('logout',  [AuthController::class, 'logout']);
            Route::get('me',       [AuthController::class, 'me']);
            Route::put('profile',  [AuthController::class, 'updateProfile']);
        });

        // Vehicles (Customer: read-only)
        Route::prefix('vehicles')->group(function () {
            Route::get('/',          [VehicleController::class, 'index']);
            Route::get('{id}',       [VehicleController::class, 'show']);

            // Admin only
            Route::middleware('role:admin')->group(function () {
                Route::post('/',         [VehicleController::class, 'store']);
                Route::put('{id}',       [VehicleController::class, 'update']);
                Route::delete('{id}',    [VehicleController::class, 'destroy']);
            });
        });

        // Route::post('/driver/location', [App\Http\Controllers\Api\DriverController::class, 'updateLocation']);
    

        // Bookings
        Route::prefix('bookings')->group(function () {
            Route::get('/',          [BookingController::class, 'index']);
            Route::post('/',         [BookingController::class, 'store']);
            Route::get('{id}',       [BookingController::class, 'show']);
            Route::post('{id}/cancel', [BookingController::class, 'cancel']);

            // Admin only
            Route::middleware('role:admin')->group(function () {
                Route::post('{id}/confirm',  [BookingController::class, 'confirm']);
            });

            // Driver
            Route::middleware('role:driver')->group(function () {
                Route::post('{id}/accept', [BookingController::class, 'accept']);
            });
        });

        // Drivers (Admin: full, Customer: read)
        Route::prefix('drivers')->group(function () {
            Route::get('/',       [DriverController::class, 'index']);
            Route::get('{id}',    [DriverController::class, 'show']);

            Route::middleware('role:admin')->group(function () {
                Route::post('{id}/toggle', [DriverController::class, 'toggle']);
            });
        });

        // Payments (Admin only)
        Route::middleware('role:admin')->group(function () {
            Route::get('payments', [PaymentController::class, 'index']);
        });

        // Dashboard (Admin only)
        Route::middleware('role:admin')->group(function () {
            Route::get('dashboard', [DashboardController::class, 'index']);
            Route::get('reports',   [DashboardController::class, 'reports']);
        });

        // Users (Admin only)
        Route::middleware('role:admin')->prefix('users')->group(function () {
            Route::get('/',           [UserController::class, 'index']);
            Route::get('{id}',        [UserController::class, 'show']);
            Route::post('{id}/toggle', [UserController::class, 'toggle']);
        });
    });

    Route::get('/debug', function () {
    return [
        'mongodb_extension' => extension_loaded('mongodb'),
        'db_connection'     => env('DB_CONNECTION'),
        'db_uri_set'        => !empty(env('DB_URI')),
    ];
});

    Route::middleware('auth:sanctum,web')->post('/driver/location', [
    App\Http\Controllers\Api\DriverController::class, 'updateLocation'
    ]);

    // routes/api.php - sementara
    Route::get('debug-sanctum-full', function() {
        return [
            'token_model' => config('sanctum.personal_access_token_model'),
            'db_default' => config('database.default'),
            'mongodb_connection' => config('database.connections.mongodb.driver'),
        ];
    });

        // routes/api.php - sementara
    Route::get('debug-token-test', function() {
        $user = App\Models\User::first();
        if (!$user) return ['error' => 'no user'];
        
        try {
            $token = $user->createToken('test');
            return [
                'success' => true,
                'token_class' => get_class($token->accessToken),
                'token_id' => $token->accessToken->getKey(),
            ];
        } catch (\Throwable $e) {
            return [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => collect($e->getTrace())->take(5)->map(fn($t) => ($t['file'] ?? '').' :'.$t['line'])->toArray(),
            ];
        }
    });


   
});
