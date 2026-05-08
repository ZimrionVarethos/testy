<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BookingController as ApiBooking;
use App\Http\Traits\WebApiProxy;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use WebApiProxy;

    public function index(Request $request, ApiBooking $api)
    {
        $status   = $request->query('status');
        $search   = $request->query('search');
        $req      = $this->makeApiRequest(compact('status', 'search'));
        $bookings = $api->adminIndexForWeb($req);

        return view('admin.bookings.index', compact('bookings', 'status', 'search'));
    }

    public function show(string $id, ApiBooking $api)
    {
        $req = $this->makeApiRequest();
        ['booking' => $booking] = $api->showForWeb($req, $id);

        $availableDrivers = collect();

        if ($booking->status === Booking::STATUS_PENDING && empty($booking->driver['driver_id'])) {
            $result = $this->tryProxyApi(fn() => $api->availableDrivers($req, $id));
            if ($result['success'] ?? false) {
                $availableDrivers = collect($result['data'])->map(function ($d) {
                    $user = new User();
                    $user->forceFill([
                        '_id'              => $d['id'],
                        'name'             => $d['name'],
                        'phone'            => $d['phone'] ?? null,
                        'avatar'           => $d['avatar'] ?? null,
                        'active_schedules' => $d['active_schedules'] ?? [],
                    ]);
                    return $user;
                });
            }
        }

        return view('admin.bookings.show', compact('booking', 'availableDrivers'));
    }

    public function assignDriver(Request $request, string $id, ApiBooking $api)
    {
        $req    = $this->makeApiRequest([], ['driver_id' => $request->driver_id]);
        $result = $this->tryProxyApi(fn() => $api->assignDriver($req, $id));

        if (!($result['success'] ?? false)) {
            return back()->withErrors(['error' => $result['message'] ?? 'Gagal assign driver.']);
        }

        $driverName = $result['data']['driver']['name'] ?? 'Driver';
        return redirect()
            ->route('admin.bookings.show', $id)
            ->with('success', "Driver {$driverName} berhasil di-assign. Pesanan sekarang Confirmed.");
    }

    public function cancel(Request $request, string $id, ApiBooking $api)
    {
        $req    = $this->makeApiRequest([], ['reason' => $request->input('reason', 'Dibatalkan oleh admin.')]);
        $result = $this->tryProxyApi(fn() => $api->cancel($req, $id));

        if (!($result['success'] ?? false)) {
            return back()->withErrors(['error' => $result['message'] ?? 'Gagal membatalkan pesanan.']);
        }

        return back()->with('success', 'Pesanan dibatalkan.');
    }
}
