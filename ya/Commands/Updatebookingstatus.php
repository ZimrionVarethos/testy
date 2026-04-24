<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Console\Command;

class UpdateBookingStatus extends Command
{
    protected $signature   = 'booking:update-status';
    protected $description = 'Otomatis update status booking berdasarkan tanggal (confirmed→ongoing, ongoing→completed)';

    public function __construct(private BookingService $bookingService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        // 1. Confirmed → Ongoing: start_date sudah tiba
        $toStart = Booking::readyToStart()->get();
        foreach ($toStart as $booking) {
            try {
                $this->bookingService->startBooking($booking);
                $this->info("[STARTED] {$booking->booking_code}");
            } catch (\Throwable $e) {
                $this->error("[ERROR] {$booking->booking_code}: {$e->getMessage()}");
            }
        }

        // 2. Ongoing → Completed: end_date sudah tiba
        $toComplete = Booking::readyToComplete()->get();
        foreach ($toComplete as $booking) {
            try {
                $this->bookingService->completeBooking($booking);
                $this->info("[COMPLETED] {$booking->booking_code}");
            } catch (\Throwable $e) {
                $this->error("[ERROR] {$booking->booking_code}: {$e->getMessage()}");
            }
        }

        $this->info('Done. Started: ' . $toStart->count() . ', Completed: ' . $toComplete->count());
    }
}