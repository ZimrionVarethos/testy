<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Vehicle;
use App\Models\User;
use App\Models\Notification;
use Carbon\Carbon;

class BookingService
{
    /**
     * STEP 1 — User buat booking
     * Status: pending
     * Notif: ke admin bahwa ada pesanan baru yang sudah dibayar
     *
     * Catatan: method ini dipanggil SETELAH payment berhasil (dari PaymentController webhook).
     * Kalau belum bayar, booking tetap pending tapi tidak muncul di admin.
     */
    public function createBooking(array $data, User $user): Booking
    {
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);

        // Cek konflik jadwal kendaraan
        $conflict = Booking::where('vehicle.vehicle_id', (string) $vehicle->_id)
            ->whereIn('status', [Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING])
            ->where('start_date', '<', $data['end_date'])
            ->where('end_date', '>', $data['start_date'])
            ->exists();

        if ($conflict) {
            throw new \Exception('Kendaraan tidak tersedia pada tanggal yang dipilih.');
        }

        $startDate    = Carbon::parse($data['start_date']);
        $endDate      = Carbon::parse($data['end_date']);
        $durationDays = max(1, $startDate->diffInDays($endDate));

        $booking = Booking::create([
            'booking_code'  => Booking::generateCode(),
            'status'        => Booking::STATUS_PENDING,
            'user'          => [
                'user_id' => (string) $user->_id,
                'name'    => $user->name,
                'phone'   => $user->phone ?? '',
                'email'   => $user->email,
            ],
            'vehicle'       => [
                'vehicle_id'    => (string) $vehicle->_id,
                'name'          => $vehicle->name,
                'plate_number'  => $vehicle->plate_number,
                'price_per_day' => $vehicle->price_per_day,
            ],
            'driver'        => null,
            'pickup'        => $data['pickup'],
            'dropoff'       => $data['dropoff'] ?? null,
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'duration_days' => $durationDays,
            'total_price'   => $vehicle->price_per_day * $durationDays,
            'notes'         => $data['notes'] ?? null,
        ]);

        return $booking;
    }

    /**
     * Dipanggil oleh PaymentController setelah pembayaran berhasil dikonfirmasi.
     * Mengirim notifikasi ke admin agar pesanan muncul di dashboard admin.
     */
    public function notifyAdminAfterPayment(Booking $booking): void
    {
        $admins = User::where('role', 'admin')
            ->pluck('_id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        Notification::sendToMany(
            $admins,
            'Pesanan Baru Perlu Diproses',
            "Pesanan {$booking->booking_code} sudah dibayar. Silakan assign driver.",
            'booking',
            (string) $booking->_id,
            route('admin.bookings.show', $booking->_id),
        );
    }

    /**
     * STEP 2 — Admin assign driver + konfirmasi sekaligus
     * Status: pending → confirmed
     * Notif: ke driver (ada pesanan baru) dan ke user (pesanan dikonfirmasi)
     *
     * Admin bisa assign driver dari halaman detail booking.
     * Sistem otomatis cek konflik jadwal driver berdasarkan range tanggal.
     */
    public function adminAssignDriver(Booking $booking, User $driver): Booking
    {
        if ($booking->status !== Booking::STATUS_PENDING) {
            throw new \Exception('Driver hanya bisa di-assign pada pesanan berstatus pending.');
        }

        // Cek konflik jadwal driver (range-based, bukan sekadar status)
        $hasConflict = Booking::driverHasConflict(
            (string) $driver->_id,
            Carbon::parse($booking->start_date),
            Carbon::parse($booking->end_date),
        );

        if ($hasConflict) {
            throw new \Exception(
                "Driver {$driver->name} sudah punya jadwal yang bentrok di rentang tanggal ini. " .
                "Cek jadwal driver sebelum assign."
            );
        }

        $booking->update([
            'driver' => [
                'driver_id'      => (string) $driver->_id,
                'name'           => $driver->name,
                'phone'          => $driver->phone ?? '',
                'license_number' => $driver->driver_profile['license_number'] ?? '',
            ],
            'status'       => Booking::STATUS_CONFIRMED,
            'assigned_at'  => now(),
            'confirmed_at' => now(),
        ]);

        // Notif ke driver
        Notification::send(
            userId:    (string) $driver->_id,
            title:     'Pesanan Baru Ditugaskan',
            message:   "Anda ditugaskan pada pesanan {$booking->booking_code}. " .
                       "Pelanggan: {$booking->user['name']}. " .
                       "Jemput: " . Carbon::parse($booking->start_date)->format('d M Y H:i') . ".",
            type:      'booking',
            relatedId: (string) $booking->_id,
            actionUrl: route('driver.bookings.show', $booking->_id),
        );

        // Notif ke user
        Notification::send(
            userId:    $booking->user['user_id'],
            title:     'Pesanan Dikonfirmasi!',
            message:   "Pesanan {$booking->booking_code} dikonfirmasi. " .
                       "Driver {$driver->name} akan menjemput pada " .
                       Carbon::parse($booking->start_date)->format('d M Y H:i') . ".",
            type:      'booking',
            relatedId: (string) $booking->_id,
            actionUrl: route('bookings.show', $booking->_id),
        );

        return $booking->refresh();
    }

    /**
     * STEP 3 — Driver klik "Sudah Jemput"
     * Status: confirmed → ongoing
     * Vehicle status: available → rented
     * Notif: ke user bahwa perjalanan dimulai
     *
     * Tombol ini hanya muncul di halaman driver saat start_date sudah tiba.
     */
    public function driverMarkPickup(Booking $booking, User $driver): Booking
    {
        if ($booking->status !== Booking::STATUS_CONFIRMED) {
            throw new \Exception('Pesanan harus berstatus confirmed untuk dapat dimulai.');
        }

        if (($booking->driver['driver_id'] ?? null) !== (string) $driver->_id) {
            throw new \Exception('Anda bukan driver yang ditugaskan pada pesanan ini.');
        }

        $booking->update([
            'status'     => Booking::STATUS_ONGOING,
            'started_at' => now(),
        ]);

        // Vehicle jadi rented
        Vehicle::find($booking->vehicle['vehicle_id'])?->update(['status' => 'rented']);

        // Notif ke user
        Notification::send(
            userId:    $booking->user['user_id'],
            title:     'Perjalanan Dimulai!',
            message:   "Driver {$driver->name} sudah menjemput. Pesanan {$booking->booking_code} sedang berjalan.",
            type:      'tracking',
            relatedId: (string) $booking->_id,
            actionUrl: route('bookings.show', $booking->_id),
        );

        return $booking->refresh();
    }

    /**
     * STEP 4 — Auto via Scheduler: Selesai saat end_date tiba
     * Status: ongoing → completed
     * Vehicle status: rented → available
     * Driver: status schedule kosong kembali (is_available tidak diubah manual, cukup jadwal-based)
     */
    public function completeBooking(Booking $booking): void
    {
        if ($booking->status !== Booking::STATUS_ONGOING) return;

        $booking->update([
            'status'       => Booking::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        // Kembalikan status vehicle
        Vehicle::find($booking->vehicle['vehicle_id'])?->update(['status' => 'available']);

        // Notif ke user
        Notification::send(
            userId:    $booking->user['user_id'],
            title:     'Perjalanan Selesai',
            message:   "Pesanan {$booking->booking_code} telah selesai. Jangan lupa berikan ulasan!",
            type:      'booking',
            relatedId: (string) $booking->_id,
            actionUrl: route('bookings.show', $booking->_id),
        );
    }

    /**
     * Cancel booking — bisa dilakukan user/admin sebelum ongoing
     */
    public function cancelBooking(Booking $booking, string $reason = ''): void
    {
        if (in_array($booking->status, [Booking::STATUS_ONGOING, Booking::STATUS_COMPLETED])) {
            throw new \Exception('Pesanan yang sedang berjalan atau sudah selesai tidak bisa dibatalkan.');
        }

        $booking->update([
            'status'        => Booking::STATUS_CANCELLED,
            'cancelled_at'  => now(),
            'cancel_reason' => $reason,
        ]);

        // Notif ke user
        Notification::send(
            userId:    $booking->user['user_id'],
            title:     'Pesanan Dibatalkan',
            message:   "Pesanan {$booking->booking_code} telah dibatalkan. {$reason}",
            type:      'booking',
            relatedId: (string) $booking->_id,
        );

        // Kalau sudah ada driver di-assign, notif driver juga
        if ($booking->driver) {
            Notification::send(
                userId:    $booking->driver['driver_id'],
                title:     'Pesanan Dibatalkan',
                message:   "Pesanan {$booking->booking_code} yang ditugaskan ke Anda telah dibatalkan.",
                type:      'booking',
                relatedId: (string) $booking->_id,
            );
        }
    }
}