<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\Booking;
use App\Models\User;

class MapsController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::all();

        $activeBookings = Booking::whereIn('status', ['confirmed', 'ongoing'])
            ->get()
            ->keyBy(fn($b) => (string) ($b->vehicle['vehicle_id'] ?? ''));

        $driverIds = $activeBookings
            ->map(fn($b) => $b->driver['driver_id'] ?? null)
            ->filter()
            ->values()
            ->all();

        $drivers = User::whereIn('_id', $driverIds)
            ->get()
            ->keyBy(fn($d) => (string) $d->_id);

        $mappedVehicles = $vehicles->map(function ($v) use ($activeBookings, $drivers) {
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

            if ($booking && !empty($booking->driver['driver_id'])) {
                $driver            = $drivers->get((string) $booking->driver['driver_id']);
                $lat               = $driver?->last_lat ?? null;
                $lon               = $driver?->last_lon ?? null;
                $locationUpdatedAt = $driver?->last_location_updated_at ?? null;

                if ($locationUpdatedAt) {
                    $isStale = now()->diffInMinutes($locationUpdatedAt) > 5;
                }
            }

            return [
                'id'                  => $vid,
                'plate'               => $v->plate_number ?? '-',
                'label'               => $label ?: '-',
                'driver'              => $driverName,
                'status'              => $v->status ?? 'available',
                'lat'                 => $lat,
                'lon'                 => $lon,
                'has_active_booking'  => $booking !== null,
                'location_updated_at' => $locationUpdatedAt
                    ? \Carbon\Carbon::parse($locationUpdatedAt)->diffForHumans()
                    : null,
                'is_stale'            => $isStale,
            ];
        });

        $stats = [
            'total'       => $mappedVehicles->count(),
            'ongoing' => $mappedVehicles->where('status', 'rented')->count(),
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

        $activeBooking = Booking::whereIn('status', ['confirmed', 'ongoing'])
            ->where('vehicle.vehicle_id', $id)
            ->latest('created_at')
            ->first();

        $lat               = null;
        $lon               = null;
        $locationUpdatedAt = null;
        $isStale           = false;

        if ($activeBooking && !empty($activeBooking->driver['driver_id'])) {
            $driver = User::find(
                $activeBooking->driver['driver_id'],
                ['last_lat', 'last_lon', 'last_location_updated_at']
            );
            $lat               = $driver?->last_lat ?? null;
            $lon               = $driver?->last_lon ?? null;
            $locationUpdatedAt = $driver?->last_location_updated_at ?? null;

            if ($locationUpdatedAt) {
                $isStale = now()->diffInMinutes($locationUpdatedAt) > 5;
            }
        }

        // Inject lokasi driver ke vehicle (tidak disimpan ke DB)
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