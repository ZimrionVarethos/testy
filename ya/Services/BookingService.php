<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Vehicle;
use App\Models\User;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /**
     * STEP 1: User membuat booking baru
     * Status: pending
     * Notif: broadcast ke semua driver yang available
     */
    public function createBooking(array $data, User $user): Booking
    {
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);

        // Validasi kendaraan tidak sedang rented
        // (cek booking aktif yang overlap tanggal, bukan cek status vehicle)
        $conflict = Booking::where('vehicle.vehicle_id', (string) $vehicle->_id)
            ->whereIn('status', ['accepted', 'confirmed', 'ongoing'])
            ->where(function ($q) use ($data) {
                $q->whereBetween('start_date', [$data['start_date'], $data['end_date']])
                  ->orWhereBetween('end_date', [$data['start_date'], $data['end_date']])
                  ->orWhere(function ($q2) use ($data) {
                      $q2->where('start_date', '<=', $data['start_date'])
                         ->where('end_date', '>=', $data['end_date']);
                  });
            })->exists();

        if ($conflict) {
            throw new \Exception('Kendaraan tidak tersedia pada tanggal yang dipilih.');
        }

        $startDate    = Carbon::parse($data['start_date']);
        $endDate      = Carbon::parse($data['end_date']);
        $durationDays = $startDate->diffInDays($endDate) ?: 1;

        $booking = Booking::create([
            'booking_code'  => Booking::generateCode(),
            'status'        => 'pending',
            'user'          => [
                'user_id' => (string) $user->_id,
                'name'    => $user->name,
                'phone'   => $user->phone,
                'email'   => $user->email,
            ],
            'vehicle'       => [
                'vehicle_id'   => (string) $vehicle->_id,
                'name'         => $vehicle->name,
                'plate_number' => $vehicle->plate_number,
                'price_per_day'=> $vehicle->price_per_day,
            ],
            'driver'        => null, // belum ada driver
            'pickup'        => $data['pickup'],
            'dropoff'       => $data['dropoff'] ?? null,
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'duration_days' => $durationDays,
            'total_price'   => $vehicle->price_per_day * $durationDays,
            'notes'         => $data['notes'] ?? null,
        ]);

        // Broadcast notif ke semua driver yang available
        $this->notifyAvailableDrivers($booking);

        return $booking;
    }

    /**
     * STEP 2: Driver mengambil/accept booking
     * Status: pending → accepted
     * Notif: ke admin bahwa driver X mengambil pesanan
     */
    public function driverAcceptBooking(string $bookingId, User $driver): Booking
    {
        $booking = Booking::findOrFail($bookingId);

        if ($booking->status !== 'pending') {
            throw new \Exception('Pesanan ini sudah diambil atau tidak tersedia.');
        }

        $booking->update([
            'status'      => 'accepted',
            'driver'      => [
                'driver_id'      => (string) $driver->_id,
                'name'           => $driver->name,
                'phone'          => $driver->phone,
                'license_number' => $driver->driver_profile['license_number'] ?? null,
            ],
            'accepted_at' => now(),
        ]);

        // Update ketersediaan driver
        $driver->update(['driver_profile.is_available' => false]);

        // Notif ke admin
        $admins = User::where('role', 'admin')->pluck('_id')->map(fn($id) => (string)$id)->toArray();
        Notification::sendToMany(
            $admins,
            'Pesanan Diambil Driver',
            "Driver {$driver->name} mengambil pesanan {$booking->booking_code}.",
            'booking',
            (string) $booking->_id
        );

        // Notif ke user bahwa pesanannya sudah ada driver
        Notification::send(
            $booking->user['user_id'],
            'Driver Ditemukan!',
            "Pesanan {$booking->booking_code} akan ditangani oleh {$driver->name}.",
            'booking',
            (string) $booking->_id
        );

        return $booking->refresh();
    }

    /**
     * STEP 3: Admin konfirmasi booking
     * Status: accepted → confirmed
     * Notif: ke user dan driver bahwa booking resmi dikonfirmasi
     * VEHICLE STATUS: BELUM berubah ke "rented" di sini!
     */
    public function adminConfirmBooking(string $bookingId): Booking
    {
        $booking = Booking::findOrFail($bookingId);

        if ($booking->status !== 'accepted') {
            throw new \Exception('Pesanan harus dalam status accepted untuk dikonfirmasi.');
        }

        $booking->update([
            'status'       => 'confirmed',
            'confirmed_at' => now(),
        ]);

        // Notif ke user
        Notification::send(
            $booking->user['user_id'],
            'Pesanan Dikonfirmasi!',
            "Pesanan {$booking->booking_code} telah dikonfirmasi. Driver akan menjemput pada " . Carbon::parse($booking->start_date)->format('d M Y H:i') . '.',
            'booking',
            (string) $booking->_id
        );

        // Notif ke driver
        if ($booking->driver) {
            Notification::send(
                $booking->driver['driver_id'],
                'Pesanan Dikonfirmasi',
                "Pesanan {$booking->booking_code} dikonfirmasi admin. Siap jemput pada " . Carbon::parse($booking->start_date)->format('d M Y H:i') . '.',
                'booking',
                (string) $booking->_id
            );
        }

        return $booking->refresh();
    }

    /**
     * STEP 4 (OTOMATIS via Scheduler): Status jadi "ongoing" saat start_date tiba
     * Status: confirmed → ongoing
     * Vehicle status: available → rented (BARU DI SINI!)
     */
    public function startBooking(Booking $booking): void
    {
        if ($booking->status !== 'confirmed') return;

        $booking->update([
            'status'     => 'ongoing',
            'started_at' => now(),
        ]);

        // Baru di sini vehicle berubah jadi "rented"
        Vehicle::find($booking->vehicle['vehicle_id'])?->update(['status' => 'rented']);


        // Notif ke user
        Notification::send(
            $booking->user['user_id'],
            'Perjalanan Dimulai',
            "Pesanan {$booking->booking_code} sedang berjalan. Selamat menikmati perjalanan!",
            'tracking',
            (string) $booking->_id
        );
    }

    /**
     * STEP 5 (OTOMATIS via Scheduler): Selesai saat end_date tiba
     * Status: ongoing → completed
     * Vehicle status: rented → available
     */
    public function completeBooking(Booking $booking): void
    {
        if ($booking->status !== 'ongoing') return;

        $booking->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        // Kembalikan status vehicle
        Vehicle::find($booking->vehicle['vehicle_id'])?->update(['status' => 'available']);

        // Bebaskan driver
        if ($booking->driver) {
            User::find($booking->driver['driver_id'])
                ?->update(['driver_profile.is_available' => true]);
        }

        // Notif ke user
        Notification::send(
            $booking->user['user_id'],
            'Perjalanan Selesai',
            "Pesanan {$booking->booking_code} selesai. Jangan lupa berikan ulasan!",
            'booking',
            (string) $booking->_id
        );
    }

    /**
     * Cancel booking (bisa dilakukan user/admin sebelum ongoing)
     */
    public function cancelBooking(Booking $booking, string $reason = ''): void
    {
        if (in_array($booking->status, ['ongoing', 'completed'])) {
            throw new \Exception('Pesanan yang sedang berjalan tidak bisa dibatalkan.');
        }

        $prevStatus = $booking->status;

        $booking->update([
            'status'        => 'cancelled',
            'cancelled_at'  => now(),
            'cancel_reason' => $reason,
        ]);

        // Bebaskan driver kalau sudah accepted
        if ($prevStatus === 'accepted' && $booking->driver) {
            User::where('_id', $booking->driver['driver_id'])
                ->update(['driver_profile.is_available' => true]);
        }

        // Notif ke user
        Notification::send(
            $booking->user['user_id'],
            'Pesanan Dibatalkan',
            "Pesanan {$booking->booking_code} telah dibatalkan. {$reason}",
            'booking',
            (string) $booking->_id
        );
    }

    // ── Private Helpers ─────────────────────────────────────

    private function notifyAvailableDrivers(Booking $booking): void
    {
        $driverIds = User::where('role', 'driver')
            ->where('driver_profile.is_available', true)
            ->where('is_active', true)
            ->pluck('_id')
            ->map(fn($id) => (string)$id)
            ->toArray();

        if (empty($driverIds)) return;

        Notification::sendToMany(
            $driverIds,
            'Ada Pesanan Baru!',
            "Pesanan {$booking->booking_code} menunggu driver. Klik untuk lihat detail.",
            'booking',
            (string) $booking->_id,
        );
    }
}