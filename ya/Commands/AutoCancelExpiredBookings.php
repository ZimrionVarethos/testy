<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoCancelExpiredBookings extends Command
{
    protected $signature   = 'bookings:auto-cancel';
    protected $description = 'Batalkan pesanan pending yang melewati deadline konfirmasi.';

    public function handle(): void
    {
        $now = Carbon::now();

        /*
         * Ambil semua booking pending di mana SALAH SATU kondisi terpenuhi:
         *   1. Sudah lebih dari 24 jam sejak dibuat (admin lambat konfirmasi)
         *   2. Waktu mulai sudah terlewat (pesan mepet, start_date sudah lewat)
         */
        $expired = Booking::where('status', 'pending')
            ->where(function ($q) use ($now) {
                $q->where('created_at', '<=', $now->copy()->subHours(24))
                  ->orWhere('start_date', '<=', $now);
            })
            ->get();

        if ($expired->isEmpty()) {
            $this->info('Tidak ada pesanan yang perlu dibatalkan.');
            return;
        }

        $count = 0;

        foreach ($expired as $booking) {
            // Tentukan alasan yang lebih spesifik untuk user
            $startDate = Carbon::parse($booking->start_date);
            $isStartPassed = $startDate->lte($now);

            $reason = $isStartPassed
                ? "Dibatalkan otomatis: waktu mulai pesanan ({$startDate->format('d M Y H:i')}) sudah terlewat sebelum admin sempat konfirmasi."
                : 'Dibatalkan otomatis: admin tidak mengkonfirmasi pesanan dalam 24 jam.';

            $booking->update([
                'status'        => 'cancelled',
                'cancelled_at'  => $now,
                'cancel_reason' => $reason,
            ]);

            // Notifikasi ke customer
            $userId = (string) ($booking->user['user_id'] ?? '');
            if ($userId) {
                Notification::send(
                    userId:    $userId,
                    title:     'Pesanan Dibatalkan Otomatis',
                    message:   "Pesanan {$booking->booking_code} dibatalkan. {$reason}",
                    type:      'booking',
                    relatedId: (string) $booking->_id,
                    actionUrl: route('bookings.show', $booking->_id),
                );
            }

            $this->line("  ✗ Cancelled: {$booking->booking_code} — {$reason}");
            $count++;
        }

        $this->info("Selesai. {$count} pesanan dibatalkan.");
    }
}