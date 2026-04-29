<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ChatMessage; // ← ganti dari Message
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * GET /api/v1/bookings/{id}/messages
     * Ambil semua pesan dalam chat room booking ini
     */
    public function index(Request $request, string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        $user    = $request->user();

        $this->authorizeChat($user, $booking);

        // Pakai method forBooking() yang sudah ada di model ChatMessage
        $messages = ChatMessage::forBooking($id)
            ->map(fn($m) => $this->messageResource($m));

        // Tandai pesan lawan bicara sebagai sudah dibaca
        ChatMessage::markReadForBooking($id, $user->role === 'driver' ? 'driver' : 'pengguna');

        return response()->json([
            'success' => true,
            'data'    => $messages,
        ]);
    }

    /**
     * POST /api/v1/bookings/{id}/messages
     * Kirim pesan baru
     */
    public function store(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $booking = Booking::findOrFail($id);
        $user    = $request->user();

        $this->authorizeChat($user, $booking);

        $senderRole = match($user->role) {
            'driver'   => 'driver',
            'pengguna' => 'pengguna',
            default    => 'pengguna',
        };

        $message = ChatMessage::create([
            'booking_id'  => $id,
            'sender_id'   => (string) $user->_id,
            'sender_name' => $user->name,
            'sender_role' => $senderRole,
            'message'     => $request->message,
            'is_read'     => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pesan terkirim.',
            'data'    => $this->messageResource($message),
        ], 201);
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function authorizeChat(mixed $user, Booking $booking): void
    {
        if ($user->role === 'admin') return;

        if ($user->role === 'driver'
            && ($booking->driver['driver_id'] ?? null) === (string) $user->_id) return;

        if ($user->role === 'pengguna'
            && ($booking->user['user_id'] ?? null) === (string) $user->_id) return;

        abort(403, 'Anda tidak memiliki akses ke chat ini.');
    }

    private function messageResource(ChatMessage $m): array
    {
        return [
            'id'          => (string) $m->_id,
            'booking_id'  => $m->booking_id,
            'sender_id'   => $m->sender_id,
            'sender_name' => $m->sender_name,
            'sender_role' => $m->sender_role,
            'message'     => $m->message,
            'is_read'     => (bool) $m->is_read,
            'created_at'  => $m->created_at?->toIso8601String(),
        ];
    }
}
