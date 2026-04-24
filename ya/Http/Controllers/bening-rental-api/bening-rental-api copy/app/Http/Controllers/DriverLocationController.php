<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User; // Pastikan model User menggunakan MongoDB Trait
use App\Models\Booking;

class DriverLocationController extends Controller
{
    /**
     * Menangani update lokasi dari aplikasi driver (Android).
     * Endpoint: POST /driver/location
     */
    public function update(Request $request)
    {
        // 1. Validasi input dari Retrofit (lat dan lon)
        $request->validate([
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
        ]);

        // 2. Ambil User (Driver) berdasarkan token
        // Karena ini Web Route, kita perlu mengambil token manual dari Header 
        // yang dikirim oleh AuthInterceptor di Android.
        $user = auth('sanctum')->user();

        if (!$user || $user->role !== 'driver') {
            return response()->json([
                'message' => 'Unauthorized. Hanya driver yang bisa mengirim lokasi.',
                'tracked' => false
            ], 401);
        }

        // 3. Update lokasi terkini di tabel/collection Users
        $user->update([
            'last_latitude'  => $request->lat,
            'last_longitude' => $request->lon,
            'last_location_update' => now(),
        ]);

        // 4. Logika "Tracked" 
        // Cek apakah driver sedang dalam pesanan aktif (ongoing)
        $isBusy = Booking::where('driver_id', $user->id)
            ->whereIn('status', ['confirmed', 'ongoing'])
            ->exists();

        // Log untuk memantau di terminal Laravel
        Log::info("Driver GPS Update: {$user->name} [{$request->lat}, {$request->lon}]");

        return response()->json([
            'message' => 'Lokasi diperbarui.',
            'tracked' => $isBusy // Jika true, Android akan tetap mengirim lokasi di background
        ]);
    }
}