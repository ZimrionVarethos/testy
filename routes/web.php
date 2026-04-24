<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\BookingController  as AdminBookingController;
use App\Http\Controllers\Admin\VehicleController  as AdminVehicleController;
use App\Http\Controllers\Admin\DriverController   as AdminDriverController;
use App\Http\Controllers\Admin\UserController     as AdminUserController;
use App\Http\Controllers\Admin\PaymentController  as AdminPaymentController;
use App\Http\Controllers\Admin\ReportController   as AdminReportController;
use App\Http\Controllers\Pengguna\BookingController  as PenggunaBookingController;
use App\Http\Controllers\Pengguna\VehicleController  as PenggunaVehicleController;
use App\Http\Controllers\Pengguna\PaymentController  as PenggunaPaymentController;
use App\Http\Controllers\Driver\BookingController    as DriverBookingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin\MapsController        as AdminMapsController;
use App\Http\Controllers\Admin\StorageController;
use App\Http\Controllers\Admin\LandingPageController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Pengguna\TicketController   as PenggunaTicketController;
use App\Http\Controllers\Admin\TicketController      as AdminTicketController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Pengguna\ChatController     as PenggunaChatController;
use App\Http\Controllers\Driver\ChatController       as DriverChatController;
use App\Http\Controllers\Pengguna\RatingController;

// ════════════════════════════════════════════════════════════
// WEBHOOK — di luar semua middleware, TANPA auth dan TANPA CSRF
// (Dikecualikan juga di VerifyCsrfToken::$except = ['payments/webhook'])
// ════════════════════════════════════════════════════════════
Route::post('/api/v1/payments/notification', [PenggunaPaymentController::class, 'webhook'])
    ->name('payments.webhook');

// ════════════════════════════════════════════════════════════
// WELCOME
// ════════════════════════════════════════════════════════════
Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

// ════════════════════════════════════════════════════════════
// AUTH
// ════════════════════════════════════════════════════════════
require __DIR__ . '/auth.php';

// ════════════════════════════════════════════════════════════
// DASHBOARD (semua role)
// ════════════════════════════════════════════════════════════
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// ════════════════════════════════════════════════════════════
// NOTIFIKASI (semua role)
// ════════════════════════════════════════════════════════════
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/notifications',              [NotificationController::class, 'index'])          ->name('notifications.index');
    Route::post('/notifications/{id}/read',   [NotificationController::class, 'markRead'])       ->name('notifications.read');
    Route::post('/notifications/read-all',    [NotificationController::class, 'markAllRead'])    ->name('notifications.read-all');
    Route::delete('/notifications/{id}',      [NotificationController::class, 'destroy'])        ->name('notifications.destroy');
    Route::delete('/notifications',           [NotificationController::class, 'destroyAll'])     ->name('notifications.destroy-all');
    Route::delete('/notifications/bulk',      [NotificationController::class, 'destroySelected'])->name('notifications.destroy-selected');
});

// ════════════════════════════════════════════════════════════
// PROFILE (semua role)
// ════════════════════════════════════════════════════════════
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile',    [ProfileController::class, 'edit'])   ->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update']) ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ════════════════════════════════════════════════════════════
// ADMIN
// ════════════════════════════════════════════════════════════
Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

    // ── Pesanan ──────────────────────────────────────────────
    Route::get('bookings',                     [AdminBookingController::class, 'index'])       ->name('bookings.index');
    Route::get('bookings/{id}',                [AdminBookingController::class, 'show'])        ->name('bookings.show');
    Route::post('bookings/{id}/assign-driver', [AdminBookingController::class, 'assignDriver'])->name('bookings.assign-driver');
    Route::post('bookings/{id}/cancel',        [AdminBookingController::class, 'cancel'])      ->name('bookings.cancel');
    // DIHAPUS: Route::post('bookings/{id}/confirm', ...) — confirm sekarang bagian dari assignDriver

    // ── Kendaraan ────────────────────────────────────────────
    Route::get('vehicles',           [AdminVehicleController::class, 'index']) ->name('vehicles.index');
    Route::get('vehicles/create',    [AdminVehicleController::class, 'create'])->name('vehicles.create');
    Route::post('vehicles',          [AdminVehicleController::class, 'store']) ->name('vehicles.store');
    Route::get('vehicles/{id}/edit', [AdminVehicleController::class, 'edit'])  ->name('vehicles.edit');
    Route::put('vehicles/{id}',      [AdminVehicleController::class, 'update'])->name('vehicles.update');
    Route::delete('vehicles/{id}',   [AdminVehicleController::class, 'destroy'])->name('vehicles.destroy');

    // ── Driver ───────────────────────────────────────────────
    Route::get('drivers',              [AdminDriverController::class, 'index'])  ->name('drivers.index');
    Route::get('drivers/{id}',         [AdminDriverController::class, 'show'])   ->name('drivers.show');
    Route::post('drivers/{id}/toggle', [AdminDriverController::class, 'toggle']) ->name('drivers.toggle');

    // ── Pengguna ─────────────────────────────────────────────
    Route::get('users',              [AdminUserController::class, 'index'])  ->name('users.index');
    Route::get('users/{id}',         [AdminUserController::class, 'show'])   ->name('users.show');
    Route::post('users/{id}/toggle', [AdminUserController::class, 'toggle'])->name('users.toggle');

    // ── Pembayaran ───────────────────────────────────────────
    Route::get('payments',      [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/{id}', [AdminPaymentController::class, 'show']) ->name('payments.show');

    // ── Laporan & Statistik ──────────────────────────────────
    Route::get('/reports',              [ReportController::class, 'index'])    ->name('reports.index');
    Route::get('/reports/export',       [ReportController::class, 'export'])   ->name('reports.export');
    Route::post('/reports/export-old',  [ReportController::class, 'exportOld'])->name('reports.export-old');
    Route::delete('/reports/delete-old',[ReportController::class, 'deleteOld'])->name('reports.delete-old');

    // ── Peta ─────────────────────────────────────────────────
    Route::get('maps', [AdminMapsController::class, 'index'])->name('maps.index');

    // ── Landing Page ─────────────────────────────────────────
    Route::get('landing',                      [LandingPageController::class, 'index'])      ->name('landing.index');
    Route::put('landing',                      [LandingPageController::class, 'update'])     ->name('landing.update');
    Route::get('landing/slides/{key}/destroy', [LandingPageController::class, 'destroySlide'])->name('landing.slides.destroy');
    Route::get('landing/{key}/destroy',        [LandingPageController::class, 'destroy'])    ->name('landing.destroy');

    // ── Tiket Bantuan ────────────────────────────────────────
    Route::get('tickets',              [AdminTicketController::class, 'index'])       ->name('tickets.index');
    Route::get('tickets/{id}',         [AdminTicketController::class, 'show'])        ->name('tickets.show');
    Route::post('tickets/{id}/reply',  [AdminTicketController::class, 'reply'])       ->name('tickets.reply');
    Route::post('tickets/{id}/status', [AdminTicketController::class, 'updateStatus'])->name('tickets.status');

    // ── Storage (MongoDB debug) ───────────────────────────────
    Route::get('/storage',                      [StorageController::class, 'index'])            ->name('storage.index');
    Route::get('/storage/{collection}',         [StorageController::class, 'show'])             ->name('storage.show');
    Route::delete('/storage/{collection}',      [StorageController::class, 'destroyCollection'])->name('storage.destroyCollection');
    Route::delete('/storage/{collection}/{id}', [StorageController::class, 'destroyDocument'])  ->name('storage.destroyDocument');
});

// ════════════════════════════════════════════════════════════
// PENGGUNA
// ════════════════════════════════════════════════════════════
Route::middleware(['auth', 'verified', 'role:pengguna,user'])
    ->group(function () {

    // ── Kendaraan & Pemesanan ────────────────────────────────
    Route::get('vehicles',            [PenggunaVehicleController::class, 'index'])       ->name('vehicles.index');
    Route::get('vehicles/{id}',       [PenggunaVehicleController::class, 'show'])        ->name('vehicles.show');
    Route::get('vehicles/{id}/book',  [PenggunaVehicleController::class, 'book'])        ->name('vehicles.book');
    Route::post('vehicles/{id}/book', [PenggunaVehicleController::class, 'storeBooking'])->name('vehicles.store-booking');

    // ── Booking ──────────────────────────────────────────────
    Route::get('bookings',         [PenggunaBookingController::class, 'index'])  ->name('bookings.index');
    Route::get('bookings/{id}',    [PenggunaBookingController::class, 'show'])   ->name('bookings.show');
    Route::delete('bookings/{id}', [PenggunaBookingController::class, 'destroy'])->name('bookings.destroy');

    // ── Payment ──────────────────────────────────────────────
    Route::get('payments',              [PenggunaPaymentController::class, 'index'])    ->name('payments.index');
    Route::get('payments/{id}',         [PenggunaPaymentController::class, 'show'])     ->name('payments.show');
    Route::get('bookings/{id}/pay',     [PenggunaPaymentController::class, 'createSnap'])->name('bookings.pay');
    Route::get('payments/{id}/finish',  [PenggunaPaymentController::class, 'finish'])   ->name('payments.finish');

    // ── Tiket Bantuan ────────────────────────────────────────
    Route::get('tickets',                    [PenggunaTicketController::class, 'index'])  ->name('tickets.index');
    Route::get('tickets/create/{bookingId}', [PenggunaTicketController::class, 'create'])->name('tickets.create');
    Route::post('tickets',                   [PenggunaTicketController::class, 'store'])  ->name('tickets.store');
    Route::get('tickets/{id}',               [PenggunaTicketController::class, 'show'])   ->name('tickets.show');
    Route::post('tickets/{id}/reply',        [PenggunaTicketController::class, 'reply'])  ->name('tickets.reply');

    // ── Chat ─────────────────────────────────────────────────
    Route::get('chats',                      [PenggunaChatController::class, 'index'])->name('pengguna.chats.index');
    Route::get('bookings/{id}/messages',     [ChatController::class, 'index'])        ->name('chat.index');
    Route::post('bookings/{id}/messages',    [ChatController::class, 'store'])        ->name('chat.store');

    // ── Rating ───────────────────────────────────────────────
    Route::post('bookings/{bookingId}/rating', [RatingController::class, 'store'])->name('bookings.rating.store');
});

// ════════════════════════════════════════════════════════════
// DRIVER
// ════════════════════════════════════════════════════════════
Route::middleware(['auth', 'verified', 'role:driver'])
    ->prefix('driver')
    ->name('driver.')
    ->group(function () {

    // ── Booking ──────────────────────────────────────────────
    Route::get('bookings',      [DriverBookingController::class, 'index'])->name('bookings.index');
    Route::get('bookings/{id}', [DriverBookingController::class, 'show']) ->name('bookings.show');

    // Tombol "Sudah Jemput" — trigger confirmed → ongoing
    Route::post('bookings/{id}/pickup', [DriverBookingController::class, 'markPickup'])->name('bookings.pickup');

    // Toggle availability manual (opsional, bisa dipertahankan untuk keperluan admin lihat)
    Route::post('toggle-availability', [DriverBookingController::class, 'toggleAvailability'])->name('toggle-availability');

    // ── Chat ─────────────────────────────────────────────────
    Route::get('chats',                   [DriverChatController::class, 'index'])  ->name('chats.index');
    Route::get('bookings/{id}/messages',  [ChatController::class, 'driverIndex']) ->name('chat.index');
    Route::post('bookings/{id}/messages', [ChatController::class, 'driverStore']) ->name('chat.store');
});

// ════════════════════════════════════════════════════════════
// DRIVER LOCATION — API (Mobile)
// ════════════════════════════════════════════════════════════
Route::middleware(['auth', 'role:driver'])
    ->post('/driver/location', [App\Http\Controllers\Api\DriverController::class, 'updateLocation']);

// ════════════════════════════════════════════════════════════
// DEBUG — HAPUS setelah selesai development
// ════════════════════════════════════════════════════════════
Route::get('/debug-midtrans', function () {
    $serverKey = config('midtrans.server_key');
    $clientKey = config('midtrans.client_key');
    $isProd    = config('midtrans.is_production');
    return response()->json([
        'server_key_length' => strlen($serverKey),
        'server_key_prefix' => substr($serverKey, 0, 15),
        'client_key_prefix' => substr($clientKey, 0, 15),
        'is_production'     => $isProd,
        'env_direct'        => substr(env('MIDTRANS_SERVER_KEY', 'NOT_FOUND'), 0, 15),
    ]);
});