<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * GET /api/v1/dashboard — statistik ringkas untuk dashboard admin
     */
    public function index(): JsonResponse
    {
        $now       = Carbon::now();
        $weekStart = $now->copy()->startOfWeek();

        $stats = [
            'total_bookings'   => Booking::count(),
            'pending_bookings' => Booking::where('status', 'pending')->count(),
            'ongoing_bookings' => Booking::where('status', 'ongoing')->count(),
            'weekly_revenue'   => Payment::where('status', Payment::STATUS_PAID)
                ->where('paid_at', '>=', $weekStart)
                ->sum('amount'),
            'monthly_revenue'  => Payment::where('status', Payment::STATUS_PAID)
                ->whereYear('paid_at', $now->year)
                ->whereMonth('paid_at', $now->month)
                ->sum('amount'),
        ];

        $vehicleStats = [
            'available'   => Vehicle::where('status', 'available')->count(),
            'rented'      => Vehicle::where('status', 'rented')->count(),
            'maintenance' => Vehicle::where('status', 'maintenance')->count(),
        ];

        // Pending yang sudah bayar — perlu tindakan admin
        $paidBookingIds = Payment::where('status', Payment::STATUS_PAID)
            ->pluck('booking_id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        $pendingPaidBookings = Booking::pending()
            ->whereIn('_id', $paidBookingIds)
            ->whereNull('driver.driver_id')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($b) => [
                'id'           => (string) $b->_id,
                'booking_code' => $b->booking_code,
                'user_name'    => $b->user['name'] ?? '-',
                'vehicle_name' => $b->vehicle['name'] ?? '-',
                'created_at'   => $b->created_at?->toIso8601String(),
            ]);

        $recentBookings = Booking::orderBy('created_at', 'desc')
            ->limit(6)
            ->get()
            ->map(fn($b) => [
                'id'           => (string) $b->_id,
                'booking_code' => $b->booking_code,
                'status'       => $b->status,
                'user_name'    => $b->user['name'] ?? '-',
                'total_price'  => $b->total_price,
                'created_at'   => $b->created_at?->toIso8601String(),
            ]);

        // Booking trend 7 hari
        $bookingTrend = collect(range(6, 0))->map(function ($daysAgo) {
            $date  = Carbon::now()->subDays($daysAgo);
            $total = Booking::whereDate('created_at', $date->toDateString())->count();
            return ['date' => $date->locale('id')->shortDayName, 'total' => $total];
        })->values();

        // Revenue 6 bulan
        $revenueChart = collect(range(5, 0))->map(function ($monthsAgo) {
            $date    = Carbon::now()->subMonths($monthsAgo);
            $revenue = Payment::where('status', Payment::STATUS_PAID)
                ->whereYear('paid_at', $date->year)
                ->whereMonth('paid_at', $date->month)
                ->sum('amount');
            return ['month' => $date->locale('id')->shortMonthName, 'revenue' => $revenue];
        })->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'stats'                => $stats,
                'vehicle_stats'        => $vehicleStats,
                'pending_paid_count'   => $pendingPaidBookings->count(),
                'pending_paid_bookings'=> $pendingPaidBookings,
                'recent_bookings'      => $recentBookings,
                'booking_trend'        => $bookingTrend,
                'revenue_chart'        => $revenueChart,
            ],
        ]);
    }

    /**
     * GET /api/v1/dashboard/reports — laporan lengkap
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
            // 🔧 FIX: role 'pengguna' bukan 'customer'
            'total_users'        => User::where('role', 'pengguna')->count(),
            'total_revenue'      => Payment::where('status', Payment::STATUS_PAID)->sum('amount'),
        ];

        // Booking selesai per bulan (12 bulan terakhir)
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $count = Booking::where('status', 'completed')
                ->whereMonth('completed_at', $month->month)
                ->whereYear('completed_at', $month->year)
                ->count();
            $revenue = Payment::where('status', Payment::STATUS_PAID)
                ->whereMonth('paid_at', $month->month)
                ->whereYear('paid_at', $month->year)
                ->sum('amount');
            $monthlyData[] = [
                'label'   => $month->format('M Y'),
                'month'   => $month->month,
                'year'    => $month->year,
                'count'   => $count,
                'revenue' => $revenue,
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
