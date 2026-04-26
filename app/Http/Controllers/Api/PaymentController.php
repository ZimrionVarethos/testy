<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(private BookingService $bookingService) {}

    /**
     * GET /api/v1/payments — daftar pembayaran milik user / semua (admin)
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Payment::orderBy('created_at', 'desc');

        // Pengguna hanya lihat milik sendiri
        if ($user->role === 'pengguna') {
            $query->where('user_id', (string) $user->_id);
        }

        $payments = $query->paginate((int) $request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $payments->map(fn($p) => $this->formatPayment($p)),
            'meta'    => [
                'current_page' => $payments->currentPage(),
                'last_page'    => $payments->lastPage(),
                'total'        => $payments->total(),
            ],
        ]);
    }


    public function createSnap(Request $request, string $bookingId): JsonResponse
    {
        \Midtrans\Config::$serverKey    = config('midtrans.server_key');
        \Midtrans\Config::$isProduction = config('midtrans.is_production');
        \Midtrans\Config::$isSanitized  = true;
        \Midtrans\Config::$is3ds        = true;
 
        $user    = $request->user();
        $booking = Booking::findOrFail($bookingId);
 
        // Pastikan booking milik user ini
        if (($booking->user['user_id'] ?? $booking->user_id) !== (string) $user->_id) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }
 
        if ($booking->status !== Booking::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan ini tidak bisa dibayar.',
            ], 422);
        }
 
        // Cek payment yang sudah ada
        $existing = Payment::activeForBooking($bookingId);
 
        if ($existing && $existing->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan ini sudah lunas.',
            ], 422);
        }
 
        // Reuse snap token yang masih valid
        if ($existing && $existing->isPending()
            && !$existing->isExpired()
            && !empty($existing->midtrans['snap_token'])) {
            return response()->json([
                'success' => true,
                'data'    => [
                    'snap_token' => $existing->midtrans['snap_token'],
                    'expired_at' => $existing->expired_at?->toIso8601String(),
                ],
            ]);
        }
 
        // Hitung deadline
        $expiredAt   = \Carbon\Carbon::parse($booking->created_at)->addMinutes(30);
        $minutesLeft = max(1, (int) now()->diffInMinutes($expiredAt, false));
 
        if ($expiredAt->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Batas waktu pembayaran sudah terlewat.',
            ], 422);
        }
 
        // Tandai payment lama expired kalau ada
        if ($existing && $existing->isPending() && $existing->isExpired()) {
            $existing->update(['status' => Payment::STATUS_EXPIRED]);
        }
 
        $orderId = 'PAY-' . $bookingId . '-' . time();
 
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
                'id'       => $bookingId,
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
            Log::error('Midtrans Snap token error (mobile)', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungi Midtrans.',
            ], 500);
        }
 
        $payment = new Payment();
        $payment->fill([
            'booking_id'   => $bookingId,
            'booking_code' => $booking->booking_code,
            'user_id'      => (string) $user->_id,
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
 
        return response()->json([
            'success' => true,
            'data'    => [
                'snap_token' => $snapToken,
                'expired_at' => $expiredAt->toIso8601String(),
            ],
        ]);
    }



    /**
     * POST /api/v1/payments/notification — Midtrans webhook
     * Route ini HARUS dikecualikan dari auth middleware.
     */
    public function notification(Request $request): JsonResponse
    {
        \Midtrans\Config::$serverKey    = config('midtrans.server_key');
        \Midtrans\Config::$isProduction = config('midtrans.is_production');

        try {
            $notification = new \Midtrans\Notification();
        } catch (\Throwable $e) {
            Log::error('Midtrans notification parse error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Bad notification'], 400);
        }

        $orderId           = $notification->order_id;
        $transactionStatus = $notification->transaction_status;
        $fraudStatus       = $notification->fraud_status;
        $paymentType       = $notification->payment_type;
        $transactionId     = $notification->transaction_id;

        Log::info('Midtrans webhook', compact('orderId', 'transactionStatus', 'fraudStatus'));

        $payment = Payment::where('midtrans.order_id', $orderId)->first();

        if (! $payment) {
            Log::warning('Midtrans webhook: payment not found', ['order_id' => $orderId]);
            return response()->json(['message' => 'Payment not found'], 404);
        }

        if ($payment->isPaid()) {
            return response()->json(['message' => 'Already processed']);
        }

        $newStatus = $this->resolvePaymentStatus($transactionStatus, $fraudStatus);

        $payment->update([
            'status'   => $newStatus,
            'method'   => $newStatus === Payment::STATUS_PAID ? $paymentType : $payment->method,
            'paid_at'  => $newStatus === Payment::STATUS_PAID ? now() : null,
            'midtrans' => array_merge($payment->midtrans ?? [], [
                'transaction_id'     => $transactionId,
                'transaction_status' => $transactionStatus,
                'fraud_status'       => $fraudStatus,
                'payment_type'       => $paymentType,
            ]),
        ]);

        // Setelah lunas → notifikasi admin via BookingService (shared logic)
        if ($newStatus === Payment::STATUS_PAID) {
            $booking = Booking::find($payment->booking_id);
            if ($booking && $booking->status === Booking::STATUS_PENDING) {
                $this->bookingService->notifyAdminAfterPayment($booking);
            }
        }

        return response()->json(['message' => 'OK']);
    }

    // ── Private helpers ────────────────────────────────────────

    private function resolvePaymentStatus(string $transactionStatus, ?string $fraudStatus): string
    {
        if (in_array($transactionStatus, ['settlement', 'capture'])) {
            if ($fraudStatus === 'challenge') return Payment::STATUS_PENDING;
            return Payment::STATUS_PAID;
        }

        return match ($transactionStatus) {
            'pending' => Payment::STATUS_PENDING,
            'deny'    => Payment::STATUS_FAILED,
            'expire'  => Payment::STATUS_EXPIRED,
            'cancel'  => Payment::STATUS_CANCELLED,
            default   => Payment::STATUS_PENDING,
        };
    }

    private function formatPayment(Payment $p): array
    {
        return [
            'id'           => (string) $p->_id,
            'booking_id'   => $p->booking_id,
            'booking_code' => $p->booking_code,
            'amount'       => $p->amount,
            'method'       => $p->method,
            'status'       => $p->status,
            'snap_token'   => $p->midtrans['snap_token'] ?? null,
            'expired_at'   => $p->expired_at,
            'paid_at'      => $p->paid_at,
            'created_at'   => $p->created_at?->toIso8601String(),
        ];
    }
}
