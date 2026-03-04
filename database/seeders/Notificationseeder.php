<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\User;
use App\Models\Booking;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $users    = User::all()->keyBy('email');
        $admin    = $users['mochfarelaz@gmail.com'];
        $budi     = $users['budi@example.com'];
        $siti     = $users['siti@example.com'];
        $rina     = $users['rina@example.com'];
        $andi     = $users['andi.driver@example.com'];
        $rizky    = $users['rizky.driver@example.com'];

        $bookings = Booking::all();
        $pending  = $bookings->firstWhere('status', 'pending');
        $accepted = $bookings->firstWhere('status', 'accepted');
        $ongoing  = $bookings->firstWhere('status', 'ongoing');
        $completed = $bookings->firstWhere('status', 'completed');

        $notifs = [
            // ── Pengguna ─────────────────────────────────────
            [
                'user_id'    => (string) $budi->_id,
                'title'      => 'Pesanan Dibuat',
                'message'    => "Pesanan {$pending->booking_code} berhasil dibuat. Menunggu driver.",
                'type'       => 'booking',
                'is_read'    => false,
                'related_id' => (string) $pending->_id,
                'created_at' => now()->subMinutes(10),
            ],
            [
                'user_id'    => (string) $budi->_id,
                'title'      => 'Perjalanan Dimulai',
                'message'    => "Pesanan {$ongoing->booking_code} sedang berjalan. Selamat menikmati perjalanan!",
                'type'       => 'tracking',
                'is_read'    => true,
                'related_id' => (string) $ongoing->_id,
                'created_at' => now()->subDay(),
            ],
            [
                'user_id'    => (string) $siti->_id,
                'title'      => 'Driver Ditemukan!',
                'message'    => "Pesanan {$accepted->booking_code} akan ditangani oleh Rizky Pratama.",
                'type'       => 'booking',
                'is_read'    => false,
                'related_id' => (string) $accepted->_id,
                'created_at' => now()->subHour(),
            ],
            [
                'user_id'    => (string) $siti->_id,
                'title'      => 'Perjalanan Selesai',
                'message'    => "Pesanan {$completed->booking_code} selesai. Jangan lupa berikan ulasan!",
                'type'       => 'booking',
                'is_read'    => true,
                'related_id' => (string) $completed->_id,
                'created_at' => now()->subDays(7),
            ],
            [
                'user_id'    => (string) $rina->_id,
                'title'      => 'Pesanan Dikonfirmasi!',
                'message'    => 'Pesanan Anda telah dikonfirmasi. Driver siap menjemput sesuai jadwal.',
                'type'       => 'booking',
                'is_read'    => false,
                'related_id' => null,
                'created_at' => now()->subHours(2),
            ],

            // ── Admin ─────────────────────────────────────────
            [
                'user_id'    => (string) $admin->_id,
                'title'      => 'Pesanan Diambil Driver',
                'message'    => "Driver Rizky Pratama mengambil pesanan {$accepted->booking_code}.",
                'type'       => 'booking',
                'is_read'    => false,
                'related_id' => (string) $accepted->_id,
                'created_at' => now()->subHour(),
            ],
            [
                'user_id'    => (string) $admin->_id,
                'title'      => 'Pesanan Baru Masuk',
                'message'    => "Pesanan {$pending->booking_code} baru saja dibuat dan menunggu driver.",
                'type'       => 'booking',
                'is_read'    => false,
                'related_id' => (string) $pending->_id,
                'created_at' => now()->subMinutes(10),
            ],

            // ── Driver ────────────────────────────────────────
            [
                'user_id'    => (string) $andi->_id,
                'title'      => 'Ada Pesanan Baru!',
                'message'    => "Pesanan {$pending->booking_code} menunggu driver. Klik untuk lihat detail.",
                'type'       => 'booking',
                'is_read'    => false,
                'related_id' => (string) $pending->_id,
                'created_at' => now()->subMinutes(10),
            ],
            [
                'user_id'    => (string) $rizky->_id,
                'title'      => 'Ada Pesanan Baru!',
                'message'    => "Pesanan {$pending->booking_code} menunggu driver. Klik untuk lihat detail.",
                'type'       => 'booking',
                'is_read'    => true,
                'related_id' => (string) $pending->_id,
                'created_at' => now()->subMinutes(10),
            ],
            [
                'user_id'    => (string) $rizky->_id,
                'title'      => 'Pesanan Dikonfirmasi',
                'message'    => "Pesanan {$accepted->booking_code} dikonfirmasi admin. Siap jemput sesuai jadwal.",
                'type'       => 'booking',
                'is_read'    => false,
                'related_id' => (string) $accepted->_id,
                'created_at' => now()->subHours(2),
            ],
        ];

        foreach ($notifs as $n) {
            Notification::create(array_merge($n, ['updated_at' => $n['created_at']]));
        }

        $this->command->info('✅ NotificationSeeder: ' . count($notifs) . ' notifikasi dibuat.');
    }
}
