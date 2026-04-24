<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;

class AutoExpirePayments extends Command
{
    protected $signature   = 'payments:auto-expire';
    protected $description = 'Tandai payment pending yang sudah melewati batas waktu sebagai expired.';

    public function handle(): void
    {
        // Ambil semua payment pending yang expired_at-nya sudah lewat
        $payments = Payment::expiredPending()->get();

        if ($payments->isEmpty()) {
            $this->info('Tidak ada payment yang perlu diexpire.');
            return;
        }

        foreach ($payments as $payment) {
            $payment->update(['status' => Payment::STATUS_EXPIRED]);
            $this->line("  ✗ Expired: {$payment->booking_code} (order: {$payment->midtrans['order_id']})");
        }

        $this->info("Selesai. {$payments->count()} payment diexpire.");
    }
}