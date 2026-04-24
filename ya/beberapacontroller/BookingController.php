<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookingService) {}

    // ── USER ────────────────────────────────────────────────

    /**
     * User: buat booking baru
     * POST /bookings
     */
    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id'         => 'required|string',
            'start_date'         => 'required|date|after:now',
            'end_date'           => 'required|date|after:start_date',
            'pickup.address'     => 'required|string',
            'pickup.lat'         => 'required|numeric',
            'pickup.lng'         => 'required|numeric',
            'notes'              => 'nullable|string|max:500',
        ]);

        try {
            $booking = $this->bookingService->createBooking($request->all(), Auth::user());
            return redirect()->route('bookings.show', $booking->_id)
                             ->with('success', 'Pesanan berhasil dibuat! Menunggu driver.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * User: lihat detail booking miliknya
     * GET /bookings/{id}
     */
    public function show(string $id)
    {
        $booking = Booking::findOrFail($id);

        // Pastikan hanya pemilik yang bisa lihat
        if (!$booking->isOwnedBy((string) Auth::id())) {
            abort(403);
        }

        return view('bookings.show', compact('booking'));
    }

    /**
     * User: cancel booking
     * DELETE /bookings/{id}
     */
    public function destroy(Request $request, string $id)
    {
        $booking = Booking::findOrFail($id);

        if (!$booking->isOwnedBy((string) Auth::id())) {
            abort(403);
        }

        try {
            $this->bookingService->cancelBooking($booking, $request->input('reason', ''));
            return redirect()->route('bookings.index')->with('success', 'Pesanan dibatalkan.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // ── DRIVER ──────────────────────────────────────────────

    /**
     * Driver: lihat semua pesanan yang masih pending (bisa diambil)
     * GET /driver/bookings/available
     */
    public function availableForDriver()
    {
        $bookings = Booking::pending()
            ->orderBy('created_at', 'asc')
            ->paginate(10);

        return view('driver.bookings.available', compact('bookings'));
    }

    /**
     * Driver: ambil/accept pesanan
     * POST /driver/bookings/{id}/accept
     */
    public function driverAccept(string $id)
    {
        try {
            $booking = $this->bookingService->driverAcceptBooking($id, Auth::user());
            return redirect()->route('driver.bookings.show', $booking->_id)
                             ->with('success', 'Berhasil mengambil pesanan!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // ── ADMIN ───────────────────────────────────────────────

    /**
     * Admin: lihat semua booking
     * GET /admin/bookings
     */
    public function adminIndex(Request $request)
    {
        $status   = $request->query('status');
        $query    = Booking::orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        $bookings = $query->paginate(15);
        return view('admin.bookings.index', compact('bookings'));
    }

    /**
     * Admin: konfirmasi booking yang sudah diambil driver
     * POST /admin/bookings/{id}/confirm
     */
    public function adminConfirm(string $id)
    {
        try {
            $this->bookingService->adminConfirmBooking($id);
            return back()->with('success', 'Pesanan berhasil dikonfirmasi.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Admin: cancel booking
     * POST /admin/bookings/{id}/cancel
     */
    public function adminCancel(Request $request, string $id)
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