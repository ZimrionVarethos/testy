<?php

namespace App\Http\Controllers\Pengguna;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BookingController as ApiBooking;
use App\Http\Traits\WebApiProxy;
use App\Services\BookingService;

class BookingController extends Controller
{
    use WebApiProxy;

    public function __construct(private BookingService $bookingService) {}

    public function index(ApiBooking $api)
    {
        $req = $this->makeApiRequest();
        $this->bookingService->autoCancelExpiredForUser((string) auth()->id());

        ['bookings' => $bookings, 'payments' => $payments] = $api->indexForWeb($req);

        return view('pengguna.bookings.index', compact('bookings', 'payments'));
    }

    public function show(string $id, ApiBooking $api)
    {
        $req = $this->makeApiRequest();

        // Auto-cancel via service jika perlu (business logic tetap di service)
        $this->bookingService->autoCancelExpiredForUser((string) auth()->id());

        ['booking' => $booking, 'payment' => $payment] = $api->showForWeb($req, $id);

        return view('pengguna.bookings.show', compact('booking'));
    }

    public function destroy(string $id, ApiBooking $api)
    {
        $req = $this->makeApiRequest([], ['reason' => 'Dibatalkan oleh pengguna.']);
        $this->proxyApi(fn() => $api->cancel($req, $id));

        return redirect()->route('bookings.index')->with('success', 'Pesanan dibatalkan.');
    }
}
