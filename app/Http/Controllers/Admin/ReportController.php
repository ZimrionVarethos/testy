<?php namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking; use App\Models\Vehicle; use App\Models\User; use Carbon\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        $stats = [
            'total_bookings'     => Booking::count(),
            'completed_bookings' => Booking::where('status', 'completed')->count(),
            'cancelled_bookings' => Booking::where('status', 'cancelled')->count(),
            'ongoing_bookings'   => Booking::where('status', 'ongoing')->count(),
            'total_vehicles'     => Vehicle::count(),
            'total_drivers'      => User::where('role', 'driver')->count(),
            'total_users'        => User::where('role', 'pengguna')->count(),
        ];

        // Booking per bulan (6 bulan terakhir)
        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $monthlyData[] = [
                'label' => $month->format('M Y'),
                'count' => Booking::where('status', 'completed')
                    ->where('completed_at', '>=', $month->startOfMonth()->toDateTimeString())
                    ->where('completed_at', '<=', $month->copy()->endOfMonth()->toDateTimeString())
                    ->count(),
            ];
        }

        return view('admin.reports.index', compact('stats', 'monthlyData'));
    }
}