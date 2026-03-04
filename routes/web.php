<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\VehicleController as AdminVehicleController;
use App\Http\Controllers\Admin\DriverController as AdminDriverController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Pengguna\BookingController as PenggunaBookingController;
use App\Http\Controllers\Pengguna\VehicleController as PenggunaVehicleController;
use App\Http\Controllers\Pengguna\PaymentController as PenggunaPaymentController;
use App\Http\Controllers\Driver\BookingController as DriverBookingController;
use App\Http\Controllers\NotificationController;

Route::get('/', function () {
    return view('welcome');
});

// ── AUTH ROUTES (Breeze/Jetstream auto-generate) ────────────
require __DIR__.'/auth.php';

// ── DASHBOARD (semua role, controller yang bedain) ──────────
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// ── NOTIFIKASI (pengguna & driver) ──────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/notifications',          [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

// ── ADMIN ROUTES ────────────────────────────────────────────
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    // Pesanan
    Route::get('bookings',                   [AdminBookingController::class, 'index'])  ->name('bookings.index');
    Route::get('bookings/{id}',              [AdminBookingController::class, 'show'])   ->name('bookings.show');
    Route::post('bookings/{id}/confirm',     [AdminBookingController::class, 'confirm'])->name('bookings.confirm');
    Route::post('bookings/{id}/cancel',      [AdminBookingController::class, 'cancel']) ->name('bookings.cancel');

    // Kendaraan
    Route::get('vehicles',                   [AdminVehicleController::class, 'index']) ->name('vehicles.index');
    Route::get('vehicles/create',            [AdminVehicleController::class, 'create'])->name('vehicles.create');
    Route::post('vehicles',                  [AdminVehicleController::class, 'store']) ->name('vehicles.store');
    Route::get('vehicles/{id}/edit',         [AdminVehicleController::class, 'edit'])  ->name('vehicles.edit');
    Route::put('vehicles/{id}',              [AdminVehicleController::class, 'update'])->name('vehicles.update');
    Route::delete('vehicles/{id}',           [AdminVehicleController::class, 'destroy'])->name('vehicles.destroy');

    // Driver
    Route::get('drivers',                    [AdminDriverController::class, 'index'])  ->name('drivers.index');
    Route::get('drivers/{id}',               [AdminDriverController::class, 'show'])   ->name('drivers.show');
    Route::post('drivers/{id}/toggle',       [AdminDriverController::class, 'toggle']) ->name('drivers.toggle');

    // Pengguna
    Route::get('users',                      [AdminUserController::class, 'index'])    ->name('users.index');
    Route::get('users/{id}',                 [AdminUserController::class, 'show'])     ->name('users.show');
    Route::post('users/{id}/toggle',         [AdminUserController::class, 'toggle'])   ->name('users.toggle');

    // Pembayaran
    Route::get('payments',                   [AdminPaymentController::class, 'index']) ->name('payments.index');
    Route::get('payments/{id}',              [AdminPaymentController::class, 'show'])  ->name('payments.show');

    // Laporan
    Route::get('reports',                    [AdminReportController::class, 'index'])  ->name('reports.index');
});

// ── PENGGUNA ROUTES ─────────────────────────────────────────
Route::middleware(['auth', 'verified', 'role:pengguna,user'])->group(function () {

    // Pesanan
    Route::get('bookings',                   [PenggunaBookingController::class, 'index'])  ->name('bookings.index');
    Route::get('bookings/{id}',              [PenggunaBookingController::class, 'show'])   ->name('bookings.show');
    Route::delete('bookings/{id}',           [PenggunaBookingController::class, 'destroy'])->name('bookings.destroy');

    // Kendaraan (browse & booking)
    Route::get('vehicles',                   [PenggunaVehicleController::class, 'index']) ->name('vehicles.index');
    Route::get('vehicles/{id}',              [PenggunaVehicleController::class, 'show'])  ->name('vehicles.show');
    Route::get('vehicles/{id}/book',         [PenggunaVehicleController::class, 'book'])  ->name('vehicles.book');
    Route::post('vehicles/{id}/book',        [PenggunaVehicleController::class, 'storeBooking'])->name('vehicles.store-booking');

    // Pembayaran
    Route::get('payments',                   [PenggunaPaymentController::class, 'index']) ->name('payments.index');
    Route::get('payments/{id}',              [PenggunaPaymentController::class, 'show'])  ->name('payments.show');
});

// ── DRIVER ROUTES ────────────────────────────────────────────
Route::middleware(['auth', 'verified', 'role:driver'])->prefix('driver')->name('driver.')->group(function () {

    Route::get('bookings/available',         [DriverBookingController::class, 'available'])  ->name('bookings.available');
    Route::get('bookings',                   [DriverBookingController::class, 'index'])      ->name('bookings.index');
    Route::get('bookings/{id}',              [DriverBookingController::class, 'show'])       ->name('bookings.show');
    Route::post('bookings/{id}/accept',      [DriverBookingController::class, 'accept'])     ->name('bookings.accept');
    Route::post('toggle-availability',       [DriverBookingController::class, 'toggleAvailability'])->name('toggle-availability');
});


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile',  [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',[ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile',[ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
