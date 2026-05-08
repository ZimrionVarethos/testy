<?php

namespace App\Http\Controllers\Pengguna;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\PaymentController as ApiPayment;
use App\Http\Traits\WebApiProxy;
use App\Models\Booking;

class PaymentController extends Controller
{
    use WebApiProxy;

    public function index(ApiPayment $api)
    {
        $req      = $this->makeApiRequest(['per_page' => 10]);
        $payments = $api->indexForWeb($req);

        return view('pengguna.payments.index', compact('payments'));
    }

    public function show(string $id, ApiPayment $api)
    {
        $req = $this->makeApiRequest();
        ['payment' => $payment, 'booking' => $booking] = $api->showForWeb($req, $id);

        return view('pengguna.payments.show', compact('payment', 'booking'));
    }

    public function createSnap(string $bookingId, ApiPayment $api)
    {
        $req     = $this->makeApiRequest();
        $booking = $api->bookingForSnapForWeb($req, $bookingId);

        if ($booking->status !== Booking::STATUS_PENDING) {
            return redirect()->route('bookings.show', $bookingId)
                ->with('info', 'Pesanan ini sudah ' . $booking->statusLabel() . '.');
        }

        $result = $this->tryProxyApi(fn() => $api->createSnap($req, $bookingId));

        if (!($result['success'] ?? false)) {
            $fresh = $api->bookingForSnapForWeb($req, $bookingId);
            if ($fresh->status === 'cancelled') {
                return redirect()->route('bookings.index')
                    ->with('error', $result['message'] ?? 'Batas waktu pembayaran sudah terlewat. Pesanan dibatalkan.');
            }
            return back()->with('error', $result['message'] ?? 'Terjadi kesalahan.');
        }

        $snapToken = $result['data']['snap_token'];
        $expiredAt = $result['data']['expired_at'];
        $payment   = $api->activePaymentForWeb((string) $booking->_id);

        return view('pengguna.payments.snap', [
            'payment'    => $payment,
            'booking'    => $booking,
            'snap_token' => $snapToken,
            'client_key' => config('midtrans.client_key'),
            'expired_at' => $expiredAt,
        ]);
    }

    public function finish(string $paymentId, ApiPayment $api)
    {
        $req = $this->makeApiRequest();
        ['payment' => $payment] = $api->showForWeb($req, $paymentId);

        return view('pengguna.payments.finish', compact('payment'));
    }
}
