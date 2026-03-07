<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\Booking;

class MapsController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::all();

        // Index booking aktif by vehicle.vehicle_id (embedded snapshot)
        $activeBookings = Booking::whereIn('status', ['confirmed', 'ongoing'])
            ->get()
            ->keyBy(fn($b) => (string) ($b->vehicle['vehicle_id'] ?? ''));

        $mappedVehicles = $vehicles->map(function ($v) use ($activeBookings) {
            $vid = (string) $v->_id;

            // Nama: field 'name' di Vehicle sudah lengkap, fallback ke brand+model
            $name  = trim($v->name  ?? '');
            $brand = trim($v->brand ?? '');
            $model = trim($v->model ?? '');
            $label = $name ?: ($brand && $model ? "$brand $model" : ($brand ?: $model));

            // Driver dari booking aktif (embedded snapshot di booking)
            $booking    = $activeBookings->get($vid);
            $driverName = $booking?->driver['name'] ?? '-';

            return [
                'id'       => $vid,
                'plate'    => $v->plate_number ?? '-',
                'label'    => $label ?: '-',
                'driver'   => $driverName,
                'status'   => $v->status ?? 'available',
                'lat'      => $v->last_lat ?? null,
                'lon'      => $v->last_lon ?? null,
            ];
        });

        $stats = [
            'total'       => $mappedVehicles->count(),
            'ongoing'     => $mappedVehicles->where('status', 'ongoing')->count(),
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

        return view('admin.maps.show', compact('vehicle', 'activeBooking'));
    }
}