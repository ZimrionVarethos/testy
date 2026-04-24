<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DriverLocationController;

// Web route tanpa awalan /api/
Route::post('/driver/location', [DriverLocationController::class, 'update']);
