<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil data user & vehicle yang sudah dibuat
        $pengguna = User::where('role', 'pengguna')->get()->keyBy('email');
        $drivers  = User::where('role', 'driver')->get()->keyBy('email');
        $vehicles = Vehicle::all()->keyBy('plate_number');

        $budi  = $pengguna['budi@example.com'];
        $siti  = $pengguna['siti@example.com'];
        $rina  = $pengguna['rina@example.com'];

        $andi  = $drivers['andi.driver@example.com'];
        $rizky = $drivers['rizky.driver@example.com'];
        $doni  = $drivers['doni.driver@example.com'];

        $innova  = $vehicles['B 1234 ABC'];
        $avanza  = $vehicles['B 5678 DEF'];
        $crv     = $vehicles['B 9012 GHI'];
        $xenia   = $vehicles['B 3456 JKL'];
        $pajero  = $vehicles['B 7890 MNO'];

        // ── Helper closure ───────────────────────────────────
        $userSnap = fn($u) => [
            'user_id' => (string) $u->_id,
            'name'    => $u->name,
            'email'   => $u->email,
        ];

        $vehicleSnap = fn($v) => [
            'vehicle_id'    => (string) $v->_id,
            'name'          => $v->name,
            'plate_number'  => $v->plate_number,
            'price_per_day' => $v->price_per_day,
        ];

        $driverSnap = fn($d) => [
            'driver_id'      => (string) $d->_id,
            'name'           => $d->name,
            'phone'          => $d->phone,
            'license_number' => $d->driver_profile['license_number'],
        ];

        // ────────────────────────────────────────────────────
        // 1. PENDING — baru dibuat, menunggu driver
        // ────────────────────────────────────────────────────
        Booking::create([
            'booking_code'  => 'BRN-' . now()->format('Ymd') . '-P001',
            'status'        => 'pending',
            'user'          => $userSnap($budi),
            'vehicle'       => $vehicleSnap($innova),
            'driver'        => null,
            'pickup'        => ['address' => 'Jl. Sudirman No. 10, Jakarta Pusat', 'lat' => -6.2088, 'lng' => 106.8228],
            'dropoff'       => ['address' => 'Bandara Soekarno-Hatta, Tangerang', 'lat' => -6.1256, 'lng' => 106.6559],
            'start_date'    => now()->addDays(3)->setTime(8, 0),
            'end_date'      => now()->addDays(5)->setTime(20, 0),
            'duration_days' => 2,
            'total_price'   => $innova->price_per_day * 2,
            'notes'         => 'Tolong jemput tepat waktu.',
        ]);

        // ────────────────────────────────────────────────────
        // 2. ACCEPTED — driver sudah ambil, menunggu konfirmasi admin
        // ────────────────────────────────────────────────────
        Booking::create([
            'booking_code'  => 'BRN-' . now()->format('Ymd') . '-A001',
            'status'        => 'accepted',
            'user'          => $userSnap($siti),
            'vehicle'       => $vehicleSnap($avanza),
            'driver'        => $driverSnap($rizky),
            'pickup'        => ['address' => 'Jl. Thamrin No. 5, Jakarta', 'lat' => -6.1944, 'lng' => 106.8229],
            'dropoff'       => ['address' => 'Puncak, Bogor', 'lat' => -6.7000, 'lng' => 107.0167],
            'start_date'    => now()->addDays(2)->setTime(9, 0),
            'end_date'      => now()->addDays(4)->setTime(18, 0),
            'duration_days' => 2,
            'total_price'   => $avanza->price_per_day * 2,
            'notes'         => null,
            'accepted_at'   => now()->subHours(1),
        ]);

        // ────────────────────────────────────────────────────
        // 3. CONFIRMED — admin sudah konfirmasi, menunggu tanggal mulai
        // ────────────────────────────────────────────────────
        Booking::create([
            'booking_code'  => 'BRN-' . now()->format('Ymd') . '-C001',
            'status'        => 'confirmed',
            'user'          => $userSnap($rina),
            'vehicle'       => $vehicleSnap($crv),
            'driver'        => $driverSnap($andi),
            'pickup'        => ['address' => 'Jl. Kemang Raya No. 22, Jakarta Selatan', 'lat' => -6.2607, 'lng' => 106.8163],
            'dropoff'       => ['address' => 'Yogyakarta', 'lat' => -7.7956, 'lng' => 110.3695],
            'start_date'    => now()->addDay()->setTime(7, 0),
            'end_date'      => now()->addDays(3)->setTime(19, 0),
            'duration_days' => 2,
            'total_price'   => $crv->price_per_day * 2,
            'notes'         => 'Perjalanan keluarga.',
            'accepted_at'   => now()->subHours(5),
            'confirmed_at'  => now()->subHours(2),
        ]);

        // ────────────────────────────────────────────────────
        // 4. ONGOING — sedang berjalan (start_date sudah lewat)
        //    Vehicle xenia sudah di-set 'rented' di VehicleSeeder
        // ────────────────────────────────────────────────────
        Booking::create([
            'booking_code'  => 'BRN-' . now()->subDays(1)->format('Ymd') . '-O001',
            'status'        => 'ongoing',
            'user'          => $userSnap($budi),
            'vehicle'       => $vehicleSnap($xenia),
            'driver'        => $driverSnap($doni),
            'pickup'        => ['address' => 'Jl. Gatot Subroto No. 77, Jakarta', 'lat' => -6.2297, 'lng' => 106.8350],
            'dropoff'       => ['address' => 'Bandung, Jawa Barat', 'lat' => -6.9175, 'lng' => 107.6191],
            'start_date'    => now()->subDay()->setTime(8, 0),
            'end_date'      => now()->addDay()->setTime(18, 0),
            'duration_days' => 2,
            'total_price'   => $xenia->price_per_day * 2,
            'notes'         => null,
            'accepted_at'   => now()->subDays(2),
            'confirmed_at'  => now()->subDays(2)->addHours(3),
            'started_at'    => now()->subDay()->setTime(8, 0),
        ]);

        // ────────────────────────────────────────────────────
        // 5. COMPLETED — sudah selesai (histori)
        // ────────────────────────────────────────────────────
        Booking::create([
            'booking_code'  => 'BRN-' . now()->subDays(10)->format('Ymd') . '-D001',
            'status'        => 'completed',
            'user'          => $userSnap($siti),
            'vehicle'       => $vehicleSnap($pajero),
            'driver'        => $driverSnap($andi),
            'pickup'        => ['address' => 'Jl. Kuningan No. 3, Jakarta', 'lat' => -6.2297, 'lng' => 106.8319],
            'dropoff'       => ['address' => 'Bali', 'lat' => -8.4095, 'lng' => 115.1889],
            'start_date'    => now()->subDays(10)->setTime(6, 0),
            'end_date'      => now()->subDays(7)->setTime(20, 0),
            'duration_days' => 3,
            'total_price'   => $pajero->price_per_day * 3,
            'notes'         => 'Liburan ke Bali.',
            'accepted_at'   => now()->subDays(12),
            'confirmed_at'  => now()->subDays(12)->addHours(2),
            'started_at'    => now()->subDays(10)->setTime(6, 0),
            'completed_at'  => now()->subDays(7)->setTime(20, 0),
        ]);

        // ────────────────────────────────────────────────────
        // 6. CANCELLED — dibatalkan
        // ────────────────────────────────────────────────────
        Booking::create([
            'booking_code'  => 'BRN-' . now()->subDays(5)->format('Ymd') . '-X001',
            'status'        => 'cancelled',
            'user'          => $userSnap($rina),
            'vehicle'       => $vehicleSnap($innova),
            'driver'        => null,
            'pickup'        => ['address' => 'Jl. Fatmawati No. 15, Jakarta Selatan', 'lat' => -6.2946, 'lng' => 106.7942],
            'dropoff'       => null,
            'start_date'    => now()->subDays(3)->setTime(9, 0),
            'end_date'      => now()->subDays(1)->setTime(18, 0),
            'duration_days' => 2,
            'total_price'   => $innova->price_per_day * 2,
            'notes'         => null,
            'cancelled_at'  => now()->subDays(4),
            'cancel_reason' => 'Rencana perjalanan berubah.',
        ]);

        $this->command->info('✅ BookingSeeder: 6 booking dibuat (pending, accepted, confirmed, ongoing, completed, cancelled).');
    }
}