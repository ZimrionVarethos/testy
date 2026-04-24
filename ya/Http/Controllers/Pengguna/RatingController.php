<?php

namespace App\Http\Controllers\Pengguna;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{
    public function store(Request $request, string $bookingId)
    {
        $request->validate([
            'score'   => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $booking = Booking::findOrFail($bookingId);

        // Hanya pemilik booking
        if (($booking->user['user_id'] ?? null) !== (string) Auth::id()) {
            abort(403);
        }

        // Hanya booking yang completed
        if ($booking->status !== 'completed') {
            return back()->withErrors(['error' => 'Rating hanya bisa diberikan untuk pesanan yang sudah selesai.']);
        }

        // 1 rating per booking
        if (Rating::existsForBooking((string) $booking->_id)) {
            return back()->withErrors(['error' => 'Pesanan ini sudah dirating.']);
        }

        // Driver harus ada
        $driverId = $booking->driver['driver_id'] ?? null;
        if (!$driverId) {
            return back()->withErrors(['error' => 'Data driver tidak ditemukan.']);
        }

        Rating::create([
            'booking_id' => (string) $booking->_id,
            'driver_id'  => (string) $driverId,
            'user_id'    => (string) Auth::id(),
            'score'      => (int) $request->score,
            'comment'    => $request->comment,
        ]);

        return back()->with('success', 'Terima kasih atas penilaian Anda!');
    }
}