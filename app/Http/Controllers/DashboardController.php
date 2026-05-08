<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\DashboardController as ApiDashboard;
use App\Http\Traits\WebApiProxy;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use WebApiProxy;

    public function index(ApiDashboard $api)
    {
        return match (Auth::user()->role) {
            'admin'  => $this->adminDashboard($api),
            'driver' => $this->driverDashboard($api),
            default  => $this->penggunaDashboard($api),
        };
    }

    private function adminDashboard(ApiDashboard $api)
    {
        $data = $api->adminForWeb();

        return view('dashboard', [
            'stats'               => $data['stats'],
            'pendingPaidBookings' => $data['pendingPaidBookings'],
            'recentBookings'      => $data['recentBookings'],
            'vehicleStats'        => $data['vehicleStats'],
            'bookingTrend'        => $data['bookingTrend'],
            'revenueChart'        => $data['revenueChart'],
            'vehicleLocations'    => $data['vehicleLocations'],
        ]);
    }

    private function driverDashboard(ApiDashboard $api)
    {
        $req  = $this->makeApiRequest();
        $data = $api->driverForWeb($req);

        return view('dashboard', [
            'stats'            => $data['stats'],
            'myActiveBookings' => $data['myActiveBookings'],
            'notifications'    => $data['notifications'],
        ]);
    }

    private function penggunaDashboard(ApiDashboard $api)
    {
        $req  = $this->makeApiRequest();
        $data = $api->penggunaForWeb($req);

        return view('dashboard', [
            'stats'          => $data['stats'],
            'activeBookings' => $data['activeBookings'],
            'activePayments' => $data['activePayments'],
            'notifications'  => $data['notifications'],
        ]);
    }
}
