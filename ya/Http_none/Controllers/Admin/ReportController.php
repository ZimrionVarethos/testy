<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Vehicle;
use App\Models\User;
use App\Exports\BookingExport;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $now    = Carbon::now();
        $period = $request->get('period', 'monthly');
        $year   = (int) $request->get('year',  $now->year);
        $month  = (int) $request->get('month', $now->month);

        // ── Date range ────────────────────────────────────────
        if ($period === 'yearly') {
            $start      = Carbon::create($year)->startOfYear();
            $end        = Carbon::create($year)->endOfYear();
            $rangeLabel = "Tahun $year";
        } else {
            $start      = Carbon::create($year, $month)->startOfMonth();
            $end        = Carbon::create($year, $month)->endOfMonth();
            $rangeLabel = Carbon::create($year, $month)->locale('id')->isoFormat('MMMM YYYY');
        }

        // ── Stats ─────────────────────────────────────────────
        $stats = [
            'total_bookings'     => Booking::where('created_at', '>=', $start)->where('created_at', '<=', $end)->count(),
            'completed_bookings' => Booking::where('status', 'completed')
                                     ->where('completed_at', '>=', $start)->where('completed_at', '<=', $end)->count(),
            'cancelled_bookings' => Booking::where('status', 'cancelled')
                                     ->where('updated_at', '>=', $start)->where('updated_at', '<=', $end)->count(),
            'ongoing_bookings'   => Booking::where('status', 'ongoing')->count(),
            'pending_bookings'   => Booking::whereIn('status', ['pending', 'accepted'])->count(),
            'period_revenue'     => (int) Booking::whereIn('status', ['confirmed', 'ongoing', 'completed'])
                                     ->where('confirmed_at', '>=', $start)->where('confirmed_at', '<=', $end)
                                     ->sum('total_price'),
            'total_revenue'      => (int) Booking::whereIn('status', ['confirmed', 'ongoing', 'completed'])
                                     ->sum('total_price'),
            'total_vehicles'     => Vehicle::count(),
            'total_drivers'      => User::where('role', 'driver')->count(),
            'total_users'        => User::where('role', 'pengguna')->count(),
        ];

        // ── Chart data ────────────────────────────────────────
        $chartLabels = $chartCompleted = $chartCancelled = $chartRevenue = [];

        if ($period === 'yearly') {
            for ($m = 1; $m <= 12; $m++) {
                $ms = Carbon::create($year, $m)->startOfMonth();
                $me = Carbon::create($year, $m)->endOfMonth();
                $chartLabels[]    = $ms->locale('id')->isoFormat('MMM');
                $chartCompleted[] = Booking::where('status', 'completed')
                                     ->where('completed_at', '>=', $ms)->where('completed_at', '<=', $me)->count();
                $chartCancelled[] = Booking::where('status', 'cancelled')
                                     ->where('updated_at', '>=', $ms)->where('updated_at', '<=', $me)->count();
                $chartRevenue[]   = (int) Booking::whereIn('status', ['confirmed', 'ongoing', 'completed'])
                                     ->where('confirmed_at', '>=', $ms)->where('confirmed_at', '<=', $me)->sum('total_price');
            }
        } else {
            $cursor = $start->copy()->startOfWeek();
            $weekNo = 1;
            while ($cursor->lte($end)) {
                $ws = $cursor->copy()->max($start);
                $we = $cursor->copy()->endOfWeek()->min($end);
                $chartLabels[]    = 'Mgg ' . $weekNo++;
                $chartCompleted[] = Booking::where('status', 'completed')
                                     ->where('completed_at', '>=', $ws)->where('completed_at', '<=', $we)->count();
                $chartCancelled[] = Booking::where('status', 'cancelled')
                                     ->where('updated_at', '>=', $ws)->where('updated_at', '<=', $we)->count();
                $chartRevenue[]   = (int) Booking::whereIn('status', ['confirmed', 'ongoing', 'completed'])
                                     ->where('confirmed_at', '>=', $ws)->where('confirmed_at', '<=', $we)->sum('total_price');
                $cursor->addWeek();
            }
        }

        // ── Status distribution ───────────────────────────────
        $statusDistribution = [
            ['label' => 'Completed', 'count' => $stats['completed_bookings'], 'color' => '#374151'],
            ['label' => 'Cancelled', 'count' => $stats['cancelled_bookings'], 'color' => '#dc2626'],
            ['label' => 'Ongoing',   'count' => $stats['ongoing_bookings'],   'color' => '#16a34a'],
            ['label' => 'Pending',   'count' => $stats['pending_bookings'],   'color' => '#d97706'],
        ];

        // ── Fleet ─────────────────────────────────────────────
        $fleetStats = [
            'available'   => Vehicle::where('status', 'available')->count(),
            'rented'      => Vehicle::where('status', 'rented')->count(),
            'maintenance' => Vehicle::where('status', 'maintenance')->count(),
        ];

        // ── Top 5 driver ──────────────────────────────────────
        $topDrivers = Booking::where('status', 'completed')
            ->where('completed_at', '>=', $start)->where('completed_at', '<=', $end)
            ->whereNotNull('driver.driver_id')
            ->get(['driver', 'total_price'])
            ->groupBy(fn($b) => (string) ($b->driver['driver_id'] ?? 'unknown'))
            ->map(fn($g) => [
                'name'    => $g->first()->driver['name'] ?? '-',
                'trips'   => $g->count(),
                'revenue' => (int) $g->sum('total_price'),
            ])
            ->sortByDesc('trips')->take(5)->values()->toArray();

        // ── Tahun tersedia untuk dropdown ─────────────────────
        $firstBooking = Booking::orderBy('created_at', 'asc')->first();
        $firstYear    = $firstBooking ? Carbon::parse($firstBooking->created_at)->year : $now->year;
        $yearOptions  = range($now->year, $firstYear);

        // ── Cleanup recommendations ───────────────────────────
        $oldCancelled = Booking::where('status', 'cancelled')
            ->where('updated_at', '<', $now->copy()->subMonths(3));
        $oldCompleted = Booking::where('status', 'completed')
            ->where('completed_at', '<', $now->copy()->subMonths(12));

        $cleanupSuggestions = collect([
            [
                'key'         => 'old_cancelled',
                'label'       => 'Booking Dibatalkan (>3 bulan)',
                'description' => 'Booking cancelled lebih dari 3 bulan lalu, tidak lagi relevan untuk operasional.',
                'count'       => (clone $oldCancelled)->count(),
                'oldest'      => ($r = (clone $oldCancelled)->orderBy('updated_at','asc')->first())
                                  ? Carbon::parse($r->updated_at)->locale('id')->isoFormat('D MMM YYYY') : null,
                'color'       => 'red',
                'threshold'   => '3 bulan',
            ],
            [
                'key'         => 'old_completed',
                'label'       => 'Booking Selesai (>12 bulan)',
                'description' => 'Booking completed lebih dari 1 tahun lalu. Simpan arsip sebelum menghapus.',
                'count'       => (clone $oldCompleted)->count(),
                'oldest'      => ($r = (clone $oldCompleted)->orderBy('completed_at','asc')->first())
                                  ? Carbon::parse($r->completed_at)->locale('id')->isoFormat('D MMM YYYY') : null,
                'color'       => 'amber',
                'threshold'   => '12 bulan',
            ],
        ])->filter(fn($s) => $s['count'] > 0)->values();

        return view('admin.reports.index', compact(
            'stats', 'period', 'year', 'month', 'rangeLabel', 'yearOptions',
            'chartLabels', 'chartCompleted', 'chartCancelled', 'chartRevenue',
            'statusDistribution', 'fleetStats', 'topDrivers', 'cleanupSuggestions',
        ));
    }

    // ── EXPORT laporan periode ────────────────────────────────
    public function export(Request $request)
    {
        $period = $request->get('period', 'monthly');
        $year   = (int) $request->get('year',  now()->year);
        $month  = (int) $request->get('month', now()->month);

        if ($period === 'yearly') {
            $start    = Carbon::create($year)->startOfYear();
            $end      = Carbon::create($year)->endOfYear();
            $filename = "laporan-{$year}.xlsx";
        } else {
            $start     = Carbon::create($year, $month)->startOfMonth();
            $end       = Carbon::create($year, $month)->endOfMonth();
            $monthName = Carbon::create($year, $month)->locale('id')->isoFormat('MMMM');
            $filename  = "laporan-{$monthName}-{$year}.xlsx";
        }

        return (new BookingExport($start, $end, $period, $year, $month))->download($filename);
    }

    // ── EXPORT arsip data lama ────────────────────────────────
    public function exportOld(Request $request)
    {
        $request->validate(['type' => 'required|in:old_cancelled,old_completed']);
        $type = $request->input('type');

        if ($type === 'old_cancelled') {
            $query    = Booking::where('status', 'cancelled')->where('updated_at', '<', now()->subMonths(3));
            $filename = 'arsip-cancelled-' . now()->format('Y-m-d') . '.xlsx';
        } else {
            $query    = Booking::where('status', 'completed')->where('completed_at', '<', now()->subMonths(12));
            $filename = 'arsip-completed-' . now()->format('Y-m-d') . '.xlsx';
        }

        return (new BookingExport(null, null, 'cleanup', query: $query))->download($filename);
    }

    // ── DELETE data lama ──────────────────────────────────────
    public function deleteOld(Request $request)
    {
        $request->validate(['type' => 'required|in:old_cancelled,old_completed']);
        $type = $request->input('type');

        $deleted = match ($type) {
            'old_cancelled' => Booking::where('status', 'cancelled')
                                ->where('updated_at', '<', now()->subMonths(3))->delete(),
            'old_completed' => Booking::where('status', 'completed')
                                ->where('completed_at', '<', now()->subMonths(12))->delete(),
            default         => 0,
        };

        return back()->with('cleanup_success', "Berhasil menghapus {$deleted} data.");
    }
}