<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BookingService
{
    // ══════════════════════════════════════════════════════════════
    //  CREATE
    // ══════════════════════════════════════════════════════════════

    /**
     * Buat booking baru — dipakai oleh Web (Pengguna/VehicleController)
     * dan API (Api/BookingController). Logic identik, satu sumber.
     *
     * $data wajib mengandung:
     *   vehicle_id, start_date, end_date, pickup (array|string), notes (opsional)
     */
    public function createBooking(array $data, User $user): Booking
    {
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);

        if (! $vehicle->isAvailable()) {
            throw new \Exception('Kendaraan tidak tersedia saat ini.');
        }

        $startDate    = Carbon::parse($data['start_date']);
        $endDate      = Carbon::parse($data['end_date']);
        $durationDays = max(1, $startDate->diffInDays($endDate));
        $totalPrice   = $durationDays * $vehicle->price_per_day;

        // Normalisasi pickup — bisa array atau string
        $pickup = is_array($data['pickup'] ?? null)
            ? $data['pickup']
            : ['address' => $data['pickup'] ?? $data['pickup_address'] ?? '', 'lat' => $data['pickup_lat'] ?? 0, 'lng' => $data['pickup_lng'] ?? 0];

        $booking = Booking::create([
            'booking_code'  => 'BRN-' . strtoupper(Str::random(8)),
            // Root-level IDs — untuk query mudah
            'user_id'       => (string) $user->_id,
            'vehicle_id'    => (string) $vehicle->_id,
            // Date & price
            'start_date'    => $startDate->toDateTimeString(),
            'end_date'      => $endDate->toDateTimeString(),
            'duration_days' => $durationDays,
            'total_price'   => $totalPrice,
            'status'        => Booking::STATUS_PENDING,
            // Pickup / dropoff
            'pickup'  => $pickup,
            'dropoff' => isset($data['dropoff']) ? $data['dropoff'] : (
                isset($data['dropoff_address']) ? [
                    'address' => $data['dropoff_address'],
                    'lat'     => $data['dropoff_lat'] ?? 0,
                    'lng'     => $data['dropoff_lng'] ?? 0,
                ] : null
            ),
            'notes' => $data['notes'] ?? null,
            // Embedded snapshot — untuk tampilan tanpa JOIN
            'user' => [
                'user_id' => (string) $user->_id,
                'name'    => $user->name,
                'email'   => $user->email,
                'phone'   => $user->phone ?? '',
            ],
            'vehicle' => [
                'vehicle_id'    => (string) $vehicle->_id,
                'name'          => $vehicle->name ?? trim(($vehicle->brand ?? '') . ' ' . ($vehicle->model ?? '')),
                'plate_number'  => $vehicle->plate_number,
                'type'          => $vehicle->type,
                'price_per_day' => $vehicle->price_per_day,
            ],
        ]);

        return $booking;
    }

    // ══════════════════════════════════════════════════════════════
    //  CANCEL
    // ══════════════════════════════════════════════════════════════

    public function cancelBooking(Booking $booking, string $reason = ''): Booking
    {
        if (in_array($booking->status, ['ongoing', 'completed', 'cancelled'])) {
            throw new \Exception('Pesanan tidak bisa dibatalkan pada status ini.');
        }

        $booking->update([
            'status'        => Booking::STATUS_CANCELLED,
            'cancelled_at'  => now(),
            'cancel_reason' => $reason ?: 'Dibatalkan.',
        ]);

        // Kembalikan status kendaraan kalau sudah dikonfirmasi
        if ($booking->status === Booking::STATUS_CONFIRMED && ! empty($booking->vehicle['vehicle_id'])) {
            Vehicle::where('_id', $booking->vehicle['vehicle_id'])
                   ->update(['status' => 'available']);
        }

        return $booking->fresh();
    }

    // ══════════════════════════════════════════════════════════════
    //  AUTO-CANCEL (deadline bayar terlewat)
    // ══════════════════════════════════════════════════════════════

    /**
     * Auto-cancel booking pending yang belum bayar dan melewati deadline.
     * Dipanggil dari Pengguna/BookingController dan Api/BookingController.
     */
    public function autoCancelExpiredForUser(string $userId): void
    {
        Booking::where('status', Booking::STATUS_PENDING)
            ->where('user.user_id', $userId)
            ->get()
            ->each(function (Booking $booking) {
                $payment    = Payment::activeForBooking((string) $booking->_id);
                $sudahBayar = $payment && $payment->isPaid();

                if (! $sudahBayar && $booking->confirmationDeadline()->isPast()) {
                    $booking->update([
                        'status'        => Booking::STATUS_CANCELLED,
                        'cancelled_at'  => now(),
                        'cancel_reason' => 'Dibatalkan otomatis: melewati batas waktu pembayaran.',
                    ]);
                }
            });
    }

    // ══════════════════════════════════════════════════════════════
    //  AUTO-COMPLETE (end_date sudah lewat)
    // ══════════════════════════════════════════════════════════════

    /**
     * Auto-complete booking confirmed/ongoing yang end_date-nya sudah lewat.
     * Dipanggil dari web Chat controller dan Api BookingController.
     */
    public function autoCompleteExpiredForUser(string $userId): void
    {
        $this->autoCompleteQuery(
            Booking::where('user.user_id', $userId)
                   ->whereIn('status', [Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING])
        );
    }

    public function autoCompleteExpiredForDriver(string $driverId): void
    {
        $this->autoCompleteQuery(
            Booking::where('driver.driver_id', $driverId)
                   ->whereIn('status', [Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING])
        );
    }

    private function autoCompleteQuery($query): void
    {
        $query->get()->each(function (Booking $b) {
            if (Carbon::parse($b->end_date)->setTimezone('Asia/Jakarta')->isPast()) {
                $b->update([
                    'status'       => Booking::STATUS_COMPLETED,
                    'completed_at' => now('Asia/Jakarta'),
                ]);

                // Kembalikan status kendaraan
                if (! empty($b->vehicle['vehicle_id'])) {
                    Vehicle::where('_id', $b->vehicle['vehicle_id'])
                           ->update(['status' => 'available']);
                }
            }
        });
    }

    // ══════════════════════════════════════════════════════════════
    //  ADMIN: ASSIGN DRIVER
    // ══════════════════════════════════════════════════════════════

    public function adminAssignDriver(Booking $booking, User $driver): Booking
    {
        if ($booking->status !== Booking::STATUS_PENDING) {
            throw new \Exception('Hanya booking berstatus pending yang bisa di-assign driver.');
        }

        // Cek apakah driver sudah punya booking aktif di rentang waktu yang sama
        $busyIds = Booking::busyDriverIdsInRange(
            Carbon::parse($booking->start_date),
            Carbon::parse($booking->end_date)
        );

        if (in_array((string) $driver->_id, $busyIds)) {
            throw new \Exception("Driver {$driver->name} sudah memiliki jadwal di rentang waktu ini.");
        }

        $booking->update([
            'status'       => Booking::STATUS_CONFIRMED,
            'confirmed_at' => now(),
            'driver_id'    => (string) $driver->_id,   // root-level untuk query
            'driver'       => [
                'driver_id'      => (string) $driver->_id,
                'name'           => $driver->name,
                'phone'          => $driver->phone ?? '',
                'license_number' => $driver->driver_profile['license_number'] ?? '-',
            ],
        ]);

        // Update status kendaraan
        Vehicle::where('_id', $booking->vehicle['vehicle_id'] ?? $booking->vehicle_id)
               ->update(['status' => 'rented']);

        // Notifikasi ke pengguna
        Notification::send(
            $booking->user['user_id'] ?? $booking->user_id,
            'Pesanan Dikonfirmasi',
            "Pesanan {$booking->booking_code} telah dikonfirmasi. Driver {$driver->name} akan menjemput Anda.",
            'booking',
            (string) $booking->_id,
            route('bookings.show', (string) $booking->_id)
        );

        // Notifikasi ke driver
        Notification::send(
            (string) $driver->_id,
            'Pesanan Baru',
            "Anda mendapat tugas untuk pesanan {$booking->booking_code}.",
            'booking',
            (string) $booking->_id,
            route('driver.bookings.show', (string) $booking->_id)
        );

        return $booking->fresh();
    }

    // ══════════════════════════════════════════════════════════════
    //  ADMIN: CONFIRM (dari status accepted — flow mobile)
    // ══════════════════════════════════════════════════════════════

    public function adminConfirmBooking(string $bookingId): Booking
    {
        $booking = Booking::findOrFail($bookingId);

        if ($booking->status !== 'accepted') {
            throw new \Exception('Booking harus berstatus accepted untuk dikonfirmasi.');
        }

        Vehicle::where('_id', $booking->vehicle['vehicle_id'] ?? $booking->vehicle_id)
               ->update(['status' => 'rented']);

        $booking->update([
            'status'       => Booking::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        return $booking->fresh();
    }

    // ══════════════════════════════════════════════════════════════
    //  DRIVER: ACCEPT (flow mobile — driver ambil dari pool)
    // ══════════════════════════════════════════════════════════════

    public function driverAcceptBooking(string $bookingId, User $driver): Booking
    {
        $booking = Booking::findOrFail($bookingId);

        if ($booking->status !== Booking::STATUS_PENDING) {
            throw new \Exception('Booking sudah tidak berstatus pending.');
        }

        $booking->update([
            'status'      => 'accepted',
            'accepted_at' => now(),
            'driver_id'   => (string) $driver->_id,
            'driver'      => [
                'driver_id'      => (string) $driver->_id,
                'name'           => $driver->name,
                'phone'          => $driver->phone ?? '',
                'license_number' => $driver->driver_profile['license_number'] ?? '-',
            ],
        ]);

        return $booking->fresh();
    }

    // ══════════════════════════════════════════════════════════════
    //  DRIVER: MARK PICKUP (confirmed → ongoing)
    // ══════════════════════════════════════════════════════════════

    public function driverMarkPickup(Booking $booking, User $driver): Booking
    {
        if ($booking->status !== Booking::STATUS_CONFIRMED) {
            throw new \Exception('Status pesanan harus confirmed untuk melakukan pickup.');
        }

        if (($booking->driver['driver_id'] ?? null) !== (string) $driver->_id) {
            throw new \Exception('Anda tidak memiliki akses ke pesanan ini.');
        }

        $booking->update([
            'status'     => Booking::STATUS_ONGOING,
            'started_at' => now(),
        ]);

        // Notifikasi ke pengguna
        Notification::send(
            $booking->user['user_id'] ?? $booking->user_id,
            'Driver Dalam Perjalanan',
            "Driver {$driver->name} sedang menuju lokasi Anda untuk pesanan {$booking->booking_code}.",
            'booking',
            (string) $booking->_id,
            route('bookings.show', (string) $booking->_id)
        );

        return $booking->fresh();
    }

    // ══════════════════════════════════════════════════════════════
    //  NOTIFIKASI ADMIN SETELAH BAYAR
    // ══════════════════════════════════════════════════════════════

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
            route('admin.bookings.show', (string) $booking->_id)
        );
    }
}
