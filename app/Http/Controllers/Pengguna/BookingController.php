<?php

namespace App\Http\Controllers\Pengguna;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\BookingService;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookingService) {}

    public function index()
    {
        $userId = (string) Auth::id();

        // 🔧 Delegasi ke BookingService — logic sama dengan yang dipakai Api/BookingController
        $this->bookingService->autoCancelExpiredForUser($userId);

        $bookings = Booking::where('user.user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Preload payment — satu query untuk semua booking di halaman ini
        $bookingIds = $bookings->pluck('_id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        $payments = Payment::whereIn('booking_id', $bookingIds)
            ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_PENDING])
            ->get()
            ->keyBy('booking_id');

        return view('pengguna.bookings.index', compact('bookings', 'payments'));
    }

    public function show(string $id)
    {
        $booking = Booking::findOrFail($id);

        if (($booking->user['user_id'] ?? null) !== (string) Auth::id()) abort(403);

        // Auto-cancel on-the-fly jika deadline terlewat
        if ($booking->status === 'pending' && $booking->confirmationDeadline()->isPast()) {
            $payment    = Payment::activeForBooking((string) $booking->_id);
            $sudahBayar = $payment && $payment->isPaid();

            if (! $sudahBayar) {
                $booking->update([
                    'status'        => 'cancelled',
                    'cancelled_at'  => now(),
                    'cancel_reason' => 'Dibatalkan otomatis: melewati batas waktu pembayaran.',
                ]);
                $booking->refresh();
            }
        }

        // Expire payment on-the-fly
        $activePayment = Payment::activeForBooking((string) $booking->_id);
        if ($activePayment && $activePayment->isPending() && $activePayment->isExpired()) {
            $activePayment->update(['status' => Payment::STATUS_EXPIRED]);
            $activePayment = null;
        }

        return view('pengguna.bookings.show', compact('booking'));
    }

    public function destroy(string $id)
    {
        $booking = Booking::findOrFail($id);

        if (($booking->user['user_id'] ?? null) !== (string) Auth::id()) abort(403);

        try {
            $this->bookingService->cancelBooking($booking, 'Dibatalkan oleh pengguna.');
            return redirect()->route('bookings.index')->with('success', 'Pesanan dibatalkan.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
