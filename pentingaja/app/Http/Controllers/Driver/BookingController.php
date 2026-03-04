<?php

// ─────────────────────────────────────────────────────────────

namespace App\Http\Controllers\Driver;
use App\Http\Controllers\Controller;
use App\Models\Booking; use App\Models\User;
use App\Services\BookingService;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookingService) {}

    public function available()
    {
        $bookings = Booking::where('status', 'pending')->orderBy('created_at', 'asc')->paginate(10);
        return view('driver.bookings.available', compact('bookings'));
    }

    public function index()
    {
        $driverId = (string) Auth::id();
        $bookings = Booking::where('driver.driver_id', $driverId)->orderBy('created_at', 'desc')->paginate(10);
        return view('driver.bookings.index', compact('bookings'));
    }

    public function show(string $id)
    {
        $booking = Booking::findOrFail($id);
        return view('driver.bookings.show', compact('booking'));
    }

    public function accept(string $id)
    {
        try {
            $booking = $this->bookingService->driverAcceptBooking($id, Auth::user());
            return redirect()->route('driver.bookings.show', $booking->_id)->with('success', 'Berhasil mengambil pesanan!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function toggleAvailability()
    {
        $driver = Auth::user();
        $current = $driver->driver_profile['is_available'] ?? false;
        $driver->update(['driver_profile.is_available' => !$current]);
        return back()->with('success', 'Status ketersediaan diperbarui.');
    }
}
