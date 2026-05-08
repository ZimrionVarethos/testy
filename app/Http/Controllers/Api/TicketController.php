<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    /**
     * GET /api/v1/tickets
     */
    public function index(Request $request): JsonResponse
    {
        $userId  = (string) $request->user()->_id;
        $tickets = Ticket::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $tickets->map(fn($t) => $this->ticketResource($t)),
            'meta'    => [
                'current_page' => $tickets->currentPage(),
                'last_page'    => $tickets->lastPage(),
                'total'        => $tickets->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/tickets/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);

        if ($ticket->user_id !== (string) $request->user()->_id) {
            abort(403);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->ticketResource($ticket, withReplies: true),
        ]);
    }

    /**
     * POST /api/v1/tickets
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'booking_id' => 'required|string',
            'subject'    => 'required|string|max:255',
            'message'    => 'required|string|max:2000',
            'priority'   => 'nullable|in:normal,urgent',
        ]);

        $booking = Booking::findOrFail($request->booking_id);
        $user    = $request->user();

        if (($booking->user['user_id'] ?? null) !== (string) $user->_id) {
            abort(403, 'Booking ini bukan milik Anda.');
        }

        $existing = Ticket::where('booking_id', $request->booking_id)
            ->whereIn('status', ['open', 'in_progress'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Sudah ada tiket aktif untuk pesanan ini.',
                'data'    => $this->ticketResource($existing),
            ], 422);
        }

        $ticket = Ticket::create([
            'booking_id'   => $request->booking_id,
            'booking_code' => $booking->booking_code,
            'user_id'      => (string) $user->_id,
            'user_name'    => $user->name,
            'subject'      => $request->subject,
            'status'       => 'open',
            'priority'     => $request->input('priority', 'normal'),
            'replies'      => [[
                'sender_role' => 'pengguna',
                'sender_name' => $user->name,
                'sender_id'   => (string) $user->_id,
                'message'     => $request->message,
                'created_at'  => now()->toIso8601String(),
            ]],
        ]);

        // Notifikasi ke semua admin
        foreach (User::where('role', 'admin')->get() as $admin) {
            Notification::send(
                (string) $admin->_id,
                'Tiket Baru: ' . $ticket->subject,
                $user->name . ' membuka tiket untuk pesanan ' . $ticket->booking_code . '.',
                'ticket',
                (string) $ticket->_id,
                url('/admin/tickets/' . (string) $ticket->_id)
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Tiket berhasil dibuka. Admin akan segera merespons.',
            'data'    => $this->ticketResource($ticket, withReplies: true),
        ], 201);
    }

    /**
     * POST /api/v1/tickets/{id}/reply
     */
    public function reply(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $ticket = Ticket::findOrFail($id);
        $user   = $request->user();

        if ($ticket->user_id !== (string) $user->_id) {
            abort(403);
        }

        if ($ticket->status === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'Tiket sudah ditutup.',
            ], 422);
        }

        // sender_role harus 'pengguna' supaya web bisa bedain user vs admin
        $ticket->addReply('pengguna', $user->name, $request->message);

        // Jika sebelumnya resolved, kembalikan ke in_progress
        if ($ticket->status === Ticket::STATUS_RESOLVED) {
            $ticket->update(['status' => Ticket::STATUS_IN_PROGRESS]);
        }

        // Notifikasi ke semua admin
        foreach (User::where('role', 'admin')->get() as $admin) {
            Notification::send(
                (string) $admin->_id,
                'Balasan Tiket: ' . $ticket->subject,
                $user->name . ' membalas tiket #' . substr((string) $ticket->_id, -6) . '.',
                'ticket',
                (string) $ticket->_id,
                url('/admin/tickets/' . (string) $ticket->_id)
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Balasan terkirim.',
            'data'    => $this->ticketResource($ticket->refresh(), withReplies: true),
        ]);
    }

    // ── ForWeb (dipakai web controller langsung) ────────────────────────

    /** Untuk web: daftar tiket milik user */
    public function indexForWeb(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Ticket::where('user_id', (string) $request->user()->_id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    /** Untuk web: detail tiket (akses sudah dicek) */
    public function showForWeb(Request $request, string $id): Ticket
    {
        $ticket = Ticket::findOrFail($id);

        if ($ticket->user_id !== (string) $request->user()->_id) {
            abort(403);
        }

        return $ticket;
    }

    /** Untuk web: daftar tiket admin dengan priority sort + counts */
    public function adminIndexForWeb(Request $request): array
    {
        $status = $request->get('status', 'all');
        $query  = Ticket::orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $all = $query->get()->sortBy(fn($t) => $t->priority === 'urgent' ? 0 : 1)->values();

        $counts = [
            'all'         => Ticket::count(),
            'open'        => Ticket::where('status', Ticket::STATUS_OPEN)->count(),
            'in_progress' => Ticket::where('status', Ticket::STATUS_IN_PROGRESS)->count(),
            'resolved'    => Ticket::where('status', Ticket::STATUS_RESOLVED)->count(),
            'closed'      => Ticket::where('status', Ticket::STATUS_CLOSED)->count(),
        ];

        return compact('all', 'counts', 'status');
    }

    /** Untuk web: detail tiket admin (open → in_progress) + return Eloquent */
    public function adminShowForWeb(string $id): Ticket
    {
        $ticket = Ticket::findOrFail($id);

        if ($ticket->status === Ticket::STATUS_OPEN) {
            $ticket->update(['status' => Ticket::STATUS_IN_PROGRESS]);
            $ticket->refresh();
        }

        return $ticket;
    }

    // ── Admin methods ─────────────────────────────────────────────────

    /**
     * GET /api/v1/admin/tickets  (admin)
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $status = $request->get('status', 'all');
        $query  = Ticket::orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->get('per_page', 15), 50);
        $tickets = $query->paginate($perPage);

        $counts = [
            'all'         => Ticket::count(),
            'open'        => Ticket::where('status', Ticket::STATUS_OPEN)->count(),
            'in_progress' => Ticket::where('status', Ticket::STATUS_IN_PROGRESS)->count(),
            'resolved'    => Ticket::where('status', Ticket::STATUS_RESOLVED)->count(),
            'closed'      => Ticket::where('status', Ticket::STATUS_CLOSED)->count(),
        ];

        return response()->json([
            'success' => true,
            'data'    => $tickets->map(fn($t) => $this->ticketResource($t)),
            'counts'  => $counts,
            'meta'    => [
                'current_page' => $tickets->currentPage(),
                'last_page'    => $tickets->lastPage(),
                'total'        => $tickets->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/admin/tickets/{id}  (admin)
     */
    public function adminShow(string $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);

        if ($ticket->status === Ticket::STATUS_OPEN) {
            $ticket->update(['status' => Ticket::STATUS_IN_PROGRESS]);
            $ticket->refresh();
        }

        return response()->json([
            'success' => true,
            'data'    => $this->ticketResource($ticket, withReplies: true),
        ]);
    }

    /**
     * POST /api/v1/admin/tickets/{id}/reply  (admin)
     */
    public function adminReply(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'message'     => 'required|string|max:2000',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $ticket = Ticket::findOrFail($id);
        $ticket->addReply('admin', $request->user()->name, $request->message);

        if ($request->filled('admin_notes')) {
            $ticket->update(['admin_notes' => $request->admin_notes]);
        }

        Notification::send(
            $ticket->user_id,
            'Admin membalas tiket Anda',
            'Tiket "' . $ticket->subject . '" mendapat balasan baru dari admin.',
            'ticket',
            (string) $ticket->_id,
            url('/tickets/' . (string) $ticket->_id)
        );

        return response()->json([
            'success' => true,
            'message' => 'Balasan terkirim.',
            'data'    => $this->ticketResource($ticket->refresh(), withReplies: true),
        ]);
    }

    /**
     * PUT /api/v1/admin/tickets/{id}/status  (admin)
     */
    public function adminUpdateStatus(Request $request, string $id): JsonResponse
    {
        $request->validate(['status' => 'required|in:open,in_progress,resolved,closed']);

        $ticket    = Ticket::findOrFail($id);
        $oldStatus = $ticket->status;
        $newStatus = $request->status;
        $updates   = ['status' => $newStatus];

        if ($newStatus === Ticket::STATUS_RESOLVED && $oldStatus !== Ticket::STATUS_RESOLVED) {
            $updates['resolved_at'] = now();
        }

        $ticket->update($updates);

        if (in_array($newStatus, [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])) {
            $label = $newStatus === Ticket::STATUS_RESOLVED ? 'diselesaikan' : 'ditutup';
            Notification::send(
                $ticket->user_id,
                'Tiket Anda ' . $label,
                'Tiket "' . $ticket->subject . '" telah ' . $label . ' oleh admin.',
                'ticket',
                (string) $ticket->_id,
                url('/tickets/' . (string) $ticket->_id)
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Status tiket diperbarui.',
            'data'    => $this->ticketResource($ticket->refresh()),
        ]);
    }

    // ── Helper ────────────────────────────────────────────────────────

    private function ticketResource(Ticket $t, bool $withReplies = false): array
    {
        $data = [
            'id'           => (string) $t->_id,
            'booking_id'   => $t->booking_id,
            'booking_code' => $t->booking_code,
            'subject'      => $t->subject,
            'status'       => $t->status,
            'status_label' => $t->statusLabel(),
            'created_at'   => $t->created_at?->toIso8601String(),
            'updated_at'   => $t->updated_at?->toIso8601String(),
        ];

        if ($withReplies) {
            // Map replies ke format messages yang diharapkan Android
            $data['messages'] = collect($t->replies ?? [])->map(fn($r) => [
                'sender'      => $r['sender_role'] ?? 'user',
                'sender_id'   => $r['sender_id']   ?? null,
                'sender_name' => $r['sender_name']  ?? null,
                'message'     => $r['message'],
                'created_at'  => $r['created_at'],
            ])->values()->toArray();
        }

        return $data;
    }
}