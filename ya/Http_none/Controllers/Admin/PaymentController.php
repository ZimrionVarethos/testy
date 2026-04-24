<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Carbon\Carbon;

class PaymentController extends Controller
{
    /**
     * Daftar semua payment dengan filter status.
     */
    public function index()
    {
        $status   = request('status');                  // filter opsional
        $search   = request('search');

        $query = Payment::orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%");
                  // MongoDB Atlas text search bisa ditambahkan di sini
            });
        }

        $payments = $query->paginate(15)->withQueryString();

        // Summary card
        $summary = [
            'total_paid'    => Payment::paid()->sum('amount'),
            'total_pending' => Payment::pending()->count(),
            'count_paid'    => Payment::paid()->count(),
        ];

        return view('admin.payments.index', compact('payments', 'summary'));
    }

    /**
     * Detail payment + booking terkait.
     */
    public function show(string $id)
    {
        $payment = Payment::findOrFail($id);
        $booking = Booking::find($payment->booking_id);

        return view('admin.payments.show', compact('payment', 'booking'));
    }
}