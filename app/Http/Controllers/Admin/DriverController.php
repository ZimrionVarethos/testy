<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\DriverController as ApiDriver;
use App\Http\Traits\WebApiProxy;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    use WebApiProxy;

    public function index(Request $request, ApiDriver $api)
    {
        $req     = $this->makeApiRequest(['per_page' => 15]);
        $drivers = $api->indexForWeb($req);

        return view('admin.drivers.index', compact('drivers'));
    }

    public function show(string $id, ApiDriver $api)
    {
        ['driver' => $driver, 'bookings' => $bookings] = $api->showForWeb($id);

        return view('admin.drivers.show', compact('driver', 'bookings'));
    }

    public function toggle(string $id, ApiDriver $api)
    {
        $result = $this->tryProxyApi(fn() => $api->toggle($id));

        if (!($result['success'] ?? false)) {
            return back()->withErrors(['error' => $result['message'] ?? 'Gagal mengubah status driver.']);
        }

        $status = $result['data']['is_active'] ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Driver berhasil {$status}.");
    }
}
