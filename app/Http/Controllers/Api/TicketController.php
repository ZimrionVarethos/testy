<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * GET /api/v1/tickets
     * Daftar tiket milik user
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
     * Detail tiket beserta reply
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
     * Buka tiket baru
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'booking_id' => 'required|string',
            'subject'    => 'required|string|max:255',
            'message'    => 'required|string|max:2000',
        ]);

        $booking = Booking::findOrFail($request->booking_id);
        $user    = $request->user();

        // Pastikan booking milik user ini
        if (($booking->user['user_id'] ?? null) !== (string) $user->_id) {
            abort(403, 'Booking ini bukan milik Anda.');
        }

        // Cek tiket aktif untuk booking ini
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
            'messages'     => [[
                'sender'     => 'user',
                'sender_id'  => (string) $user->_id,
                'message'    => $request->message,
                'created_at' => now()->toIso8601String(),
            ]],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tiket berhasil dibuka. Admin akan segera merespons.',
            'data'    => $this->ticketResource($ticket),
        ], 201);
    }

    /**
     * POST /api/v1/tickets/{id}/reply
     * User balas tiket
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

        $messages   = $ticket->messages ?? [];
        $messages[] = [
            'sender'     => 'user',
            'sender_id'  => (string) $user->_id,
            'message'    => $request->message,
            'created_at' => now()->toIso8601String(),
        ];

        $ticket->update([
            'messages' => $messages,
            'status'   => 'open', // reopen kalau sempat in_progress
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Balasan terkirim.',
            'data'    => $this->ticketResource($ticket->refresh(), withReplies: true),
        ]);
    }

    // ── Helper ────────────────────────────────────────────────────

    private function ticketResource(Ticket $t, bool $withReplies = false): array
    {
        $data = [
            'id'           => (string) $t->_id,
            'booking_id'   => $t->booking_id,
            'booking_code' => $t->booking_code,
            'subject'      => $t->subject,
            'status'       => $t->status,
            'created_at'   => $t->created_at?->toIso8601String(),
            'updated_at'   => $t->updated_at?->toIso8601String(),
        ];

        if ($withReplies) {
            $data['messages'] = $t->messages ?? [];
        }

        return $data;
    }
}