<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Daftar pembayaran — Admin only.
     */
    public function index(Request $request): JsonResponse
    {
        $payments = Payment::orderBy('created_at', 'desc')
            ->paginate((int) $request->get('per_page', 15));

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
     * Midtrans Webhook — dipanggil Midtrans server setelah transaksi.
     * Route: POST /api/v1/payments/notification  (publik, tanpa auth)
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

        Log::info('Midtrans webhook', compact('orderId', 'transactionStatus', 'fraudStatus', 'paymentType'));

        // Cari payment by midtrans.order_id
        $payment = Payment::where('midtrans.order_id', $orderId)->first();

        if (!$payment) {
            Log::warning('Midtrans webhook: payment not found', ['order_id' => $orderId]);
            return response()->json(['message' => 'Payment not found'], 404);
        }

        // Skip jika sudah paid
        if ($payment->isPaid()) {
            return response()->json(['message' => 'Already processed']);
        }

        // Tentukan status baru
        $newStatus = $this->resolvePaymentStatus($transactionStatus, $fraudStatus);

        // Update Payment
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

        // Flow A: payment lunas → booking pending jadi confirmed (siap diproses admin)
        if ($newStatus === Payment::STATUS_PAID) {
            $this->confirmBookingAfterPayment($payment->booking_id);
        }

        return response()->json(['message' => 'OK']);
    }

    // ── Private helpers ──────────────────────────────────────

    /**
     * Flow A: setelah lunas, booking pending → confirmed.
     * Admin tinggal assign driver, tidak perlu approve manual lagi.
     */
    private function confirmBookingAfterPayment(string $bookingId): void
    {
        $booking = Booking::find($bookingId);

        if (!$booking) {
            Log::warning('confirmBookingAfterPayment: booking not found', ['id' => $bookingId]);
            return;
        }

        // Jangan auto-confirm — biarkan tetap pending
        // Admin yang akan confirm sekaligus assign driver
        // Cukup tandai payment_status dan notif admin
        if ($booking->status === Booking::STATUS_PENDING) {
            // tidak perlu update booking sama sekali, status tetap pending
        
            $admins = \App\Models\User::where('role', 'admin')
                ->pluck('_id')
                ->map(fn($id) => (string) $id)
                ->toArray();
        
            \App\Models\Notification::sendToMany(
                $admins,
                'Pesanan Baru Perlu Diproses',
                "Pesanan {$booking->booking_code} sudah dibayar. Silakan assign driver.",
                'booking',
                (string) $booking->_id,
                route('admin.bookings.show', $booking->_id),
            );
        }
    }

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
            'paid_at'      => $p->paid_at,
            'created_at'   => $p->created_at,
        ];
    }
}