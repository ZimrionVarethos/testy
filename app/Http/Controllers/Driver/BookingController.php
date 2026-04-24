<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookingService) {}

    /**
     * Daftar semua pesanan yang di-assign ke driver ini.
     */
    public function index()
    {
        $driverId = (string) Auth::id();

        $bookings = Booking::where('driver.driver_id', $driverId)
            ->orderBy('start_date', 'desc')
            ->paginate(10);

        return view('driver.bookings.index', compact('bookings'));
    }

    /**
     * Detail pesanan — hanya driver yang bersangkutan yang bisa akses.
     */
    public function show(string $id)
    {
        $booking = Booking::findOrFail($id);

        abort_if(
            ($booking->driver['driver_id'] ?? null) !== (string) Auth::id(),
            403,
            'Anda tidak punya akses ke pesanan ini.'
        );

        // Tentukan apakah tombol "Sudah Jemput" boleh ditampilkan:
        // - Status harus confirmed
        // - start_date harus sudah tiba (atau dalam toleransi 30 menit sebelumnya)
        $canPickup = $booking->status === Booking::STATUS_CONFIRMED
            && Carbon::parse($booking->start_date)->subMinutes(30)->lte(Carbon::now());

        return view('driver.bookings.show', compact('booking', 'canPickup'));
    }

    /**
     * Driver klik tombol "Sudah Jemput".
     * Mengubah status confirmed → ongoing.
     * Hanya bisa dilakukan saat start_date sudah tiba (toleransi 30 menit sebelumnya).
     *
     * POST /driver/bookings/{id}/pickup
     */
    public function markPickup(string $id)
    {
        $booking = Booking::findOrFail($id);
        $driver  = Auth::user();

        // Guard: pastikan driver yang benar
        if (($booking->driver['driver_id'] ?? null) !== (string) $driver->_id) {
            abort(403);
        }

        // Guard: cek waktu — tidak boleh terlalu awal
        if (Carbon::parse($booking->start_date)->subMinutes(30)->gt(Carbon::now())) {
            return back()->withErrors([
                'error' => 'Tombol "Sudah Jemput" hanya bisa diklik mulai 30 menit sebelum waktu penjemputan.'
            ]);
        }

        try {
            $this->bookingService->driverMarkPickup($booking, $driver);

            return redirect()
                ->route('driver.bookings.show', $id)
                ->with('success', 'Status perjalanan diperbarui menjadi Sedang Berjalan.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}