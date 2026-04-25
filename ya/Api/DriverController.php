<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverController extends Controller
{
    /**
     * GET /api/v1/drivers — daftar semua driver (Admin only)
     */
    public function index(Request $request): JsonResponse
    {
        $drivers = User::where('role', 'driver')
            ->orderBy('created_at', 'desc')
            ->paginate((int) $request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $drivers->map(fn($d) => $this->driverResource($d)),
            'meta'    => [
                'current_page' => $drivers->currentPage(),
                'last_page'    => $drivers->lastPage(),
                'total'        => $drivers->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/drivers/{id} — detail driver beserta riwayat booking
     */
    public function show(string $id): JsonResponse
    {
        $driver = User::where('role', 'driver')->findOrFail($id);

        // 🔧 FIX: pakai nested field driver.driver_id (konsisten dengan web Admin/DriverController)
        $bookings = Booking::where('driver.driver_id', (string) $driver->_id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => array_merge($this->driverResource($driver), [
                'recent_bookings' => $bookings->map(fn($b) => [
                    'id'           => (string) $b->_id,
                    'booking_code' => $b->booking_code,
                    'status'       => $b->status,
                    'total_price'  => $b->total_price,
                    'start_date'   => $b->start_date,
                    'end_date'     => $b->end_date,
                    'user_name'    => $b->user['name'] ?? '-',
                    'vehicle_name' => $b->vehicle['name'] ?? '-',
                ]),
            ]),
        ]);
    }

    /**
     * POST /api/v1/drivers/{id}/toggle — aktifkan / nonaktifkan (Admin)
     */
    public function toggle(string $id): JsonResponse
    {
        $driver = User::where('role', 'driver')->findOrFail($id);
        $driver->update(['is_active' => ! $driver->is_active]);

        return response()->json([
            'success' => true,
            'message' => $driver->is_active ? 'Driver diaktifkan.' : 'Driver dinonaktifkan.',
            'data'    => $this->driverResource($driver->fresh()),
        ]);
    }

    /**
     * POST /api/v1/driver/location — update lokasi GPS driver
     * Hanya simpan jika driver punya booking aktif.
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lon' => 'required|numeric|between:-180,180',
        ]);

        $driver = Auth::user();

        if (! $driver || $driver->role !== 'driver') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // 🔧 FIX: pakai driver.driver_id (konsisten dengan web MapsController)
        $hasActiveBooking = Booking::where('driver.driver_id', (string) $driver->_id)
            ->whereIn('status', ['confirmed', 'ongoing'])
            ->exists();

        if (! $hasActiveBooking) {
            return response()->json([
                'message' => 'Tidak ada pesanan aktif, lokasi tidak disimpan.',
                'tracked' => false,
            ]);
        }

        $driver->update([
            'last_lat'                 => (float) $request->lat,
            'last_lon'                 => (float) $request->lon,
            'last_location_updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Lokasi diperbarui.',
            'tracked' => true,
        ]);
    }

    // ── Helper ────────────────────────────────────────────────────

    private function driverResource(User $d): array
    {
        $dp = $d->driver_profile ?? [];

        return [
            'id'             => (string) $d->_id,
            'name'           => $d->name,
            'email'          => $d->email,
            'phone'          => $d->phone,
            'is_active'      => $d->is_active,
            'driver_profile' => [
                'license_number' => $dp['license_number'] ?? null,
                'license_expiry' => $dp['license_expiry'] ?? null,
                'is_available'   => $dp['is_available'] ?? false,
                'rating_avg'     => $dp['rating_avg'] ?? 0,
                'total_trips'    => $dp['total_trips'] ?? 0,
            ],
            'last_lat'                 => $d->last_lat ?? null,
            'last_lon'                 => $d->last_lon ?? null,
            'last_location_updated_at' => $d->last_location_updated_at ?? null,
            'created_at'               => $d->created_at?->toIso8601String(),
        ];
    }
}
