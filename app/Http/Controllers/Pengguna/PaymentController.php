<?php

namespace App\Http\Controllers\Pengguna;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(private BookingService $bookingService)
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
     * Buat Snap token dan tampilkan halaman pembayaran.
     * Hanya booking berstatus 'pending' yang boleh bayar.
     *
     * CATATAN: booking 'confirmed' tidak lagi bisa bayar di sini,
     * karena alur baru: user BAYAR DULU baru admin confirm+assign driver.
     */
    public function createSnap(string $bookingId)
    {
        $userId  = (string) Auth::id();
        $booking = Booking::findOrFail($bookingId);

        if (($booking->user['user_id'] ?? null) !== $userId) abort(403);

        // Hanya pending yang bisa bayar
        if ($booking->status !== Booking::STATUS_PENDING) {
            return redirect()->route('bookings.show', $bookingId)
                ->with('info', 'Pesanan ini sudah ' . $booking->statusLabel() . '.');
        }

        $existing = Payment::activeForBooking((string) $booking->_id);

        // Sudah lunas — tidak perlu bayar lagi
        if ($existing && $existing->isPaid()) {
            return redirect()->route('bookings.show', $bookingId)
                ->with('info', 'Pesanan ini sudah lunas. Menunggu konfirmasi admin.');
        }

        // Payment pending tapi sudah expired — tandai expired, buat baru
        if ($existing && $existing->isPending() && $existing->isExpired()) {
            $existing->update(['status' => Payment::STATUS_EXPIRED]);
            $existing = null;
        }

        // Hitung deadline bayar: 30 menit dari created_at booking
        // (sesuai confirmationDeadline di Booking model)
        $expiredAt = \Carbon\Carbon::parse($booking->created_at)->addMinutes(30);

        if ($expiredAt->isPast()) {
            // Booking juga otomatis cancel karena sudah lewat deadline
            $booking->update([
                'status'        => Booking::STATUS_CANCELLED,
                'cancelled_at'  => now(),
                'cancel_reason' => 'Dibatalkan otomatis: melewati batas waktu pembayaran.',
            ]);
            return redirect()->route('bookings.index')
                ->with('error', 'Batas waktu pembayaran sudah terlewat. Pesanan dibatalkan.');
        }

        // Reuse snap token yang masih valid
        if ($existing && $existing->isPending()
            && !$existing->isExpired()
            && !empty($existing->midtrans['snap_token'])) {
            return view('pengguna.payments.snap', [
                'payment'    => $existing,
                'booking'    => $booking,
                'snap_token' => $existing->midtrans['snap_token'],
                'client_key' => config('midtrans.client_key'),
                'expired_at' => $existing->expired_at,
            ]);
        }

        // Buat Snap token baru
        $minutesLeft = (int) now()->diffInMinutes($expiredAt, false);
        $minutesLeft = max(1, $minutesLeft); // minimal 1 menit untuk Midtrans
        $orderId     = 'PAY-' . (string) $booking->_id . '-' . time();
        $user        = Auth::user();

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
            'expired_at'   => $expiredAt,
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
            'expired_at' => $expiredAt,
        ]);
    }

    /**
     * Finish callback setelah Snap (redirect dari Midtrans).
     * Status TIDAK diupdate di sini — webhook yang handle.
     * Halaman ini hanya menampilkan status terkini setelah refresh.
     */
    public function finish(string $paymentId)
    {
        $payment = Payment::findOrFail($paymentId);
        if ($payment->user_id !== (string) Auth::id()) abort(403);

        $payment->refresh(); // webhook mungkin sudah update duluan
        return view('pengguna.payments.finish', compact('payment'));
    }

    /**
     * ── WEBHOOK dari Midtrans ─────────────────────────────────
     *
     * Ini satu-satunya tempat yang mengupdate status payment jadi 'paid'
     * dan memicu notifikasi ke admin agar pesanan bisa diproses.
     *
     * PENTING: Route webhook HARUS dikecualikan dari CSRF middleware.
     * Tambahkan di app/Http/Middleware/VerifyCsrfToken.php:
     *   protected $except = ['payments/webhook'];
     *
     * Route: POST /payments/webhook  (tanpa auth middleware)
     */
    public function webhook(Request $request)
    {
        // Verifikasi signature Midtrans
        $serverKey       = config('midtrans.server_key');
        $orderId         = $request->input('order_id');
        $statusCode      = $request->input('status_code');
        $grossAmount     = $request->input('gross_amount');
        $signatureKey    = $request->input('signature_key');

        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if ($signatureKey !== $expectedSignature) {
            Log::warning('Midtrans webhook: invalid signature', ['order_id' => $orderId]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $transactionStatus = $request->input('transaction_status');
        $fraudStatus       = $request->input('fraud_status');

        // Cari payment berdasarkan order_id yang tersimpan di midtrans.order_id
        $payment = Payment::where('midtrans.order_id', $orderId)->first();

        if (!$payment) {
            Log::warning('Midtrans webhook: payment not found', ['order_id' => $orderId]);
            return response()->json(['message' => 'Payment not found'], 404);
        }

        // Tentukan status berdasarkan kombinasi transaction_status + fraud_status
        $newStatus = $this->resolvePaymentStatus($transactionStatus, $fraudStatus);

        if (!$newStatus) {
            // Status yang tidak perlu diproses (mis. 'pending' dari Midtrans = masih menunggu)
            return response()->json(['message' => 'Status ignored: ' . $transactionStatus]);
        }

        // Update payment
        $payment->update([
            'status'  => $newStatus,
            'paid_at' => $newStatus === Payment::STATUS_PAID ? now() : null,
            'midtrans' => array_merge($payment->midtrans ?? [], [
                'transaction_status' => $transactionStatus,
                'payment_type'       => $request->input('payment_type'),
                'transaction_id'     => $request->input('transaction_id'),
                'fraud_status'       => $fraudStatus,
            ]),
        ]);

        Log::info('Midtrans webhook: payment updated', [
            'order_id'   => $orderId,
            'new_status' => $newStatus,
        ]);

        // ── Jika pembayaran berhasil, notifikasi admin ────────
        if ($newStatus === Payment::STATUS_PAID) {
            $booking = Booking::find($payment->booking_id);

            if ($booking && $booking->status === Booking::STATUS_PENDING) {
                // Notif ke admin: ada pesanan yang sudah dibayar, siap diproses
                $this->bookingService->notifyAdminAfterPayment($booking);
            }
        }

        return response()->json(['message' => 'OK']);
    }

    // ── Private helper ────────────────────────────────────────

    /**
     * Mapping transaction_status + fraud_status Midtrans ke status Payment internal.
     * Return null jika status tidak perlu diproses.
     */
    private function resolvePaymentStatus(string $transactionStatus, ?string $fraudStatus): ?string
    {
        return match (true) {
            // Berhasil
            $transactionStatus === 'capture' && $fraudStatus === 'accept'   => Payment::STATUS_PAID,
            $transactionStatus === 'settlement'                              => Payment::STATUS_PAID,

            // Gagal / ditolak
            $transactionStatus === 'deny'                                    => Payment::STATUS_FAILED,
            $transactionStatus === 'cancel'                                  => Payment::STATUS_CANCELLED,
            $transactionStatus === 'expire'                                  => Payment::STATUS_EXPIRED,
            $transactionStatus === 'failure'                                 => Payment::STATUS_FAILED,

            // Challenge (fraud review) — anggap pending dulu, bisa dioverride manual
            $transactionStatus === 'capture' && $fraudStatus === 'challenge' => null,

            // Pending dari Midtrans = masih menunggu user bayar, tidak perlu update
            default => null,
        };
    }
}