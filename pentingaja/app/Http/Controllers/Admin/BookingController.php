<?php
// File: app/Http/Controllers/Adminbookingcontroller.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookingService) {}

    public function index(Request $request)
    {
        $status   = $request->query('status');
        $search   = $request->query('search');
        $query    = Booking::orderBy('created_at', 'desc');

        if ($status) $query->where('status', $status);
        if ($search) $query->where('booking_code', 'like', "%{$search}%");

        $bookings = $query->paginate(15);
        return view('admin.bookings.index', compact('bookings', 'status', 'search'));
    }

    public function show(string $id)
    {
        $booking = Booking::findOrFail($id);
        return view('admin.bookings.show', compact('booking'));
    }

    public function confirm(string $id)
    {
        try {
            $this->bookingService->adminConfirmBooking($id);
            return back()->with('success', 'Pesanan berhasil dikonfirmasi.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function cancel(Request $request, string $id)
    {
        $booking = Booking::findOrFail($id);
        try {
            $this->bookingService->cancelBooking($booking, $request->input('reason', 'Dibatalkan oleh admin.'));
            return back()->with('success', 'Pesanan dibatalkan.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}