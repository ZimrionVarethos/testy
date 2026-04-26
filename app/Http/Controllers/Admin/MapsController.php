<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;

class MapsController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::all();

        // ── Hanya tampilkan booking yang SEDANG BERLANGSUNG ──────────
        // Syarat: status ongoing/confirmed DAN start_date sudah tiba DAN end_date belum lewat
        // Booking confirmed yang start_date-nya belum tiba tidak ditampilkan di peta
        $now = Carbon::now();

        $activeBookings = Booking::whereIn('status', ['confirmed', 'ongoing'])
            ->where('start_date', '<=', $now)   // ← start_date sudah tiba
            ->where('end_date', '>', $now)       // ← end_date belum lewat
            ->get()
            ->keyBy(fn($b) => (string) ($b->vehicle['vehicle_id'] ?? ''));

        $mappedVehicles = $vehicles->map(function ($v) use ($activeBookings) {
            $vid   = (string) $v->_id;
            $name  = trim($v->name  ?? '');
            $brand = trim($v->brand ?? '');
            $model = trim($v->model ?? '');
            $label = $name ?: ($brand && $model ? "$brand $model" : ($brand ?: $model));

            $booking    = $activeBookings->get($vid);
            $driverName = $booking?->driver['name'] ?? '-';

            $lat               = null;
            $lon               = null;
            $locationUpdatedAt = null;
            $isStale           = false;

            // Hanya ambil koordinat kalau booking aktif (start_date sudah tiba)
            if ($booking && !empty($booking->driver['driver_id'])) {
                $driver            = User::find($booking->driver['driver_id']);
                $lat               = $driver?->last_lat ?? null;
                $lon               = $driver?->last_lon ?? null;
                $locationUpdatedAt = $driver?->last_location_updated_at ?? null;

                if ($locationUpdatedAt) {
                    $isStale = now()->diffInMinutes($locationUpdatedAt) > 5;
                }
            }

            return [
                'id'                     => $vid,
                'plate'                  => $v->plate_number ?? '-',
                'label'                  => $label ?: '-',
                'driver'                 => $driverName,
                'status'                 => $v->status ?? 'available',
                'lat'                    => $lat,
                'lon'                    => $lon,
                'has_active_booking'     => $booking !== null,
                'location_updated_at'    => $locationUpdatedAt
                    ? Carbon::parse($locationUpdatedAt)->format('H:i, d M')
                    : null,
                'location_updated_human' => $locationUpdatedAt
                    ? Carbon::parse($locationUpdatedAt)->diffForHumans()
                    : null,
                'is_stale'               => $isStale,
            ];
        });

        $stats = [
            'total'       => $mappedVehicles->count(),
            'ongoing'     => $mappedVehicles->where('status', 'rented')->count(),
            'available'   => $mappedVehicles->where('status', 'available')->count(),
            'maintenance' => $mappedVehicles->where('status', 'maintenance')->count(),
        ];

        return view('admin.maps.index', [
            'vehicles' => $mappedVehicles->values(),
            'stats'    => $stats,
        ]);
    }

    public function show(string $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $now     = Carbon::now();

        // Sama — hanya booking yang sedang berlangsung
        $activeBooking = Booking::whereIn('status', ['confirmed', 'ongoing'])
            ->where('vehicle.vehicle_id', $id)
            ->where('start_date', '<=', $now)   // ← start_date sudah tiba
            ->where('end_date', '>', $now)       // ← end_date belum lewat
            ->latest('created_at')
            ->first();

        $lat               = null;
        $lon               = null;
        $locationUpdatedAt = null;
        $isStale           = false;

        if ($activeBooking && !empty($activeBooking->driver['driver_id'])) {
            $driver            = User::find($activeBooking->driver['driver_id']);
            $lat               = $driver?->last_lat ?? null;
            $lon               = $driver?->last_lon ?? null;
            $locationUpdatedAt = $driver?->last_location_updated_at ?? null;

            if ($locationUpdatedAt) {
                $isStale = now()->diffInMinutes($locationUpdatedAt) > 5;
            }
        }

        $vehicle->last_lat                 = $lat;
        $vehicle->last_lon                 = $lon;
        $vehicle->last_location_updated_at = $locationUpdatedAt;
        $vehicle->is_stale                 = $isStale;

        if ($activeBooking?->driver) {
            $vehicle->driver = $activeBooking->driver;
        }

        return view('admin.maps.show', compact('vehicle', 'activeBooking'));
    }
}