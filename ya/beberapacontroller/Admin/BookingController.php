<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookingService) {}

    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');
        $query  = Booking::orderBy('created_at', 'desc');

        if ($status) $query->where('status', $status);
        if ($search) $query->where('booking_code', 'like', "%{$search}%");

        $bookings = $query->paginate(15);
        return view('admin.bookings.index', compact('bookings', 'status', 'search'));
    }

    public function show(string $id)
    {
        $booking          = Booking::findOrFail($id);
        $availableDrivers = collect();

        // Muat daftar driver tersedia hanya jika pesanan belum ada driver
        if (
            in_array($booking->status, ['pending', 'confirmed']) &&
            empty($booking->driver['driver_id'])
        ) {
            $busyIds = Booking::busyDriverIds();

            // Ambil semua driver aktif, filter yang sedang sibuk di PHP
            // (lebih aman untuk MongoDB ObjectId vs string comparison)
            $availableDrivers = User::where('role', 'driver')
                ->where('is_active', true)
                ->get()
                ->filter(fn($d) => !in_array((string) $d->_id, $busyIds))
                ->values();
        }

        return view('admin.bookings.show', compact('booking', 'availableDrivers'));
    }

    /**
     * Admin assign driver ke pesanan.
     * Otomatis mengubah status → confirmed dan kirim notifikasi ke driver.
     */
    public function assignDriver(Request $request, string $id)
    {
        $request->validate([
            'driver_id' => ['required', 'string'],
        ]);

        $booking = Booking::findOrFail($id);

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return back()->withErrors([
                'error' => 'Driver hanya bisa di-assign pada pesanan berstatus pending atau confirmed.',
            ]);
        }

        $driver = User::where('role', 'driver')
            ->where('is_active', true)
            ->findOrFail($request->driver_id);

        // Validasi ulang: driver tidak sedang punya pesanan aktif
        $isBusy = Booking::whereIn('status', ['confirmed', 'ongoing'])
            ->where('driver.driver_id', (string) $driver->_id)
            ->exists();

        if ($isBusy) {
            return back()->withErrors([
                'error' => "Driver {$driver->name} sedang punya pesanan aktif. Pilih driver lain.",
            ]);
        }

        // Simpan snapshot driver ke dalam dokumen booking (embedded)
        $booking->update([
            'driver' => [
                'driver_id'      => (string) $driver->_id,
                'name'           => $driver->name,
                'phone'          => $driver->phone ?? '',
                'license_number' => $driver->driver_profile['license_number'] ?? '',
            ],
            'status'       => 'confirmed',
            'assigned_at'  => now(),
            'confirmed_at' => now(),
        ]);

        // Kirim notifikasi ke driver
        Notification::send(
            userId:    (string) $driver->_id,
            title:     'Pesanan Baru Ditugaskan',
            message:   "Anda ditugaskan pada pesanan {$booking->booking_code}. "
                     . "Pelanggan: {$booking->user['name']}. "
                     . "Mulai: " . \Carbon\Carbon::parse($booking->start_date)->format('d M Y H:i') . ".",
            type:      'booking',
            relatedId: (string) $booking->_id,
            actionUrl: route('driver.bookings.show', $booking->_id),
        );

        return redirect()
            ->route('admin.bookings.show', $id)
            ->with('success', "Driver {$driver->name} berhasil di-assign. Status pesanan → Confirmed.");
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