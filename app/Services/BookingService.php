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
    public function __construct(private FcmService $fcm) {}

    // ══════════════════════════════════════════════════════════════
    //  STEP 1 — CREATE
    //  Dipakai: Web (Pengguna/VehicleController) + API (Api/BookingController)
    // ══════════════════════════════════════════════════════════════

    public function createBooking(array $data, User $user): Booking
    {
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);

        if (!$vehicle->isAvailable()) {
            throw new \Exception('Kendaraan tidak tersedia saat ini.');
        }

        // Cek konflik jadwal kendaraan berdasarkan range tanggal
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
        $totalPrice   = $durationDays * $vehicle->price_per_day;

        // Normalisasi pickup — bisa array atau string
        $pickup = is_array($data['pickup'] ?? null)
            ? $data['pickup']
            : [
                'address' => $data['pickup'] ?? $data['pickup_address'] ?? '',
                'lat'     => $data['pickup_lat'] ?? 0,
                'lng'     => $data['pickup_lng'] ?? 0,
            ];

        $dropoff = null;
        if (!empty($data['dropoff'])) {
            $dropoff = is_array($data['dropoff']) ? $data['dropoff'] : [
                'address' => $data['dropoff_address'] ?? '',
                'lat'     => $data['dropoff_lat'] ?? 0,
                'lng'     => $data['dropoff_lng'] ?? 0,
            ];
        }

        return Booking::create([
            'booking_code'  => Booking::generateCode(),
            'status'        => Booking::STATUS_PENDING,
            // Root-level IDs — untuk query langsung tanpa dot-notation
            'user_id'       => (string) $user->_id,
            'vehicle_id'    => (string) $vehicle->_id,
            // Date & price
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'duration_days' => $durationDays,
            'total_price'   => $totalPrice,
            // Lokasi
            'pickup'        => $pickup,
            'dropoff'       => $dropoff,
            'notes'         => $data['notes'] ?? null,
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
                'type'          => $vehicle->type ?? null,
                'price_per_day' => $vehicle->price_per_day,
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    //  Webhook setelah payment sukses — notif admin
    // ══════════════════════════════════════════════════════════════

    public function notifyAdminAfterPayment(Booking $booking): void
    {
        $admins = User::where('role', 'admin')
            ->pluck('_id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        // In-app notif (web dashboard)
        Notification::sendToMany(
            $admins,
            'Pesanan Baru Perlu Diproses',
            "Pesanan {$booking->booking_code} sudah dibayar. Silakan assign driver.",
            'booking',
            (string) $booking->_id,
            route('admin.bookings.show', (string) $booking->_id)
        );

        // Push notif (kalau admin punya app — opsional)
        $this->fcm->sendToMany($admins,
            'Pesanan Baru',
            "Pesanan {$booking->booking_code} sudah dibayar. Assign driver sekarang.",
            ['booking_id' => (string) $booking->_id, 'type' => 'booking_paid']
        );
    }

    // ══════════════════════════════════════════════════════════════
    //  STEP 2 — ADMIN ASSIGN DRIVER → pending → confirmed
    //  Hanya bisa dilakukan via web dashboard
    // ══════════════════════════════════════════════════════════════

    public function adminAssignDriver(Booking $booking, User $driver): Booking
    {
        if ($booking->status !== Booking::STATUS_PENDING) {
            throw new \Exception('Hanya booking berstatus pending yang bisa di-assign driver.');
        }

        // Cek konflik jadwal driver berdasarkan range tanggal
        $hasConflict = Booking::driverHasConflict(
            (string) $driver->_id,
            Carbon::parse($booking->start_date),
            Carbon::parse($booking->end_date),
        );

        if ($hasConflict) {
            throw new \Exception(
                "Driver {$driver->name} sudah punya jadwal yang bentrok di rentang tanggal ini."
            );
        }

        $booking->update([
            'status'       => Booking::STATUS_CONFIRMED,
            'confirmed_at' => now(),
            'assigned_at'  => now(),
            'driver_id'    => (string) $driver->_id,   // root-level untuk query
            'driver'       => [
                'driver_id'      => (string) $driver->_id,
                'name'           => $driver->name,
                'phone'          => $driver->phone ?? '',
                'license_number' => $driver->driver_profile['license_number'] ?? '-',
            ],
        ]);

        // In-app + push ke driver
        Notification::send(
            (string) $driver->_id,
            'Pesanan Baru Ditugaskan',
            "Anda ditugaskan pada pesanan {$booking->booking_code}. " .
            "Jemput: " . Carbon::parse($booking->start_date)->format('d M Y H:i') . ".",
            'booking',
            (string) $booking->_id,
            route('driver.bookings.show', (string) $booking->_id)
        );

        $this->fcm->sendToUser(
            (string) $driver->_id,
            'Pesanan Baru Ditugaskan',
            "Pesanan {$booking->booking_code} — Jemput {$booking->user['name']} " .
            "pada " . Carbon::parse($booking->start_date)->format('d M Y H:i'),
            ['type' => 'booking_assigned', 'booking_id' => (string) $booking->_id]
        );

        // In-app + push ke user
        Notification::send(
            $booking->user['user_id'] ?? $booking->user_id,
            'Pesanan Dikonfirmasi!',
            "Pesanan {$booking->booking_code} dikonfirmasi. " .
            "Driver {$driver->name} akan menjemput pada " .
            Carbon::parse($booking->start_date)->format('d M Y H:i') . ".",
            'booking',
            (string) $booking->_id,
            route('bookings.show', (string) $booking->_id)
        );

        $this->fcm->sendToUser(
            $booking->user['user_id'] ?? $booking->user_id,
            'Pesanan Dikonfirmasi!',
            "Driver {$driver->name} akan menjemput Anda pada " .
            Carbon::parse($booking->start_date)->format('d M Y H:i') . ".",
            ['type' => 'booking_confirmed', 'booking_id' => (string) $booking->_id]
        );

        return $booking->fresh();
    }

    // ══════════════════════════════════════════════════════════════
    //  STEP 3 — DRIVER MARK PICKUP → confirmed → ongoing
    //  Dipanggil dari: Web (Driver/BookingController) + API (Api/BookingController)
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

        Vehicle::find($booking->vehicle['vehicle_id'] ?? $booking->vehicle_id)
               ?->update(['status' => 'rented']);

        // In-app + push ke user
        Notification::send(
            $booking->user['user_id'] ?? $booking->user_id,
            'Perjalanan Dimulai!',
            "Driver {$driver->name} sudah menjemput. Pesanan {$booking->booking_code} sedang berjalan.",
            'tracking',
            (string) $booking->_id,
            route('bookings.show', (string) $booking->_id)
        );

        $this->fcm->sendToUser(
            $booking->user['user_id'] ?? $booking->user_id,
            'Perjalanan Dimulai!',
            "Driver {$driver->name} sudah menjemput Anda. Selamat jalan!",
            ['type' => 'booking_ongoing', 'booking_id' => (string) $booking->_id]
        );

        return $booking->fresh();
    }

    // ══════════════════════════════════════════════════════════════
    //  STEP 4 — AUTO COMPLETE via Scheduler → ongoing → completed
    // ══════════════════════════════════════════════════════════════

    public function completeBooking(Booking $booking): void
    {
        if ($booking->status !== Booking::STATUS_ONGOING) return;

        $booking->update([
            'status'       => Booking::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        Vehicle::find($booking->vehicle['vehicle_id'] ?? $booking->vehicle_id)
               ?->update(['status' => 'available']);

        // In-app + push ke user
        Notification::send(
            $booking->user['user_id'] ?? $booking->user_id,
            'Perjalanan Selesai',
            "Pesanan {$booking->booking_code} telah selesai. Jangan lupa berikan ulasan!",
            'booking',
            (string) $booking->_id,
            route('bookings.show', (string) $booking->_id)
        );

        $this->fcm->sendToUser(
            $booking->user['user_id'] ?? $booking->user_id,
            'Perjalanan Selesai',
            "Pesanan {$booking->booking_code} selesai. Terima kasih telah menggunakan layanan kami!",
            ['type' => 'booking_completed', 'booking_id' => (string) $booking->_id]
        );
    }

    // ══════════════════════════════════════════════════════════════
    //  CANCEL
    //  Dipanggil dari: Web + API, oleh user atau admin
    // ══════════════════════════════════════════════════════════════

    public function cancelBooking(Booking $booking, string $reason = ''): Booking
    {
        if (in_array($booking->status, [
            Booking::STATUS_ONGOING,
            Booking::STATUS_COMPLETED,
            Booking::STATUS_CANCELLED,
        ])) {
            throw new \Exception('Pesanan tidak bisa dibatalkan pada status ini.');
        }

        $booking->update([
            'status'        => Booking::STATUS_CANCELLED,
            'cancelled_at'  => now(),
            'cancel_reason' => $reason ?: 'Dibatalkan.',
        ]);

        // Kembalikan status kendaraan kalau sudah confirmed
        if (!empty($booking->vehicle['vehicle_id'])) {
            Vehicle::find($booking->vehicle['vehicle_id'])?->update(['status' => 'available']);
        }

        // In-app + push ke user
        Notification::send(
            $booking->user['user_id'] ?? $booking->user_id,
            'Pesanan Dibatalkan',
            "Pesanan {$booking->booking_code} telah dibatalkan. {$reason}",
            'booking',
            (string) $booking->_id,
        );

        $this->fcm->sendToUser(
            $booking->user['user_id'] ?? $booking->user_id,
            'Pesanan Dibatalkan',
            "Pesanan {$booking->booking_code} dibatalkan. {$reason}",
            ['type' => 'booking_cancelled', 'booking_id' => (string) $booking->_id]
        );

        // Notif ke driver kalau sudah ada yang di-assign
        if (!empty($booking->driver['driver_id'])) {
            Notification::send(
                $booking->driver['driver_id'],
                'Pesanan Dibatalkan',
                "Pesanan {$booking->booking_code} yang ditugaskan ke Anda telah dibatalkan.",
                'booking',
                (string) $booking->_id,
            );

            $this->fcm->sendToUser(
                $booking->driver['driver_id'],
                'Pesanan Dibatalkan',
                "Pesanan {$booking->booking_code} yang ditugaskan kepada Anda telah dibatalkan.",
                ['type' => 'booking_cancelled', 'booking_id' => (string) $booking->_id]
            );
        }

        return $booking->fresh();
    }

    // ══════════════════════════════════════════════════════════════
    //  AUTO-CANCEL — deadline bayar terlewat (belum bayar)
    //  Dipanggil dari: Pengguna/BookingController + Api/BookingController
    // ══════════════════════════════════════════════════════════════

    public function autoCancelExpiredForUser(string $userId): void
    {
        Booking::where('status', Booking::STATUS_PENDING)
            ->where('user.user_id', $userId)
            ->get()
            ->each(function (Booking $booking) {
                $payment    = Payment::activeForBooking((string) $booking->_id);
                $sudahBayar = $payment && $payment->isPaid();

                // Hanya cancel kalau belum bayar dan sudah lewat deadline
                // Kalau sudah bayar, biarkan pending — menunggu admin assign driver
                if (!$sudahBayar && $booking->confirmationDeadline()->isPast()) {
                    $booking->update([
                        'status'        => Booking::STATUS_CANCELLED,
                        'cancelled_at'  => now(),
                        'cancel_reason' => 'Dibatalkan otomatis: melewati batas waktu pembayaran.',
                    ]);
                }
            });
    }

    // ══════════════════════════════════════════════════════════════
    //  AUTO-COMPLETE — end_date sudah lewat
    //  Dipanggil dari: Api/BookingController (on-the-fly per user/driver)
    //  Juga dihandle oleh scheduler booking:update-status (global)
    // ══════════════════════════════════════════════════════════════

    public function autoCompleteExpiredForUser(string $userId): void
    {
        $this->runAutoComplete(
            Booking::where('user.user_id', $userId)
                   ->whereIn('status', [Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING])
        );
    }

    public function autoCompleteExpiredForDriver(string $driverId): void
    {
        $this->runAutoComplete(
            Booking::where('driver.driver_id', $driverId)
                   ->whereIn('status', [Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING])
        );
    }

    private function runAutoComplete($query): void
    {
        $query->get()->each(function (Booking $b) {
            if (Carbon::parse($b->end_date)->isPast()) {
                $b->update([
                    'status'       => Booking::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);

                if (!empty($b->vehicle['vehicle_id'])) {
                    Vehicle::find($b->vehicle['vehicle_id'])?->update(['status' => 'available']);
                }
            }
        });
    }
}