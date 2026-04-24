<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Console\Command;

class UpdateBookingStatus extends Command
{
    protected $signature   = 'booking:update-status';
    protected $description = 'Auto-complete pesanan ongoing yang sudah melewati end_date';

    public function __construct(private BookingService $bookingService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        /**
         * CATATAN ALUR:
         *
         * confirmed → ongoing : TIDAK lagi dilakukan di sini.
         *   Sekarang driver yang trigger lewat tombol "Sudah Jemput" (driverMarkPickup).
         *   Scheduler tidak paksa status jadi ongoing karena driver harus konfirmasi manual.
         *
         * ongoing → completed : Tetap otomatis via scheduler.
         *   Saat end_date tiba, pesanan diselesaikan, vehicle dikembalikan ke available.
         */

        $toComplete = Booking::readyToComplete()->get();

        if ($toComplete->isEmpty()) {
            $this->info('Tidak ada pesanan yang perlu diselesaikan.');
            return;
        }

        foreach ($toComplete as $booking) {
            try {
                $this->bookingService->completeBooking($booking);
                $this->info("[COMPLETED] {$booking->booking_code}");
            } catch (\Throwable $e) {
                $this->error("[ERROR] {$booking->booking_code}: {$e->getMessage()}");
            }
        }

        $this->info("Selesai. {$toComplete->count()} pesanan diselesaikan.");
    }
}