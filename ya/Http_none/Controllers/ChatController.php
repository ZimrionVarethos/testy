<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    // ── PENGGUNA ─────────────────────────────────────────────

    /** GET /bookings/{id}/messages — polling endpoint untuk pengguna */
    public function index(string $id)
    {
        $booking = Booking::findOrFail($id);
        $this->authorizeUser($booking);

        ChatMessage::markReadForBooking((string) $booking->_id, 'pengguna');

        $messages = ChatMessage::forBooking((string) $booking->_id);

        return response()->json([
            'messages'  => $messages->map(fn($m) => $this->formatMessage($m)),
            'unread'    => 0, // sudah di-mark read di atas
        ]);
    }

    /** POST /bookings/{id}/messages — kirim pesan dari pengguna */
    public function store(Request $request, string $id)
    {
        $request->validate(['message' => 'required|string|max:1000']);

        $booking = Booking::findOrFail($id);
        $this->authorizeUser($booking);
        $this->authorizeChatStatus($booking);

        $msg = ChatMessage::create([
            'booking_id'  => (string) $booking->_id,
            'sender_id'   => (string) Auth::id(),
            'sender_name' => Auth::user()->name,
            'sender_role' => 'pengguna',
            'message'     => $request->message,
            'is_read'     => false,
        ]);

        return response()->json(['message' => $this->formatMessage($msg)]);
    }

    // ── DRIVER ───────────────────────────────────────────────

    /** GET /driver/bookings/{id}/messages — polling endpoint untuk driver */
    public function driverIndex(string $id)
    {
        $booking = Booking::findOrFail($id);
        $this->authorizeDriver($booking);

        ChatMessage::markReadForBooking((string) $booking->_id, 'driver');

        $messages = ChatMessage::forBooking((string) $booking->_id);

        return response()->json([
            'messages' => $messages->map(fn($m) => $this->formatMessage($m)),
            'unread'   => 0,
        ]);
    }

    /** POST /driver/bookings/{id}/messages — kirim pesan dari driver */
    public function driverStore(Request $request, string $id)
    {
        $request->validate(['message' => 'required|string|max:1000']);

        $booking = Booking::findOrFail($id);
        $this->authorizeDriver($booking);
        $this->authorizeChatStatus($booking);

        $msg = ChatMessage::create([
            'booking_id'  => (string) $booking->_id,
            'sender_id'   => (string) Auth::id(),
            'sender_name' => Auth::user()->name,
            'sender_role' => 'driver',
            'message'     => $request->message,
            'is_read'     => false,
        ]);

        return response()->json(['message' => $this->formatMessage($msg)]);
    }

    // ── Private helpers ──────────────────────────────────────

    private function authorizeUser(Booking $booking): void
    {
        if (($booking->user['user_id'] ?? null) !== (string) Auth::id()) {
            abort(403);
        }
    }

    private function authorizeDriver(Booking $booking): void
    {
        if (($booking->driver['driver_id'] ?? null) !== (string) Auth::id()) {
            abort(403);
        }
    }

    private function authorizeChatStatus(Booking $booking): void
    {
        if (!in_array($booking->status, ['confirmed', 'ongoing'])) {
            abort(422, 'Chat hanya tersedia untuk pesanan yang sudah dikonfirmasi.');
        }
    }

    private function formatMessage(ChatMessage $m): array
    {
        return [
            'id'          => (string) $m->_id,
            'sender_id'   => $m->sender_id,
            'sender_name' => $m->sender_name,
            'sender_role' => $m->sender_role,
            'message'     => $m->message,
            'is_read'     => $m->is_read,
            'time'        => $m->created_at->format('H:i'),
            'date'        => $m->created_at->format('d M Y'),
            'created_at'  => $m->created_at->toIso8601String(),
        ];
    }
}