<?php

namespace App\Http\Controllers\Admin;

use App\Exports\BookingExport;
use App\Http\Controllers\Api\DashboardController as ApiDashboard;
use App\Http\Controllers\Controller;
use App\Http\Traits\WebApiProxy;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use WebApiProxy;

    public function index(Request $request)
    {
        // Semua query MongoDB ada di Api/DashboardController::reportsForWeb()
        $api  = app(ApiDashboard::class);
        $req  = $this->makeApiRequest($request->query());
        $data = $api->reportsForWeb($req);

        return view('admin.reports.index', $data);
    }

    // ── EXPORT laporan periode ────────────────────────────────────────────────
    // export() tidak punya query MongoDB di controller — query ada di BookingExport class.
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

    // ── EXPORT arsip data lama ────────────────────────────────────────────────
    // Query builder diambil dari API layer, bukan dibuat langsung di sini.
    public function exportOld(Request $request)
    {
        $request->validate(['type' => 'required|in:old_cancelled,old_completed']);
        $type = $request->input('type');

        // Query MongoDB hanya ada di Api/DashboardController
        $api   = app(ApiDashboard::class);
        $query = $api->exportOldQueryForWeb($type);

        $filename = match ($type) {
            'old_cancelled' => 'arsip-cancelled-' . now()->format('Y-m-d') . '.xlsx',
            'old_completed' => 'arsip-completed-' . now()->format('Y-m-d') . '.xlsx',
        };

        return (new BookingExport(null, null, 'cleanup', query: $query))->download($filename);
    }

    // ── DELETE data lama ──────────────────────────────────────────────────────
    public function deleteOld(Request $request)
    {
        $request->validate(['type' => 'required|in:old_cancelled,old_completed']);

        // Operasi delete MongoDB ada di Api/DashboardController
        $api     = app(ApiDashboard::class);
        $deleted = $api->deleteOldForWeb($request->input('type'));

        return back()->with('cleanup_success', "Berhasil menghapus {$deleted} data.");
    }
}
