<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Vehicle;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $role = $user->role;

        return match($role) {
            'admin'             => $this->adminDashboard(),
            'pengguna', 'user'  => $this->penggunaDashboard(),
            'driver'            => $this->driverDashboard(),
            default             => view('dashboard'),
        };
    }

    // ── ADMIN ────────────────────────────────────────────────
    private function adminDashboard()
    {
        $stats = [
            'total_bookings'   => Booking::count(),
            'pending_bookings' => Booking::whereIn('status', ['pending', 'accepted'])->count(),
            'ongoing_bookings' => Booking::where('status', 'ongoing')->count(),
            // 'monthly_revenue'  => \App\Models\Payment::where('status', 'paid')
            //                         ->where('created_at', '>=', now()->startOfMonth())   tunggu midtrans dulu
            //                         ->sum('amount'),
            'monthly_revenue'  => Booking::whereIn('status', ['confirmed', 'ongoing', 'completed'])
                        ->where('confirmed_at', '>=', now()->startOfMonth())
                        ->sum('total_price'),            
        ];

        $vehicleStats = [
            'available'   => Vehicle::where('status', 'available')->count(),
            'rented'      => Vehicle::where('status', 'rented')->count(),
            'maintenance' => Vehicle::where('status', 'maintenance')->count(),
        ];

        $recentBookings = Booking::orderBy('created_at', 'desc')->limit(5)->get();

        // Pesanan yang sudah diambil driver, menunggu konfirmasi admin
        $acceptedBookings = Booking::where('status', 'accepted')
                                   ->orderBy('accepted_at', 'asc')
                                   ->get();

        return view('dashboard', compact('stats', 'vehicleStats', 'recentBookings', 'acceptedBookings'));
    }

    // ── PENGGUNA ─────────────────────────────────────────────
    private function penggunaDashboard()
    {
        $userId = (string) Auth::id();

        $stats = [
            'total'     => Booking::where('user.user_id', $userId)->count(),
            'ongoing'   => Booking::where('user.user_id', $userId)->where('status', 'ongoing')->count(),
            'completed' => Booking::where('user.user_id', $userId)->where('status', 'completed')->count(),
        ];

        $activeBookings = Booking::where('user.user_id', $userId)
            ->whereIn('status', ['pending', 'accepted', 'confirmed', 'ongoing'])
            ->orderBy('created_at', 'desc')
            ->get();

        $notifications = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard', compact('stats', 'activeBookings', 'notifications'));
    }

    // ── DRIVER ───────────────────────────────────────────────
    private function driverDashboard()
    {
        $driver   = Auth::user();
        $driverId = (string) $driver->_id;

        $stats = [
            'total_trips'      => $driver->driver_profile['total_trips'] ?? 0,
            'ongoing'          => Booking::where('driver.driver_id', $driverId)->where('status', 'ongoing')->count(),
            'pending_available'=> Booking::where('status', 'pending')->count(),
            'rating_avg'       => $driver->driver_profile['rating_avg'] ?? 0,
        ];

        $myActiveBookings = Booking::where('driver.driver_id', $driverId)
            ->whereIn('status', ['accepted', 'confirmed', 'ongoing'])
            ->orderBy('start_date', 'asc')
            ->get();

        $availableBookings = Booking::where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->limit(5)
            ->get();

        return view('dashboard', compact('stats', 'myActiveBookings', 'availableBookings'));
    }
}