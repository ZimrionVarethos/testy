<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private BookingService $bookingService)
    {
    }

    /**
     * GET /api/v1/dashboard — statistik ringkas untuk dashboard admin
     */
    public function index(): JsonResponse
    {
        $this->bookingService->autoCancelPendingPaidWithoutDriver();

        $now       = Carbon::now();
        $weekStart = $now->copy()->startOfWeek();

        $weekRevenue = Payment::where('status', Payment::STATUS_PAID)
            ->where('paid_at', '>=', $weekStart)
            ->sum('amount');

        $stats = [
            'total_bookings'   => Booking::count(),
            'pending_bookings' => Booking::where('status', 'pending')->count(),
            'ongoing_bookings' => Booking::where('status', 'ongoing')->count(),
            'week_bookings'    => Booking::where('created_at', '>=', $weekStart)->count(),
            'week_completed'   => Booking::completed()->where('completed_at', '>=', $weekStart)->count(),
            'week_revenue'     => $weekRevenue,
            'weekly_revenue'   => $weekRevenue, // alias untuk kompatibilitas
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
                'user'         => $b->user,
                'vehicle_name' => $b->vehicle['name'] ?? '-',
                'start_date'   => $b->start_date?->toIso8601String(),
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
        $trendStart    = Carbon::now()->subDays(6)->startOfDay();
        $trendBookings = Booking::where('created_at', '>=', $trendStart)->get(['created_at']);
        $trendByDay    = $trendBookings->groupBy(fn($b) => Carbon::parse($b->created_at)->toDateString());

        $bookingTrend = collect(range(6, 0))->map(function ($daysAgo) use ($trendByDay) {
            $date = Carbon::now()->subDays($daysAgo);
            return ['date' => $date->locale('id')->shortDayName, 'total' => $trendByDay->get($date->toDateString(), collect())->count()];
        })->values();

        // Revenue 6 bulan
        $revenueStart    = Carbon::now()->subMonths(5)->startOfMonth();
        $revenuePayments = Payment::where('status', Payment::STATUS_PAID)
            ->where('paid_at', '>=', $revenueStart)
            ->get(['paid_at', 'amount']);
        $revenueByMonth  = $revenuePayments->groupBy(fn($p) => Carbon::parse($p->paid_at)->format('Y-m'));

        $revenueChart = collect(range(5, 0))->map(function ($monthsAgo) use ($revenueByMonth) {
            $date = Carbon::now()->subMonths($monthsAgo);
            return ['month' => $date->locale('id')->shortMonthName, 'revenue' => (int) $revenueByMonth->get($date->format('Y-m'), collect())->sum('amount')];
        })->values();

        // Tambahkan pending_paid ke stats agar SPA bisa baca stats.pending_paid
        $stats['pending_paid'] = $pendingPaidBookings->count();

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
        $this->bookingService->autoCancelPendingPaidWithoutDriver();

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

        $trendStart    = Carbon::now()->subDays(6)->startOfDay();
        $trendBookings = Booking::where('created_at', '>=', $trendStart)->get(['created_at']);
        $trendByDay    = $trendBookings->groupBy(fn($b) => Carbon::parse($b->created_at)->toDateString());

        $bookingTrend = collect(range(6, 0))->map(function ($daysAgo) use ($trendByDay) {
            $date = Carbon::now()->subDays($daysAgo);
            return ['date' => $date->locale('id')->shortDayName, 'total' => $trendByDay->get($date->toDateString(), collect())->count()];
        })->values()->toArray();

        $revenueStart    = Carbon::now()->subMonths(5)->startOfMonth();
        $revenuePayments = Payment::where('status', Payment::STATUS_PAID)
            ->where('paid_at', '>=', $revenueStart)
            ->get(['paid_at', 'amount']);
        $revenueByMonth  = $revenuePayments->groupBy(fn($p) => Carbon::parse($p->paid_at)->format('Y-m'));

        $revenueChart = collect(range(5, 0))->map(function ($monthsAgo) use ($revenueByMonth) {
            $date = Carbon::now()->subMonths($monthsAgo);
            return ['month' => $date->locale('id')->shortMonthName, 'revenue' => (int) $revenueByMonth->get($date->format('Y-m'), collect())->sum('amount')];
        })->values()->toArray();

        // Sumber kebenaran: hanya kendaraan berstatus 'rented' yang boleh muncul di peta
        $rentedVehicleIds = Vehicle::where('status', 'rented')
            ->get()->map(fn($v) => (string) $v->_id)->toArray();

        $activeBookingsByDriver = Booking::whereIn('status', [Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING])
            ->where('end_date', '>', $now)
            ->whereIn('vehicle.vehicle_id', $rentedVehicleIds)
            ->get()
            ->keyBy(fn($b) => (string) ($b->driver['driver_id'] ?? ''));

        $locationDriverIds = $activeBookingsByDriver->keys()->filter()->values()->toArray();
        $driversMap        = User::whereIn('_id', $locationDriverIds)
            ->whereNotNull('last_lat')->whereNotNull('last_lon')
            ->get()->keyBy(fn($d) => (string) $d->_id);

        $vehicleLocations = $activeBookingsByDriver
            ->filter(fn($b) => $driversMap->has((string) ($b->driver['driver_id'] ?? '')))
            ->map(function ($booking) use ($driversMap) {
                $driver = $driversMap->get((string) $booking->driver['driver_id']);
                return [
                    'lat'                 => $driver->last_lat,
                    'lon'                 => $driver->last_lon,
                    'plate'               => $booking->vehicle['plate_number'] ?? '-',
                    'driver'              => $booking->driver['name'] ?? $driver->name,
                    'status'              => $booking->status,
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
            ->where('end_date', '>', $now)
            ->get()
            ->keyBy(fn($b) => (string) ($b->vehicle['vehicle_id'] ?? ''));

        $driverIds  = $activeBookings->filter(fn($b) => !empty($b->driver['driver_id']))
            ->map(fn($b) => (string) $b->driver['driver_id'])
            ->unique()->values()->toArray();
        $driversMap = User::whereIn('_id', $driverIds)->get()->keyBy(fn($u) => (string) $u->_id);

        $mappedVehicles = $vehicles->map(function ($v) use ($activeBookings, $driversMap) {
            $vid     = (string) $v->_id;
            $name    = trim($v->name  ?? '');
            $brand   = trim($v->brand ?? '');
            $model   = trim($v->model ?? '');
            $label   = $name ?: ($brand && $model ? "$brand $model" : ($brand ?: $model));
            // Hanya kendaraan berstatus 'rented' yang boleh lookup booking — kendaraan 'available'
            // tidak boleh menampilkan driver/GPS meski ada booking future yang di-assign
            $booking = $v->status === 'rented' ? $activeBookings->get($vid) : null;

            $lat = $lon = $locationUpdatedAt = null;
            $isStale = false;

            if ($booking && !empty($booking->driver['driver_id'])) {
                $driver            = $driversMap->get((string) $booking->driver['driver_id']);
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

    public function maps(): JsonResponse
    {
        ['vehicles' => $vehicles, 'stats' => $stats] = $this->mapsIndexForWeb();

        return response()->json([
            'success' => true,
            'data'    => [
                'vehicles' => $vehicles,
                'stats'    => $stats,
            ],
        ]);
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

    public function mapShow(string $vehicleId): JsonResponse
    {
        ['vehicle' => $vehicle, 'activeBooking' => $activeBooking] = $this->mapsShowForWeb($vehicleId);

        return response()->json([
            'success' => true,
            'data'    => [
                'vehicle' => [
                    'id' => (string) $vehicle->_id,
                    'plate' => $vehicle->plate_number,
                    'name' => $vehicle->name,
                    'brand' => $vehicle->brand,
                    'model' => $vehicle->model,
                    'status' => $vehicle->status,
                    'last_lat' => $vehicle->last_lat,
                    'last_lon' => $vehicle->last_lon,
                    'last_location_updated_at' => $vehicle->last_location_updated_at,
                    'is_stale' => $vehicle->is_stale,
                    'driver' => $vehicle->driver ?? null,
                ],
                'active_booking' => $activeBooking ? [
                    'id' => (string) $activeBooking->_id,
                    'booking_code' => $activeBooking->booking_code,
                    'status' => $activeBooking->status,
                    'start_date' => $activeBooking->start_date?->toIso8601String(),
                    'end_date' => $activeBooking->end_date?->toIso8601String(),
                    'user' => $activeBooking->user,
                    'driver' => $activeBooking->driver,
                ] : null,
            ],
        ]);
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

    // ── ForWeb (dipakai Admin/ReportController langsung) ────────────────────

    /**
     * Data laporan lengkap dengan filter periode — untuk Admin/ReportController.
     */
    public function reportsForWeb(\Illuminate\Http\Request $request): array
    {
        $now    = Carbon::now();
        $period = $request->get('period', 'monthly');
        $year   = (int) $request->get('year',  $now->year);
        $month  = (int) $request->get('month', $now->month);

        if ($period === 'yearly') {
            $start      = Carbon::create($year)->startOfYear();
            $end        = Carbon::create($year)->endOfYear();
            $rangeLabel = "Tahun $year";
        } else {
            $start      = Carbon::create($year, $month)->startOfMonth();
            $end        = Carbon::create($year, $month)->endOfMonth();
            $rangeLabel = Carbon::create($year, $month)->locale('id')->isoFormat('MMMM YYYY');
        }

        // Semua stat menggunakan updated_at untuk completed/cancelled (reliable di semua record)
        // period_revenue konsisten dengan completed_bookings (sama-sama pakai updated_at completed)
        $stats = [
            'total_bookings'     => Booking::where('created_at', '>=', $start)->where('created_at', '<=', $end)->count(),
            'completed_bookings' => Booking::where('status', 'completed')->where('updated_at', '>=', $start)->where('updated_at', '<=', $end)->count(),
            'cancelled_bookings' => Booking::where('status', 'cancelled')->where('updated_at', '>=', $start)->where('updated_at', '<=', $end)->count(),
            'ongoing_bookings'   => Booking::where('status', 'ongoing')->count(),
            'pending_bookings'   => Booking::whereIn('status', ['pending', 'accepted'])->count(),
            'period_revenue'     => (int) Booking::where('status', 'completed')
                                        ->where('updated_at', '>=', $start)->where('updated_at', '<=', $end)
                                        ->sum('total_price'),
            'total_revenue'      => (int) Booking::whereIn('status', ['confirmed', 'ongoing', 'completed'])->sum('total_price'),
            'total_vehicles'     => Vehicle::count(),
            'total_drivers'      => User::where('role', 'driver')->count(),
            'total_users'        => User::where('role', 'pengguna')->count(),
        ];

        $chartLabels = $chartCompleted = $chartCancelled = $chartRevenue = [];

        // Query langsung per bucket — hindari in-PHP Carbon filtering yang rawan timezone/casting issue
        if ($period === 'yearly') {
            for ($m = 1; $m <= 12; $m++) {
                $ms = Carbon::create($year, $m)->startOfMonth();
                $me = Carbon::create($year, $m)->endOfMonth();
                $chartLabels[]    = $ms->locale('id')->isoFormat('MMM');
                $chartCompleted[] = Booking::where('status', 'completed')->where('updated_at', '>=', $ms)->where('updated_at', '<=', $me)->count();
                $chartCancelled[] = Booking::where('status', 'cancelled')->where('updated_at', '>=', $ms)->where('updated_at', '<=', $me)->count();
                $chartRevenue[]   = (int) Booking::where('status', 'completed')->where('updated_at', '>=', $ms)->where('updated_at', '<=', $me)->sum('total_price');
            }
        } else {
            $cursor = $start->copy()->startOfWeek();
            $weekNo = 1;
            while ($cursor->lte($end)) {
                $ws = $cursor->copy()->max($start);
                $we = $cursor->copy()->endOfWeek()->min($end);
                $chartLabels[]    = 'Mgg ' . $weekNo++;
                $chartCompleted[] = Booking::where('status', 'completed')->where('updated_at', '>=', $ws)->where('updated_at', '<=', $we)->count();
                $chartCancelled[] = Booking::where('status', 'cancelled')->where('updated_at', '>=', $ws)->where('updated_at', '<=', $we)->count();
                $chartRevenue[]   = (int) Booking::where('status', 'completed')->where('updated_at', '>=', $ws)->where('updated_at', '<=', $we)->sum('total_price');
                $cursor->addWeek();
            }
        }

        $statusDistribution = [
            ['label' => 'Completed', 'count' => $stats['completed_bookings'], 'color' => '#374151'],
            ['label' => 'Cancelled', 'count' => $stats['cancelled_bookings'], 'color' => '#dc2626'],
            ['label' => 'Ongoing',   'count' => $stats['ongoing_bookings'],   'color' => '#16a34a'],
            ['label' => 'Pending',   'count' => $stats['pending_bookings'],   'color' => '#d97706'],
        ];

        $fleetStats = [
            'available'   => Vehicle::where('status', 'available')->count(),
            'rented'      => Vehicle::where('status', 'rented')->count(),
            'maintenance' => Vehicle::where('status', 'maintenance')->count(),
        ];

        $topDrivers = Booking::where('status', 'completed')
            ->where('updated_at', '>=', $start)->where('updated_at', '<=', $end)
            ->whereNotNull('driver.driver_id')
            ->get(['driver', 'total_price', 'updated_at'])
            ->groupBy(fn($b) => (string) ($b->driver['driver_id'] ?? 'unknown'))
            ->map(fn($g) => [
                'name'    => $g->first()->driver['name'] ?? '-',
                'trips'   => $g->count(),
                'revenue' => (int) $g->sum('total_price'),
            ])
            ->sortByDesc('trips')->take(5)->values()->toArray();

        $firstBooking = Booking::orderBy('created_at', 'asc')->first();
        $firstYear    = $firstBooking ? Carbon::parse($firstBooking->created_at)->year : $now->year;
        $yearOptions  = range($now->year, $firstYear);

        $oldCancelled = Booking::where('status', 'cancelled')->where('updated_at', '<', $now->copy()->subMonths(3));
        $oldCompleted = Booking::where('status', 'completed')->where('updated_at', '<', $now->copy()->subMonths(12));

        $cleanupSuggestions = collect([
            [
                'key'         => 'old_cancelled',
                'label'       => 'Booking Dibatalkan (>3 bulan)',
                'description' => 'Booking cancelled lebih dari 3 bulan lalu, tidak lagi relevan untuk operasional.',
                'count'       => (clone $oldCancelled)->count(),
                'oldest'      => ($r = (clone $oldCancelled)->orderBy('updated_at', 'asc')->first())
                                  ? Carbon::parse($r->updated_at)->locale('id')->isoFormat('D MMM YYYY') : null,
                'color'       => 'red',
                'threshold'   => '3 bulan',
            ],
            [
                'key'         => 'old_completed',
                'label'       => 'Booking Selesai (>12 bulan)',
                'description' => 'Booking completed lebih dari 1 tahun lalu. Simpan arsip sebelum menghapus.',
                'count'       => (clone $oldCompleted)->count(),
                'oldest'      => ($r = (clone $oldCompleted)->orderBy('updated_at', 'asc')->first())
                                  ? Carbon::parse($r->updated_at)->locale('id')->isoFormat('D MMM YYYY') : null,
                'color'       => 'amber',
                'threshold'   => '12 bulan',
            ],
        ])->filter(fn($s) => $s['count'] > 0)->values();

        return compact(
            'stats', 'period', 'year', 'month', 'rangeLabel', 'yearOptions',
            'chartLabels', 'chartCompleted', 'chartCancelled', 'chartRevenue',
            'statusDistribution', 'fleetStats', 'topDrivers', 'cleanupSuggestions'
        );
    }

    /**
     * Query builder untuk export data lama — return builder bukan data, biar tetap lazy.
     */
    public function exportOldQueryForWeb(string $type): \Illuminate\Database\Eloquent\Builder
    {
        return match ($type) {
            'old_cancelled' => Booking::where('status', 'cancelled')->where('updated_at', '<', now()->subMonths(3)),
            'old_completed' => Booking::where('status', 'completed')->where('updated_at', '<', now()->subMonths(12)),
            default         => Booking::whereRaw(['_id' => null]), // empty query
        };
    }

    /**
     * Hapus data lama dan return jumlah yang dihapus.
     */
    public function deleteOldForWeb(string $type): int
    {
        return match ($type) {
            'old_cancelled' => Booking::where('status', 'cancelled')->where('updated_at', '<', now()->subMonths(3))->delete(),
            'old_completed' => Booking::where('status', 'completed')->where('updated_at', '<', now()->subMonths(12))->delete(),
            default         => 0,
        };
    }

    /**
     * GET /api/v1/dashboard/reports — laporan lengkap (mendukung filter period/year/month)
     * Mendelegasikan ke reportsForWeb() agar data sama persis dengan Blade.
     */
    public function reports(\Illuminate\Http\Request $request): JsonResponse
    {
        $data = $this->reportsForWeb($request);
        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * DELETE /api/v1/dashboard/reports/cleanup — hapus data lama
     */
    public function deleteOld(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate(['type' => 'required|in:old_cancelled,old_completed']);
        $deleted = $this->deleteOldForWeb($request->input('type'));
        return response()->json([
            'success' => true,
            'data'    => [
                'deleted' => $deleted,
                'message' => "Berhasil menghapus {$deleted} data.",
            ],
        ]);
    }
}
