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

        if (($booking->user['user_id'] ?? null) !== $userId) abort(403);

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return redirect()->route('bookings.show', $bookingId)
                ->with('error', 'Pesanan ini tidak bisa dibayar.');
        }

        $existing = Payment::activeForBooking((string) $booking->_id);

        // Sudah lunas
        if ($existing && $existing->isPaid()) {
            return redirect()->route('bookings.show', $bookingId)
                ->with('info', 'Pesanan ini sudah lunas.');
        }

        // ── BARU: payment pending tapi sudah expired dari sisi sistem ──
        if ($existing && $existing->isPending() && $existing->isExpired()) {
            // Tandai expired, lalu buat payment baru di bawah
            $existing->update(['status' => Payment::STATUS_EXPIRED]);
            $existing = null;
        }

        // ── BARU: hitung deadline bayar = min(created_at+24jam, start_date) ──
        // Hitung deadline bayar
        $expiredAt = \Carbon\Carbon::parse($booking->created_at)->addHours(24);
        $startDate = \Carbon\Carbon::parse($booking->start_date);

        if ($startDate->lt($expiredAt)) {
            $expiredAt = $startDate;
        }

        // Jika deadline sudah lewat, tolak
        if ($expiredAt->isPast()) {
            return redirect()->route('bookings.show', $bookingId)
                ->with('error', 'Batas waktu pembayaran sudah terlewat.');
        }

        // Reuse snap token jika masih pending, belum expired, dan token ada
        if ($existing && $existing->isPending()
            && !$existing->isExpired()
            && !empty($existing->midtrans['snap_token'])) {
            return view('pengguna.payments.snap', [
                'payment'    => $existing,
                'booking'    => $booking,
                'snap_token' => $existing->midtrans['snap_token'],
                'client_key' => config('midtrans.client_key'),
                'expired_at' => $existing->expired_at,  // ← kirim ke view
            ]);
        }

        // Buat Snap token baru
        $orderId = 'PAY-' . (string) $booking->_id . '-' . time();
        $user    = Auth::user();

        // ── DIUPDATE: minimum 30 menit, maksimal sesuai deadline ──
        $minutesLeft = (int) \Carbon\Carbon::now()->diffInMinutes($expiredAt, false);

        if ($minutesLeft < 30) {
            // Deadline terlalu mepet — beri minimal 30 menit
            // tapi jangan lebih dari original deadline
            $minutesLeft = 30;
            // Geser expired_at ikut
            $expiredAt = \Carbon\Carbon::now()->addMinutes(30);
        }

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
            'item_details' => [[
                'id'       => (string) $booking->_id,
                'price'    => (int) $booking->total_price,
                'quantity' => 1,
                'name'     => 'Sewa ' . ($booking->vehicle['name'] ?? 'Kendaraan')
                               . ' (' . $booking->booking_code . ')',
            ]],
            // ── BARU: sinkronkan expiry Midtrans dengan expiry sistem ──
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit'       => 'minutes',
                'duration'   => $minutesLeft,
            ],
        ];

        try {
            $snapToken = \Midtrans\Snap::getSnapToken($params);
        } catch (\Throwable $e) {
            Log::error('Midtrans Snap token error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal menghubungi Midtrans: ' . $e->getMessage());
        }

        $payment = $existing ?? new Payment();
        $payment->fill([
            'booking_id'   => (string) $booking->_id,
            'booking_code' => $booking->booking_code,
            'user_id'      => $userId,
            'amount'       => (int) $booking->total_price,
            'method'       => 'snap',
            'status'       => Payment::STATUS_PENDING,
            'expired_at'   => $expiredAt,  // ← BARU: simpan deadline
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
            'expired_at' => $expiredAt,  // ← kirim ke view
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