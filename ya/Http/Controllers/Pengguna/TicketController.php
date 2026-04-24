<?php

namespace App\Http\Controllers\Pengguna;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Ticket;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    /** Daftar tiket milik user */
    public function index()
    {
        $tickets = Ticket::where('user_id', (string) Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('pengguna.tickets.index', compact('tickets'));
    }

    /** Form buat tiket baru, pre-fill dari booking */
    public function create(string $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);

        if (($booking->user['user_id'] ?? null) !== (string) Auth::id()) {
            abort(403);
        }

        // Hanya boleh buat tiket jika sudah bayar
        $activePayment = \App\Models\Payment::activeForBooking((string) $booking->_id);
        if (!$activePayment || !$activePayment->isPaid()) {
            return redirect()->route('bookings.show', $bookingId)
                ->withErrors(['error' => 'Tiket hanya bisa dibuat untuk pesanan yang sudah dibayar.']);
        }

        return view('pengguna.tickets.create', compact('booking'));
    }

    /** Simpan tiket baru */
    public function store(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|string',
            'subject'    => 'required|string|max:200',
            'message'    => 'required|string|max:2000',
            'priority'   => 'required|in:normal,urgent',
        ]);

        $booking = Booking::findOrFail($request->booking_id);

        if (($booking->user['user_id'] ?? null) !== (string) Auth::id()) {
            abort(403);
        }

        $user   = Auth::user();
        $ticket = Ticket::create([
            'booking_id'   => (string) $booking->_id,
            'booking_code' => $booking->booking_code,
            'user_id'      => (string) $user->id,
            'user_name'    => $user->name,
            'subject'      => $request->subject,
            'message'      => $request->message,
            'status'       => Ticket::STATUS_OPEN,
            'priority'     => $request->priority,
            'replies'      => [],
        ]);

        // Notifikasi ke semua admin
        $this->notifyAdmins($ticket, $booking);

        return redirect()->route('tickets.show', (string) $ticket->_id)
            ->with('success', 'Tiket berhasil dibuat. Admin akan segera merespons.');
    }

    /** Detail tiket */
    public function show(string $id)
    {
        $ticket = Ticket::findOrFail($id);

        if ($ticket->user_id !== (string) Auth::id()) {
            abort(403);
        }

        return view('pengguna.tickets.show', compact('ticket'));
    }

    /** User balas tiket (reply) */
    public function reply(Request $request, string $id)
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $ticket = Ticket::findOrFail($id);

        if ($ticket->user_id !== (string) Auth::id()) {
            abort(403);
        }

        if (!$ticket->isOpen()) {
            return back()->withErrors(['error' => 'Tiket sudah ditutup, tidak bisa dibalas.']);
        }

        $ticket->addReply('pengguna', Auth::user()->name, $request->message);

        // Jika sebelumnya resolved, kembalikan ke in_progress
        if ($ticket->status === Ticket::STATUS_RESOLVED) {
            $ticket->update(['status' => Ticket::STATUS_IN_PROGRESS]);
        }

        // Notifikasi ke semua admin
        $this->notifyAdmins($ticket, null, isReply: true);

        return back()->with('success', 'Balasan terkirim.');
    }

    // ── Private helpers ──────────────────────────────────────

    private function notifyAdmins(Ticket $ticket, ?Booking $booking = null, bool $isReply = false): void
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            if ($isReply) {
                Notification::send(
                    (string) $admin->_id,
                    'Balasan Tiket: ' . $ticket->subject,
                    $ticket->user_name . ' membalas tiket #' . substr((string) $ticket->_id, -6) . '.',
                    'booking',
                    (string) $ticket->_id,
                    route('admin.tickets.show', (string) $ticket->_id)
                );
            } else {
                Notification::send(
                    (string) $admin->_id,
                    'Tiket Baru: ' . $ticket->subject,
                    $ticket->user_name . ' membuka tiket untuk pesanan ' . $ticket->booking_code . '.',
                    'booking',
                    (string) $ticket->_id,
                    route('admin.tickets.show', (string) $ticket->_id)
                );
            }
        }
    }
}