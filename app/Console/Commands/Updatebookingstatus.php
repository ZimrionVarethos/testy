<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateBookingStatus extends Command
{
    protected $signature   = 'booking:update-status';
    protected $description = 'Auto-complete ongoing yang lewat end_date, auto-cancel confirmed yang tidak dijemput';

    public function __construct(private BookingService $bookingService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->handleOngoingToCompleted();
        $this->handleConfirmedNotPickedUp();
        $this->handlePendingPaidExpired();
    }

    /**
     * ongoing → completed saat end_date sudah lewat
     */
    private function handleOngoingToCompleted(): void
    {
        $bookings = Booking::readyToComplete()->get();

        foreach ($bookings as $booking) {
            try {
                $this->bookingService->completeBooking($booking);
                $this->info("[COMPLETED] {$booking->booking_code}");
            } catch (\Throwable $e) {
                $this->error("[ERROR] {$booking->booking_code}: {$e->getMessage()}");
            }
        }
    }

    /**
     * confirmed → cancelled saat start_date sudah lewat tapi driver tidak pernah pickup.
     * Toleransi 2 jam setelah start_date — kalau driver masih belum pickup, cancel otomatis.
     *
     * Kasus: admin assign driver, tapi driver tidak muncul / tidak klik "Sudah Jemput"
     */
    private function handleConfirmedNotPickedUp(): void
    {
        $cutoff = Carbon::now()->subHours(2); // toleransi 2 jam

        $bookings = Booking::confirmed()
            ->where('start_date', '<', $cutoff)
            ->get();

        foreach ($bookings as $booking) {
            try {
                $booking->update([
                    'status'        => Booking::STATUS_CANCELLED,
                    'cancelled_at'  => now(),
                    'cancel_reason' => 'Dibatalkan otomatis: driver tidak melakukan penjemputan tepat waktu.',
                ]);

                // Kembalikan vehicle ke available
                \App\Models\Vehicle::find($booking->vehicle['vehicle_id'])
                    ?->update(['status' => 'available']);

                // Notif ke user
                \App\Models\Notification::send(
                    $booking->user['user_id'] ?? $booking->user_id,
                    'Pesanan Dibatalkan Otomatis',
                    "Pesanan {$booking->booking_code} dibatalkan karena driver tidak melakukan penjemputan. Silakan hubungi admin untuk proses refund.",
                    'booking',
                    (string) $booking->_id,
                );

                $this->warn("[CANCELLED - NO PICKUP] {$booking->booking_code}");
            } catch (\Throwable $e) {
                $this->error("[ERROR] {$booking->booking_code}: {$e->getMessage()}");
            }
        }
    }

    /**
     * pending (sudah bayar) → cancelled saat jadwal sudah lewat tapi admin tidak assign driver.
     * Admin punya waktu sampai start_date. Lewat dari itu, cancel otomatis.
     *
     * Kasus: user sudah bayar, admin lupa assign driver, jadwal sudah lewat
     */
    private function handlePendingPaidExpired(): void
    {
        // Ambil booking pending yang start_date-nya sudah lewat
        $bookings = Booking::pending()
            ->where('start_date', '<', Carbon::now())
            ->get();

        foreach ($bookings as $booking) {
            // Hanya yang sudah bayar — yang belum bayar sudah di-handle AutoCancelExpiredBookings
            $payment = \App\Models\Payment::activeForBooking((string) $booking->_id);
            if (!$payment || !$payment->isPaid()) continue;

            try {
                $booking->update([
                    'status'        => Booking::STATUS_CANCELLED,
                    'cancelled_at'  => now(),
                    'cancel_reason' => 'Dibatalkan otomatis: admin tidak menugaskan driver sebelum waktu keberangkatan.',
                ]);

                // Notif ke user
                \App\Models\Notification::send(
                    $booking->user['user_id'] ?? $booking->user_id,
                    'Pesanan Dibatalkan Otomatis',
                    "Pesanan {$booking->booking_code} dibatalkan karena tidak ada driver yang ditugaskan sebelum jadwal. Silakan hubungi admin untuk proses refund.",
                    'booking',
                    (string) $booking->_id,
                );

                // Notif ke admin
                $admins = \App\Models\User::where('role', 'admin')
                    ->pluck('_id')
                    ->map(fn($id) => (string) $id)
                    ->toArray();

                \App\Models\Notification::sendToMany(
                    $admins,
                    'Pesanan Dibatalkan — Perlu Refund',
                    "Pesanan {$booking->booking_code} dibatalkan otomatis karena tidak ada driver. Proses refund ke pengguna.",
                    'booking',
                    (string) $booking->_id,
                    route('admin.bookings.show', (string) $booking->_id),
                );

                $this->warn("[CANCELLED - NO DRIVER] {$booking->booking_code}");
            } catch (\Throwable $e) {
                $this->error("[ERROR] {$booking->booking_code}: {$e->getMessage()}");
            }
        }
    }
}