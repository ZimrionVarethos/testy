<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BookingController as ApiBooking;
use App\Http\Traits\WebApiProxy;

class BookingController extends Controller
{
    use WebApiProxy;

    public function index(ApiBooking $api)
    {
        $req      = $this->makeApiRequest(['per_page' => 10]);
        $bookings = $api->driverIndexForWeb($req);

        return view('driver.bookings.index', compact('bookings'));
    }

    public function show(string $id, ApiBooking $api)
    {
        $req = $this->makeApiRequest();
        ['booking' => $booking, 'canPickup' => $canPickup] = $api->driverShowForWeb($req, $id);

        return view('driver.bookings.show', compact('booking', 'canPickup'));
    }

    public function markPickup(string $id, ApiBooking $api)
    {
        $req    = $this->makeApiRequest();
        $result = $this->tryProxyApi(fn() => $api->pickup($req, $id));

        if (!($result['success'] ?? false)) {
            return back()->withErrors(['error' => $result['message'] ?? 'Terjadi kesalahan.']);
        }

        return redirect()
            ->route('driver.bookings.show', $id)
            ->with('success', 'Status perjalanan diperbarui menjadi Sedang Berjalan.');
    }
}
