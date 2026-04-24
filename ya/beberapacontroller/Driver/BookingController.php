<?php

/**
 * app/http/Controllers/Driver/BookingController.php
 */

namespace App\Http\Controllers\Driver;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;


class BookingController extends Controller
{
    /**
     * Daftar pesanan yang sudah di-assign ke driver ini.
     */
    public function index()
    {
        $driverId = (string) Auth::id();

        $bookings = Booking::where('driver.driver_id', $driverId)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('driver.bookings.index', compact('bookings'));
    }

    /**
     * Detail pesanan — hanya bisa dilihat driver yang bersangkutan.
     */
    public function show(string $id)
    {
        $booking = Booking::findOrFail($id);

        abort_if(
            ($booking->driver['driver_id'] ?? null) !== (string) Auth::id(),
            403,
            'Anda tidak punya akses ke pesanan ini.'
        );

        return view('driver.bookings.show', compact('booking'));
    }

    /**
     * Toggle status ketersediaan driver (is_available di driver_profile).
     */
    public function toggleAvailability()
    {
        $driver  = Auth::user();
        $current = $driver->driver_profile['is_available'] ?? false;
        $driver->update(['driver_profile.is_available' => !$current]);

        $label = $current ? 'tidak tersedia' : 'tersedia';
        return back()->with('success', "Status Anda sekarang: {$label}.");
    }
}