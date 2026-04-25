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
