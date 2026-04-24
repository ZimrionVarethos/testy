<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookingService) {}

    public function index(Request $request)
    {
        $status  = $request->query('status');
        $search  = $request->query('search');
        $query   = Booking::orderBy('created_at', 'desc');

        if ($status) $query->where('status', $status);
        if ($search) $query->where('booking_code', 'like', "%{$search}%");

        $bookings = $query->paginate(15);
        return view('admin.bookings.index', compact('bookings', 'status', 'search'));
    }

    public function show(string $id)
    {
        $booking          = Booking::findOrFail($id);
        $availableDrivers = collect();

        // Tampilkan daftar driver hanya kalau pesanan masih pending dan belum ada driver
        if ($booking->status === Booking::STATUS_PENDING && empty($booking->driver['driver_id'])) {
            $startDate = Carbon::parse($booking->start_date);
            $endDate   = Carbon::parse($booking->end_date);

            // Driver yang sibuk di RENTANG TANGGAL ini saja (bukan semua yang punya booking aktif)
            $busyIds = Booking::busyDriverIdsInRange($startDate, $endDate);

            $availableDrivers = User::where('role', 'driver')
                ->where('is_active', true)
                ->get()
                ->filter(fn($d) => !in_array((string) $d->_id, $busyIds))
                ->values();

            // Sertakan juga jadwal tiap driver untuk ditampilkan di dropdown/tooltip
            $availableDrivers = $availableDrivers->map(function ($driver) {
                $driver->active_schedules = Booking::activeScheduleForDriver((string) $driver->_id);
                return $driver;
            });
        }

        return view('admin.bookings.show', compact('booking', 'availableDrivers'));
    }

    /**
     * Admin assign driver → status pending jadi confirmed.
     * Menggunakan BookingService agar logika terpusat.
     */
    public function assignDriver(Request $request, string $id)
    {
        $request->validate([
            'driver_id' => ['required', 'string'],
        ]);

        $booking = Booking::findOrFail($id);
        $driver  = User::where('role', 'driver')
            ->where('is_active', true)
            ->findOrFail($request->driver_id);

        try {
            $this->bookingService->adminAssignDriver($booking, $driver);

            return redirect()
                ->route('admin.bookings.show', $id)
                ->with('success', "Driver {$driver->name} berhasil di-assign. Pesanan sekarang Confirmed.");

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function cancel(Request $request, string $id)
    {
        $booking = Booking::findOrFail($id);
        try {
            $this->bookingService->cancelBooking(
                $booking,
                $request->input('reason', 'Dibatalkan oleh admin.')
            );
            return back()->with('success', 'Pesanan dibatalkan.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}