<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\DashboardController as ApiDashboard;
use App\Http\Traits\WebApiProxy;

class MapsController extends Controller
{
    use WebApiProxy;

    public function index(ApiDashboard $api)
    {
        ['vehicles' => $vehicles, 'stats' => $stats] = $api->mapsIndexForWeb();

        return view('admin.maps.index', compact('vehicles', 'stats'));
    }

    public function show(string $id, ApiDashboard $api)
    {
        ['vehicle' => $vehicle, 'activeBooking' => $activeBooking] = $api->mapsShowForWeb($id);

        return view('admin.maps.show', compact('vehicle', 'activeBooking'));
    }
}
