<?php

namespace App\Http\Controllers\Pengguna;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct()
    {
        \Midtrans\Config::$serverKey    = config('midtrans.server_key');
        \Midtrans\Config::$isProduction = config('midtrans.is_production');
        \Midtrans\Config::$isSanitized  = true;
        \Midtrans\Config::$is3ds        = true;
    }

    /**
     * Daftar riwayat pembayaran milik pengguna.
     */
    public function index()
    {
        $userId   = (string) Auth::id();
        $payments = Payment::where('user_id', $userId)
                           ->orderBy('created_at', 'desc')
                           ->paginate(10);

        return view('pengguna.payments.index', compact('payments'));
    }

    /**
     * Detail payment.
     */
    public function show(string $id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->user_id !== (string) Auth::id()) abort(403);

        $booking = Booking::findOrFail($payment->booking_id);

        return view('pengguna.payments.show', compact('payment', 'booking'));
    }

    /**
     * Flow A: Booking baru dibuat (status 'pending') langsung bisa bayar.
     * Booking 'confirmed' juga boleh bayar (kalau admin confirm duluan).
     * Buat Snap token lalu tampilkan halaman pembayaran.
     */
    public function createSnap(string $bookingId)
    {
        $userId  = (string) Auth::id();
        $booking = Booking::findOrFail($bookingId);

        // Pastikan booking milik pengguna ini
        if (($booking->user['user_id'] ?? null) !== $userId) abort(403);

        // Hanya pending/confirmed yang boleh bayar
        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return redirect()->route('bookings.show', $bookingId)
                             ->with('error', 'Pesanan ini tidak bisa dibayar (status: ' . $booking->status . ').');
        }

        // Sudah lunas → tidak perlu bayar lagi
        $existing = Payment::activeForBooking((string) $booking->_id);
        if ($existing && $existing->isPaid()) {
            return redirect()->route('bookings.show', $bookingId)
                             ->with('info', 'Pesanan ini sudah lunas.');
        }

        // Reuse snap token jika masih pending dan token ada
        if ($existing && $existing->isPending() && !empty($existing->midtrans['snap_token'])) {
            return view('pengguna.payments.snap', [
                'payment'    => $existing,
                'booking'    => $booking,
                'snap_token' => $existing->midtrans['snap_token'],
                'client_key' => config('midtrans.client_key'),
            ]);
        }

        // Buat Snap token baru ke Midtrans
        $orderId = 'PAY-' . (string) $booking->_id . '-' . time();
        $user    = Auth::user();

        $params = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => (int) $booking->total_price,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email'      => $user->email,
                'phone'      => $user->phone ?? '',
            ],
            'item_details' => [
                [
                    'id'       => (string) $booking->_id,
                    'price'    => (int) $booking->total_price,
                    'quantity' => 1,
                    'name'     => 'Sewa ' . ($booking->vehicle['name'] ?? 'Kendaraan') . ' (' . $booking->booking_code . ')',
                ],
            ],
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit'       => 'hours',
                'duration'   => 24,
            ],
        ];

        try {
            $snapToken = \Midtrans\Snap::getSnapToken($params);
            } catch (\Throwable $e) {
                Log::error('Midtrans Snap token error', ['error' => $e->getMessage()]);
                // DEBUG — hapus setelah ketemu masalahnya
                return back()->with('error', 'Midtrans error: ' . $e->getMessage());
            }

        // Simpan Payment baru
        $payment = $existing ?? new Payment();
        $payment->fill([
            'booking_id'   => (string) $booking->_id,
            'booking_code' => $booking->booking_code,
            'user_id'      => $userId,
            'amount'       => (int) $booking->total_price,
            'method'       => 'snap',
            'status'       => Payment::STATUS_PENDING,
            'midtrans'     => [
                'snap_token' => $snapToken,
                'order_id'   => $orderId,
            ],
        ]);
        $payment->save();

        return view('pengguna.payments.snap', [
            'payment'    => $payment,
            'booking'    => $booking,
            'snap_token' => $snapToken,
            'client_key' => config('midtrans.client_key'),
        ]);
    }

    /**
     * Finish callback setelah Snap — status sebenarnya diupdate via webhook.
     */
    public function finish(string $paymentId)
    {
        $payment = Payment::findOrFail($paymentId);

        if ($payment->user_id !== (string) Auth::id()) abort(403);

        $payment->refresh(); // webhook mungkin sudah update duluan

        return view('pengguna.payments.finish', compact('payment'));
    }
}