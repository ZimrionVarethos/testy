<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Daftar pembayaran (booking confirmed/ongoing/completed).
     */
    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::whereIn('status', ['confirmed', 'ongoing', 'completed'])
            ->orderBy('confirmed_at', 'desc')
            ->paginate((int) $request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $bookings->map(fn($b) => [
                'id'           => (string) $b->_id,
                'booking_code' => $b->booking_code,
                'user_name'    => $b->user['name'] ?? '-',
                'vehicle_name' => $b->vehicle['name'] ?? '-',
                'total_price'  => $b->total_price,
                'status'       => $b->status,
                'confirmed_at' => $b->confirmed_at,
            ]),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page'    => $bookings->lastPage(),
                'total'        => $bookings->total(),
            ],
        ]);
    }
}
