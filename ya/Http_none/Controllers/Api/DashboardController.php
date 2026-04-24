<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Statistik ringkas untuk dashboard admin.
     */
    public function index(): JsonResponse
    {
        $now = Carbon::now();

        $stats = [
            'total_bookings'   => Booking::count(),
            'pending_bookings' => Booking::where('status', 'pending')->count(),
            'ongoing_bookings' => Booking::where('status', 'ongoing')->count(),
            'monthly_revenue'  => Booking::whereIn('status', ['confirmed', 'ongoing', 'completed'])
                ->whereMonth('confirmed_at', $now->month)
                ->whereYear('confirmed_at', $now->year)
                ->sum('total_price'),
        ];

        $vehicleStats = [
            'available'   => Vehicle::where('status', 'available')->count(),
            'rented'      => Vehicle::where('status', 'rented')->count(),
            'maintenance' => Vehicle::where('status', 'maintenance')->count(),
        ];

        $recentBookings = Booking::orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($b) => [
                'id'           => (string) $b->_id,
                'booking_code' => $b->booking_code,
                'status'       => $b->status,
                'user_name'    => $b->user['name'] ?? '-',
                'total_price'  => $b->total_price,
            ]);

        $acceptedBookings = Booking::where('status', 'accepted')
            ->orderBy('accepted_at', 'asc')
            ->get()
            ->map(fn($b) => [
                'id'           => (string) $b->_id,
                'booking_code' => $b->booking_code,
                'driver_name'  => $b->driver['name'] ?? '-',
                'user_name'    => $b->user['name'] ?? '-',
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'stats'            => $stats,
                'vehicle_stats'    => $vehicleStats,
                'recent_bookings'  => $recentBookings,
                'accepted_bookings'=> $acceptedBookings,
            ],
        ]);
    }

    /**
     * Laporan lengkap.
     */
    public function reports(): JsonResponse
    {
        $stats = [
            'total_bookings'     => Booking::count(),
            'completed_bookings' => Booking::where('status', 'completed')->count(),
            'cancelled_bookings' => Booking::where('status', 'cancelled')->count(),
            'ongoing_bookings'   => Booking::where('status', 'ongoing')->count(),
            'total_vehicles'     => Vehicle::count(),
            'total_drivers'      => User::where('role', 'driver')->count(),
            'total_users'        => User::where('role', 'customer')->count(),
            'total_revenue'      => Booking::whereIn('status', ['confirmed', 'ongoing', 'completed'])
                ->sum('total_price'),
        ];

        // Booking selesai per bulan (12 bulan terakhir)
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $count = Booking::where('status', 'completed')
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();
            $monthlyData[] = [
                'label' => $month->format('M y'),
                'month' => $month->month,
                'year'  => $month->year,
                'count' => $count,
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'stats'        => $stats,
                'monthly_data' => $monthlyData,
            ],
        ]);
    }
}
