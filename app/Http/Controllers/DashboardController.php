<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Vehicle;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        return match(Auth::user()->role) {
            'admin'            => $this->adminDashboard(),
            'pengguna', 'user' => $this->penggunaDashboard(),
            'driver'           => $this->driverDashboard(),
            default            => view('dashboard'),
        };
    }

    private function adminDashboard()
    {
        $now       = Carbon::now();
        $weekStart = $now->copy()->startOfWeek();
        $weekEnd   = $now->copy()->endOfWeek();
        $prevStart = $weekStart->copy()->subWeek();
        $prevEnd   = $weekEnd->copy()->subWeek();

        // ── Stat cards: data MINGGU INI ──────────────────────
        $weekBookings = Booking::where('created_at', '>=', $weekStart)
                                ->where('created_at', '<=', $weekEnd)->count();
        $prevBookings = Booking::where('created_at', '>=', $prevStart)
                                ->where('created_at', '<=', $prevEnd)->count();

        $weekRevenue  = (int) Booking::whereIn('status', ['confirmed', 'ongoing', 'completed'])
                            ->where('confirmed_at', '>=', $weekStart)
                            ->where('confirmed_at', '<=', $weekEnd)
                            ->sum('total_price');
        $prevRevenue  = (int) Booking::whereIn('status', ['confirmed', 'ongoing', 'completed'])
                            ->where('confirmed_at', '>=', $prevStart)
                            ->where('confirmed_at', '<=', $prevEnd)
                            ->sum('total_price');

        $stats = [
            'week_bookings'    => $weekBookings,
            'week_revenue'     => $weekRevenue,
            'pending_bookings' => Booking::whereIn('status', ['pending', 'accepted'])->count(),
            'ongoing_bookings' => Booking::where('status', 'ongoing')->count(),
            'booking_change'   => $prevBookings > 0
                                    ? round((($weekBookings - $prevBookings) / $prevBookings) * 100)
                                    : null,
            'revenue_change'   => $prevRevenue > 0
                                    ? round((($weekRevenue - $prevRevenue) / $prevRevenue) * 100)
                                    : null,
            'week_label'       => $weekStart->locale('id')->isoFormat('D MMM')
                                    . ' – ' . $weekEnd->locale('id')->isoFormat('D MMM YYYY'),
        ];

        // ── Chart: booking trend 7 hari ──────────────────────
        $bookingTrend = collect(range(6, 0))->map(function (int $d) use ($now) {
            $day = $now->copy()->subDays($d);
            return [
                'date'  => $day->locale('id')->isoFormat('ddd'),
                'total' => Booking::where('created_at', '>=', $day->copy()->startOfDay())
                                  ->where('created_at', '<=', $day->copy()->endOfDay())
                                  ->count(),
            ];
        })->values()->toArray();

        // ── Chart: revenue 6 bulan ────────────────────────────
        $revenueChart = collect(range(5, 0))->map(function (int $m) use ($now) {
            $month = $now->copy()->subMonths($m);
            return [
                'month'   => $month->locale('id')->isoFormat('MMM'),
                'revenue' => (int) Booking::whereIn('status', ['confirmed', 'ongoing', 'completed'])
                                ->where('confirmed_at', '>=', $month->copy()->startOfMonth())
                                ->where('confirmed_at', '<=', $month->copy()->endOfMonth())
                                ->sum('total_price'),
            ];
        })->values()->toArray();

        // ── Vehicle stats ─────────────────────────────────────
        $vehicleStats = [
            'available'   => Vehicle::where('status', 'available')->count(),
            'rented'      => Vehicle::where('status', 'rented')->count(),
            'maintenance' => Vehicle::where('status', 'maintenance')->count(),
        ];

        $recentBookings   = Booking::orderBy('created_at', 'desc')->limit(5)->get();
        $acceptedBookings = Booking::where('status', 'accepted')->orderBy('accepted_at', 'asc')->get();

        // ── Map: vehicle locations ────────────────────────────
        $activeBookings = Booking::whereIn('status', ['confirmed', 'ongoing'])
            ->get()->keyBy(fn($b) => (string) ($b->vehicle['vehicle_id'] ?? ''));

        $vehicleLocations = Vehicle::all()->map(function ($v) use ($activeBookings) {
            $vid     = (string) $v->_id;
            $booking = $activeBookings->get($vid);
            $lat = $lon = $locationUpdatedAt = null;

            if ($booking && !empty($booking->driver['driver_id'])) {
                $driver            = User::find($booking->driver['driver_id']);
                $lat               = $driver?->last_lat;
                $lon               = $driver?->last_lon;
                $locationUpdatedAt = $driver?->last_location_updated_at;
            }

            return [
                'id'                  => $vid,
                'plate'               => $v->plate_number ?? '-',
                'driver'              => $booking?->driver['name'] ?? '-',
                'status'              => $v->status ?? 'available',
                'lat'                 => $lat,
                'lon'                 => $lon,
                'location_updated_at' => $locationUpdatedAt
                    ? Carbon::parse($locationUpdatedAt)->format('H:i, d M') : null,
            ];
        })->filter(fn($v) => !empty($v['lat']) && !empty($v['lon']))->values()->all();

        return view('dashboard', compact(
            'stats', 'vehicleStats', 'recentBookings', 'acceptedBookings',
            'vehicleLocations', 'bookingTrend', 'revenueChart',
        ));
    }

    private function penggunaDashboard()
    {
        $userId = (string) Auth::id();
        $stats  = [
            'total'     => Booking::where('user.user_id', $userId)->count(),
            'ongoing'   => Booking::where('user.user_id', $userId)->where('status', 'ongoing')->count(),
            'completed' => Booking::where('user.user_id', $userId)->where('status', 'completed')->count(),
        ];
        $activeBookings = Booking::where('user.user_id', $userId)
            ->whereIn('status', ['pending', 'accepted', 'confirmed', 'ongoing'])
            ->orderBy('created_at', 'desc')->get();
        $notifications = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')->limit(5)->get();

        return view('dashboard', compact('stats', 'activeBookings', 'notifications'));
    }

    private function driverDashboard()
    {
        $driver   = Auth::user();
        $driverId = (string) $driver->_id;
        $stats    = [
            'total_trips'       => $driver->driver_profile['total_trips'] ?? 0,
            'ongoing'           => Booking::where('driver.driver_id', $driverId)->where('status', 'ongoing')->count(),
            'pending_available' => Booking::where('status', 'pending')->count(),
            'rating_avg'        => $driver->driver_profile['rating_avg'] ?? 0,
        ];
        $myActiveBookings = Booking::where('driver.driver_id', $driverId)
            ->whereIn('status', ['accepted', 'confirmed', 'ongoing'])
            ->orderBy('start_date', 'asc')->get();
        $availableBookings = Booking::where('status', 'pending')
            ->orderBy('created_at', 'asc')->limit(5)->get();

        return view('dashboard', compact('stats', 'myActiveBookings', 'availableBookings'));
    }
}