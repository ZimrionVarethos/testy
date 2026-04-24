<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return match ($user->role) {
            'admin'    => $this->adminDashboard(),
            'driver'   => $this->driverDashboard(),
            default    => $this->penggunaDashboard(),
        };
    }

    // ────────────────────────────────────────────────────────
    // ADMIN
    // ────────────────────────────────────────────────────────
    private function adminDashboard()
    {
        $now       = Carbon::now();
        $weekStart = $now->copy()->startOfWeek();

        // ── Stats utama ──────────────────────────────────────
        $totalBookings  = Booking::count();
        $weekBookings   = Booking::where('created_at', '>=', $weekStart)->count();
        $ongoingBookings= Booking::ongoing()->count();
        $weekCompleted  = Booking::completed()->where('completed_at', '>=', $weekStart)->count();

        $weekRevenue = Payment::where('status', Payment::STATUS_PAID)
            ->where('paid_at', '>=', $weekStart)
            ->sum('amount');

        // ── Pending yang sudah bayar (perlu tindakan admin) ──
        // Ini yang muncul di alert "Perlu Tindakan" di dashboard
        $paidBookingIds = Payment::where('status', Payment::STATUS_PAID)
            ->pluck('booking_id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        $pendingPaidBookings = Booking::pending()
            ->whereIn('_id', $paidBookingIds)
            ->whereNull('driver.driver_id')
            ->orderBy('created_at', 'asc')
            ->get();

        $pendingPaidCount = $pendingPaidBookings->count();

        // ── Pesanan terbaru ──────────────────────────────────
        $recentBookings = Booking::orderBy('created_at', 'desc')->limit(6)->get();

        // ── Vehicle stats ────────────────────────────────────
        $vehicleStats = [
            'available'   => Vehicle::where('status', 'available')->count(),
            'rented'      => Vehicle::where('status', 'rented')->count(),
            'maintenance' => Vehicle::where('status', 'maintenance')->count(),
        ];

        // ── Booking trend (7 hari) ───────────────────────────
        $bookingTrend = collect(range(6, 0))->map(function ($daysAgo) {
            $date  = Carbon::now()->subDays($daysAgo);
            $total = Booking::whereDate('created_at', $date->toDateString())->count();
            return ['date' => $date->locale('id')->shortDayName, 'total' => $total];
        })->values()->toArray();

        // ── Revenue chart (6 bulan) ──────────────────────────
        $revenueChart = collect(range(5, 0))->map(function ($monthsAgo) {
            $date    = Carbon::now()->subMonths($monthsAgo);
            $revenue = Payment::where('status', Payment::STATUS_PAID)
                ->whereYear('paid_at', $date->year)
                ->whereMonth('paid_at', $date->month)
                ->sum('amount');
            return ['month' => $date->locale('id')->shortMonthName, 'revenue' => $revenue];
        })->values()->toArray();

        // ── Vehicle locations (untuk peta) ───────────────────
        $vehicleLocations = User::where('role', 'driver')
            ->where('is_active', true)
            ->whereNotNull('last_lat')
            ->whereNotNull('last_lon')
            ->get()
            ->map(function ($driver) {
                $activeBooking = Booking::activeByDriver((string) $driver->_id)->first();
                return [
                    'lat'                => $driver->last_lat,
                    'lon'                => $driver->last_lon,
                    'plate'              => $activeBooking->vehicle['plate_number'] ?? '-',
                    'driver'             => $driver->name,
                    'status'             => $activeBooking?->status ?? 'available',
                    'location_updated_at'=> $driver->last_location_updated_at
                        ? Carbon::parse($driver->last_location_updated_at)->format('d M H:i')
                        : null,
                ];
            })->filter(fn($v) => $v['lat'] && $v['lon'])->values()->toArray();

        return view('dashboard', [
            'stats' => [
                'total_bookings'  => $totalBookings,
                'week_bookings'   => $weekBookings,
                'ongoing_bookings'=> $ongoingBookings,
                'week_completed'  => $weekCompleted,
                'week_revenue'    => $weekRevenue,
                'pending_paid'    => $pendingPaidCount,   // ← dipakai di stat card
            ],
            'pendingPaidBookings' => $pendingPaidBookings, // ← dipakai di alert
            'recentBookings'      => $recentBookings,
            'vehicleStats'        => $vehicleStats,
            'bookingTrend'        => $bookingTrend,
            'revenueChart'        => $revenueChart,
            'vehicleLocations'    => $vehicleLocations,
        ]);
    }

    // ────────────────────────────────────────────────────────
    // DRIVER
    // ────────────────────────────────────────────────────────
    private function driverDashboard()
    {
        $driverId = (string) Auth::id();

        $myActiveBookings = Booking::where('driver.driver_id', $driverId)
            ->whereIn('status', [Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING])
            ->orderBy('start_date', 'asc')
            ->get();

        $stats = [
            'total_trips' => Booking::where('driver.driver_id', $driverId)->count(),
            'ongoing'     => Booking::where('driver.driver_id', $driverId)->ongoing()->count(),
            'confirmed'   => Booking::where('driver.driver_id', $driverId)->confirmed()->count(),
        ];

        $notifications = Notification::where('user_id', $driverId)
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard', compact('myActiveBookings', 'stats', 'notifications'));
    }

    // ────────────────────────────────────────────────────────
    // PENGGUNA
    // ────────────────────────────────────────────────────────
    private function penggunaDashboard()
    {
        $userId = (string) Auth::id();

        $activeBookings = Booking::where('user.user_id', $userId)
            ->whereIn('status', [
                Booking::STATUS_PENDING,
                Booking::STATUS_CONFIRMED,
                Booking::STATUS_ONGOING,
            ])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $stats = [
            'total'     => Booking::where('user.user_id', $userId)->count(),
            'ongoing'   => Booking::where('user.user_id', $userId)->ongoing()->count(),
            'completed' => Booking::where('user.user_id', $userId)->completed()->count(),
        ];

        $notifications = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard', compact('activeBookings', 'stats', 'notifications'));
    }
}