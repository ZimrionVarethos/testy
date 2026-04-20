<?php

use App\Console\Commands\AutoCancelExpiredBookings;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Jalankan setiap 5 menit agar kondisi start_date yang terlewat
 * terdeteksi cepat — tidak perlu nunggu sejam.
 */
Schedule::command(AutoCancelExpiredBookings::class)
    ->everyFiveMinutes()
    ->withoutOverlapping()   // cegah tumpang tindih jika query lambat
    ->runInBackground();



// Cek dan update status booking setiap 5 menit
Schedule::command('booking:update-status')->everyFiveMinutes();
