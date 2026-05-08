<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    // ── ForWeb (dipakai web controller langsung) ─────────────────

    /** Untuk web admin dashboard */
    public function adminForWeb(): array
    {
        $now       = Carbon::now();
        $weekStart = $now->copy()->startOfWeek();

        $weekRevenue = Payment::where('status', Payment::STATUS_PAID)
            ->where('paid_at', '>=', $weekStart)->sum('amount');

        $stats = [
            'total_bookings'   => Booking::count(),
            'week_bookings'    => Booking::where('created_at', '>=', $weekStart)->count(),
            'ongoing_bookings' => Booking::ongoing()->count(),
            'week_completed'   => Booking::completed()->where('completed_at', '>=', $weekStart)->count(),
            'week_revenue'     => $weekRevenue,
            'pending_paid'     => 0, // diisi di bawah
        ];

        $paidBookingIds = Payment::where('status', Payment::STATUS_PAID)
            ->pluck('booking_id')->map(fn($id) => (string) $id)->toArray();

        $pendingPaidBookings = Booking::pending()
            ->whereIn('_id', $paidBookingIds)
            ->whereNull('driver.driver_id')
            ->orderBy('created_at', 'asc')->get();

        $stats['pending_paid'] = $pendingPaidBookings->count();

        $recentBookings = Booking::orderBy('created_at', 'desc')->limit(6)->get();

        $vehicleStats = [
            'available'   => Vehicle::where('status', 'available')->count(),
            'rented'      => Vehicle::where('status', 'rented')->count(),
            'maintenance' => Vehicle::where('status', 'maintenance')->count(),
        ];

        $bookingTrend = collect(range(6, 0))->map(function ($daysAgo) {
            $date  = Carbon::now()->subDays($daysAgo);
            $total = Booking::whereDate('created_at', $date->toDateString())->count();
            return ['date' => $date->locale('id')->shortDayName, 'total' => $total];
        })->values()->toArray();

        $revenueChart = collect(range(5, 0))->map(function ($monthsAgo) {
            $date    = Carbon::now()->subMonths($monthsAgo);
            $revenue = Payment::where('status', Payment::STATUS_PAID)
                ->whereYear('paid_at', $date->year)->whereMonth('paid_at', $date->month)->sum('amount');
            return ['month' => $date->locale('id')->shortMonthName, 'revenue' => $revenue];
        })->values()->toArray();

        $vehicleLocations = User::where('role', 'driver')->where('is_active', true)
            ->whereNotNull('last_lat')->whereNotNull('last_lon')->get()
            ->map(function ($driver) {
                $activeBooking = Booking::activeByDriver((string) $driver->_id)->first();
                return [
                    'lat'                 => $driver->last_lat,
                    'lon'                 => $driver->last_lon,
                    'plate'               => $activeBooking->vehicle['plate_number'] ?? '-',
                    'driver'              => $driver->name,
                    'status'              => $activeBooking?->status ?? 'available',
                    'location_updated_at' => $driver->last_location_updated_at
                        ? Carbon::parse($driver->last_location_updated_at)->format('d M H:i') : null,
                ];
            })->filter(fn($v) => $v['lat'] && $v['lon'])->values()->toArray();

        return compact('stats', 'pendingPaidBookings', 'recentBookings', 'vehicleStats',
                       'bookingTrend', 'revenueChart', 'vehicleLocations');
    }

    /** Untuk web driver dashboard */
    public function driverForWeb(Request $request): array
    {
        $driverId = (string) $request->user()->_id;

        $myActiveBookings = Booking::where('driver.driver_id', $driverId)
            ->whereIn('status', [Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING])
            ->orderBy('start_date', 'asc')->get();

        $stats = [
            'total_trips' => Booking::where('driver.driver_id', $driverId)->count(),
            'ongoing'     => Booking::where('driver.driver_id', $driverId)->ongoing()->count(),
            'confirmed'   => Booking::where('driver.driver_id', $driverId)->confirmed()->count(),
        ];

        $notifications = Notification::where('user_id', $driverId)
            ->where('is_read', false)->orderBy('created_at', 'desc')->limit(5)->get();

        return compact('myActiveBookings', 'stats', 'notifications');
    }

    /** Untuk web pengguna dashboard */
    public function penggunaForWeb(Request $request): array
    {
        $userId = (string) $request->user()->_id;

        $activeBookings = Booking::where('user.user_id', $userId)
            ->whereIn('status', [
                Booking::STATUS_PENDING,
                Booking::STATUS_CONFIRMED,
                Booking::STATUS_ONGOING,
            ])
            ->orderBy('created_at', 'desc')->limit(5)->get();

        $bookingIds = $activeBookings->pluck('_id')->map(fn($id) => (string) $id)->toArray();
        $activePayments = Payment::whereIn('booking_id', $bookingIds)
            ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_PENDING])
            ->get()->keyBy('booking_id');

        $stats = [
            'total'     => Booking::where('user.user_id', $userId)->count(),
            'ongoing'   => Booking::where('user.user_id', $userId)->ongoing()->count(),
            'completed' => Booking::where('user.user_id', $userId)->completed()->count(),
        ];

        $notifications = Notification::where('user_id', $userId)
            ->where('is_read', false)->orderBy('created_at', 'desc')->limit(5)->get();

        return compact('activeBookings', 'activePayments', 'stats', 'notifications');
    }

    /** Untuk web: data peta kendaraan aktif (admin) */
    public function mapsIndexForWeb(): array
    {
        $vehicles = \App\Models\Vehicle::all();
        $now      = Carbon::now();

        $activeBookings = Booking::whereIn('status', ['confirmed', 'ongoing'])
            ->where('start_date', '<=', $now)
            ->where('end_date', '>', $now)
            ->get()
            ->keyBy(fn($b) => (string) ($b->vehicle['vehicle_id'] ?? ''));

        $mappedVehicles = $vehicles->map(function ($v) use ($activeBookings) {
            $vid     = (string) $v->_id;
            $name    = trim($v->name  ?? '');
            $brand   = trim($v->brand ?? '');
            $model   = trim($v->model ?? '');
            $label   = $name ?: ($brand && $model ? "$brand $model" : ($brand ?: $model));
            $booking = $activeBookings->get($vid);

            $lat = $lon = $locationUpdatedAt = null;
            $isStale = false;

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
                'driver'                 => $booking?->driver['name'] ?? '-',
                'status'                 => $v->status ?? 'available',
                'lat'                    => $lat,
                'lon'                    => $lon,
                'has_active_booking'     => $booking !== null,
                'location_updated_at'    => $locationUpdatedAt
                    ? Carbon::parse($locationUpdatedAt)->format('H:i, d M') : null,
                'location_updated_human' => $locationUpdatedAt
                    ? Carbon::parse($locationUpdatedAt)->diffForHumans() : null,
                'is_stale'               => $isStale,
            ];
        });

        $stats = [
            'total'       => $mappedVehicles->count(),
            'ongoing'     => $mappedVehicles->where('status', 'rented')->count(),
            'available'   => $mappedVehicles->where('status', 'available')->count(),
            'maintenance' => $mappedVehicles->where('status', 'maintenance')->count(),
        ];

        return ['vehicles' => $mappedVehicles->values(), 'stats' => $stats];
    }

    /** Untuk web: detail peta satu kendaraan (admin) */
    public function mapsShowForWeb(string $vehicleId): array
    {
        $vehicle = \App\Models\Vehicle::findOrFail($vehicleId);
        $now     = Carbon::now();

        $activeBooking = Booking::whereIn('status', ['confirmed', 'ongoing'])
            ->where('vehicle.vehicle_id', $vehicleId)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>', $now)
            ->latest('created_at')
            ->first();

        $lat = $lon = $locationUpdatedAt = null;
        $isStale = false;

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

        return compact('vehicle', 'activeBooking');
    }

    /**
     * GET /api/v1/dashboard/driver
     */
    public function driver(Request $request): JsonResponse
    {
        $driverId = (string) $request->user()->_id;

        $myActiveBookings = Booking::where('driver.driver_id', $driverId)
            ->whereIn('status', [Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING])
            ->orderBy('start_date', 'asc')
            ->get()
            ->map(fn($b) => [
                'id'           => (string) $b->_id,
                'booking_code' => $b->booking_code,
                'status'       => $b->status,
                'start_date'   => $b->start_date?->toIso8601String(),
                'end_date'     => $b->end_date?->toIso8601String(),
                'user_name'    => $b->user['name'] ?? '-',
                'vehicle_name' => $b->vehicle['name'] ?? '-',
                'pickup'       => $b->pickup,
            ]);

        $stats = [
            'total_trips' => Booking::where('driver.driver_id', $driverId)->count(),
            'ongoing'     => Booking::where('driver.driver_id', $driverId)->ongoing()->count(),
            'confirmed'   => Booking::where('driver.driver_id', $driverId)->confirmed()->count(),
        ];

        $notifications = Notification::where('user_id', $driverId)
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($n) => [
                'id'         => (string) $n->_id,
                'title'      => $n->title,
                'message'    => $n->message,
                'created_at' => $n->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'stats'               => $stats,
                'active_bookings'     => $myActiveBookings,
                'unread_notifications'=> $notifications,
            ],
        ]);
    }

    /**
     * GET /api/v1/dashboard/pengguna
     */
    public function pengguna(Request $request): JsonResponse
    {
        $userId = (string) $request->user()->_id;

        $activeBookings = Booking::where('user.user_id', $userId)
            ->whereIn('status', [
                Booking::STATUS_PENDING,
                Booking::STATUS_CONFIRMED,
                Booking::STATUS_ONGOING,
            ])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $bookingIds = $activeBookings->pluck('_id')->map(fn($id) => (string) $id)->toArray();
        $activePayments = Payment::whereIn('booking_id', $bookingIds)
            ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_PENDING])
            ->get()
            ->keyBy('booking_id');

        $bookingData = $activeBookings->map(function ($b) use ($activePayments) {
            $payment = $activePayments[(string) $b->_id] ?? null;
            return [
                'id'           => (string) $b->_id,
                'booking_code' => $b->booking_code,
                'status'       => $b->status,
                'start_date'   => $b->start_date?->toIso8601String(),
                'vehicle_name' => $b->vehicle['name'] ?? '-',
                'total_price'  => $b->total_price,
                'payment_status' => $payment?->status,
                'snap_token'   => $payment?->midtrans['snap_token'] ?? null,
            ];
        });

        $stats = [
            'total'     => Booking::where('user.user_id', $userId)->count(),
            'ongoing'   => Booking::where('user.user_id', $userId)->ongoing()->count(),
            'completed' => Booking::where('user.user_id', $userId)->completed()->count(),
        ];

        $notifications = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($n) => [
                'id'         => (string) $n->_id,
                'title'      => $n->title,
                'message'    => $n->message,
                'created_at' => $n->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'stats'               => $stats,
                'active_bookings'     => $bookingData,
                'unread_notifications'=> $notifications,
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
