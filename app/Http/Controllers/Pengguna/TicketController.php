<?php

namespace App\Http\Controllers\Pengguna;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BookingController as ApiBooking;
use App\Http\Controllers\Api\TicketController as ApiTicket;
use App\Http\Traits\WebApiProxy;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    use WebApiProxy;

    public function index(ApiTicket $api)
    {
        $req     = $this->makeApiRequest();
        $tickets = $api->indexForWeb($req);

        return view('pengguna.tickets.index', compact('tickets'));
    }

    public function create(string $bookingId, ApiBooking $apiBooking)
    {
        $req = $this->makeApiRequest();
        ['booking' => $booking, 'payment' => $payment] = $apiBooking->showForWeb($req, $bookingId);

        if (!$payment || !$payment->isPaid()) {
            return redirect()->route('bookings.show', $bookingId)
                ->withErrors(['error' => 'Tiket hanya bisa dibuat untuk pesanan yang sudah dibayar.']);
        }

        return view('pengguna.tickets.create', compact('booking'));
    }

    public function store(Request $request, ApiTicket $api)
    {
        $req = $this->makeApiRequest([], $request->only(['booking_id', 'subject', 'message', 'priority']));

        $result   = $this->proxyApi(fn() => $api->store($req));
        $ticketId = $result['data']['id'];

        return redirect()->route('tickets.show', $ticketId)
            ->with('success', 'Tiket berhasil dibuat. Admin akan segera merespons.');
    }

    public function show(string $id, ApiTicket $api)
    {
        $req    = $this->makeApiRequest();
        $ticket = $api->showForWeb($req, $id);

        return view('pengguna.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, string $id, ApiTicket $api)
    {
        $req = $this->makeApiRequest([], ['message' => $request->message]);
        $this->proxyApi(fn() => $api->reply($req, $id));

        return back()->with('success', 'Balasan terkirim.');
    }
}
