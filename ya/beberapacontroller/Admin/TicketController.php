<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    /** Daftar semua tiket, bisa filter by status */
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');

        $query = Ticket::orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // MongoDB tidak support orderByRaw → sort di PHP:
        // urgent dulu, lalu created_at desc (sudah di-order dari query)
        $all     = $query->get()->sortBy(fn($t) => $t->priority === 'urgent' ? 0 : 1)->values();
        $page    = (int) $request->query('page', 1);
        $perPage = 15;
        $tickets = new \Illuminate\Pagination\LengthAwarePaginator(
            $all->slice(($page - 1) * $perPage, $perPage)->values(),
            $all->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $counts = [
            'all'         => Ticket::count(),
            'open'        => Ticket::where('status', Ticket::STATUS_OPEN)->count(),
            'in_progress' => Ticket::where('status', Ticket::STATUS_IN_PROGRESS)->count(),
            'resolved'    => Ticket::where('status', Ticket::STATUS_RESOLVED)->count(),
            'closed'      => Ticket::where('status', Ticket::STATUS_CLOSED)->count(),
        ];

        return view('admin.tickets.index', compact('tickets', 'status', 'counts'));
    }

    /** Detail tiket */
    public function show(string $id)
    {
        $ticket = Ticket::findOrFail($id);

        // Auto-set ke in_progress saat admin membuka tiket open
        if ($ticket->status === Ticket::STATUS_OPEN) {
            $ticket->update(['status' => Ticket::STATUS_IN_PROGRESS]);
            $ticket->refresh();
        }

        return view('admin.tickets.show', compact('ticket'));
    }

    /** Admin balas tiket */
    public function reply(Request $request, string $id)
    {
        $request->validate([
            'message'     => 'required|string|max:2000',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $ticket = Ticket::findOrFail($id);

        $ticket->addReply('admin', Auth::user()->name, $request->message);

        if ($request->filled('admin_notes')) {
            $ticket->update(['admin_notes' => $request->admin_notes]);
        }

        // Notifikasi ke user
        Notification::send(
            $ticket->user_id,
            'Admin membalas tiket Anda',
            'Tiket "' . $ticket->subject . '" mendapat balasan baru dari admin.',
            'booking',
            (string) $ticket->_id,
            route('tickets.show', (string) $ticket->_id)
        );

        return back()->with('success', 'Balasan terkirim.');
    }

    /** Update status tiket */
    public function updateStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);

        $ticket     = Ticket::findOrFail($id);
        $oldStatus  = $ticket->status;
        $newStatus  = $request->status;

        $updates = ['status' => $newStatus];

        if ($newStatus === Ticket::STATUS_RESOLVED && $oldStatus !== Ticket::STATUS_RESOLVED) {
            $updates['resolved_at'] = now();
        }

        $ticket->update($updates);

        // Notifikasi ke user hanya jika status berubah jadi resolved/closed
        if (in_array($newStatus, [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])) {
            $label = $newStatus === Ticket::STATUS_RESOLVED ? 'diselesaikan' : 'ditutup';
            Notification::send(
                $ticket->user_id,
                'Tiket Anda ' . $label,
                'Tiket "' . $ticket->subject . '" telah ' . $label . ' oleh admin.',
                'booking',
                (string) $ticket->_id,
                route('tickets.show', (string) $ticket->_id)
            );
        }

        return back()->with('success', 'Status tiket diperbarui.');
    }
}