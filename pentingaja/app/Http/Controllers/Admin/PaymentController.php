<?php
// ─────────────────────────────────────────────────────────────

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Booking; use Carbon\Carbon;

class PaymentController extends Controller
{
    public function index()
    {
        // Placeholder — ganti dengan model Payment setelah integrasi Midtrans
        $bookings = Booking::whereIn('status', ['confirmed', 'ongoing', 'completed'])
                           ->orderBy('confirmed_at', 'desc')->paginate(15);
        return view('admin.payments.index', compact('bookings'));
    }

    public function show(string $id)
    {
        $booking = Booking::findOrFail($id);
        return view('admin.payments.show', compact('booking'));
    }
}
