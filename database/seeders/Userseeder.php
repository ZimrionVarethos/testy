<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ── ADMIN ────────────────────────────────────────────
        User::create([
            'name'              => 'Admin',
            'email'             => 'mochfarelaz@gmail.com',
            'password'          => Hash::make('admin123'),
            'role'              => 'admin',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        // ── PENGGUNA (tanpa phone, sesuai form registrasi) ───
        $pengguna = [
            [
                'name'  => 'Budi Santoso',
                'email' => 'budi@example.com',
            ],
            [
                'name'  => 'Siti Rahayu',
                'email' => 'siti@example.com',
            ],
            [
                'name'  => 'Rina Wati',
                'email' => 'rina@example.com',
            ],
        ];

        foreach ($pengguna as $p) {
            User::create([
                'name'              => $p['name'],
                'email'             => $p['email'],
                'password'          => Hash::make('password123'),
                'role'              => 'pengguna',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);
        }

        // ── DRIVER (butuh driver_profile) ────────────────────
        $drivers = [
            [
                'name'  => 'Andi Wijaya',
                'email' => 'andi.driver@example.com',
                'phone' => '081234567891',
                'license_number' => 'SIM-A-001234',
                'license_expiry' => '2027-06-01',
                'rating_avg'     => 4.8,
                'total_trips'    => 120,
                'is_available'   => true,
            ],
            [
                'name'  => 'Rizky Pratama',
                'email' => 'rizky.driver@example.com',
                'phone' => '082233445566',
                'license_number' => 'SIM-A-005678',
                'license_expiry' => '2026-12-01',
                'rating_avg'     => 4.6,
                'total_trips'    => 85,
                'is_available'   => true,
            ],
            [
                'name'  => 'Doni Saputra',
                'email' => 'doni.driver@example.com',
                'phone' => '083344556677',
                'license_number' => 'SIM-A-009012',
                'license_expiry' => '2026-08-15',
                'rating_avg'     => 4.5,
                'total_trips'    => 60,
                'is_available'   => false, // sedang ada trip
            ],
        ];

        foreach ($drivers as $d) {
            User::create([
                'name'              => $d['name'],
                'email'             => $d['email'],
                'password'          => Hash::make('password123'),
                'phone'             => $d['phone'],
                'role'              => 'driver',
                'is_active'         => true,
                'email_verified_at' => now(),
                'driver_profile'    => [
                    'license_number' => $d['license_number'],
                    'license_expiry' => $d['license_expiry'],
                    'is_available'   => $d['is_available'],
                    'current_location' => [
                        'lat' => -6.2088 + (rand(-10, 10) / 1000),
                        'lng' => 106.8456 + (rand(-10, 10) / 1000),
                    ],
                    'rating_avg'   => $d['rating_avg'],
                    'total_trips'  => $d['total_trips'],
                ],
            ]);
        }

        $this->command->info('UserSeeder: 1 admin, 3 pengguna, 3 driver dibuat.');
    }
}

