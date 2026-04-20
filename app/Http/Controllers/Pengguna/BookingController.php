<?php namespace App\Http\Controllers\Pengguna;
use App\Http\Controllers\Controller;
use App\Models\Booking; use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function index()
    {
        $userId   = (string) Auth::id();
        $bookings = Booking::where('user.user_id', $userId)->orderBy('created_at', 'desc')->paginate(10);
        return view('pengguna.bookings.index', compact('bookings'));
    }

    public function show(string $id)
    {
        $booking = Booking::findOrFail($id);
    
        if (($booking->user['user_id'] ?? null) !== (string) Auth::id()) abort(403);
    
        // ← TAMBAH: expire payment on-the-fly saat halaman dibuka
        $activePayment = \App\Models\Payment::activeForBooking((string) $booking->_id);
        if ($activePayment && $activePayment->isPending() && $activePayment->isExpired()) {
            $activePayment->update(['status' => \App\Models\Payment::STATUS_EXPIRED]);
            $activePayment = null;
        }
    
        return view('pengguna.bookings.show', compact('booking'));
    }

    public function destroy(string $id)
    {
        $booking = Booking::findOrFail($id);
        if ($booking->user['user_id'] !== (string) Auth::id()) abort(403);
        if (in_array($booking->status, ['ongoing', 'completed'])) {
            return back()->withErrors(['error' => 'Pesanan tidak bisa dibatalkan.']);
        }
        $booking->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancel_reason' => 'Dibatalkan oleh pengguna.']);
        return redirect()->route('bookings.index')->with('success', 'Pesanan dibatalkan.');
    }
}