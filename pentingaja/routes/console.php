<?php

// routes/console.php
// Tambahkan schedule ini agar command jalan otomatis setiap menit

use Illuminate\Support\Facades\Schedule;

// Cek dan update status booking setiap 5 menit
Schedule::command('booking:update-status')->everyFiveMinutes();

// Alternatif: kalau mau lebih presisi, jalankan setiap menit
// Schedule::command('booking:update-status')->everyMinute();