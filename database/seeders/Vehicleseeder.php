<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Vehicle;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $vehicles = [
            [
                'name'          => 'Toyota Innova Reborn',
                'brand'         => 'Toyota',
                'model'         => 'Innova Reborn',
                'year'          => 2022,
                'plate_number'  => 'B 1234 ABC',
                'type'          => 'MPV',
                'capacity'      => 7,
                'price_per_day' => 550000,
                'status'        => 'available',
                'features'      => ['AC', 'Musik', 'GPS', 'Kamera Mundur'],
                'images'        => [],
                'rating_avg'    => 4.8,
                'total_bookings'=> 42,
            ],
            [
                'name'          => 'Toyota Avanza',
                'brand'         => 'Toyota',
                'model'         => 'Avanza',
                'year'          => 2021,
                'plate_number'  => 'B 5678 DEF',
                'type'          => 'MPV',
                'capacity'      => 7,
                'price_per_day' => 400000,
                'status'        => 'available',
                'features'      => ['AC', 'Musik'],
                'images'        => [],
                'rating_avg'    => 4.5,
                'total_bookings'=> 78,
            ],
            [
                'name'          => 'Honda CR-V',
                'brand'         => 'Honda',
                'model'         => 'CR-V Turbo',
                'year'          => 2023,
                'plate_number'  => 'B 9012 GHI',
                'type'          => 'SUV',
                'capacity'      => 5,
                'price_per_day' => 700000,
                'status'        => 'available',
                'features'      => ['AC', 'Musik', 'GPS', 'Sunroof', 'Kamera 360'],
                'images'        => [],
                'rating_avg'    => 4.9,
                'total_bookings'=> 30,
            ],
            [
                'name'          => 'Daihatsu Xenia',
                'brand'         => 'Daihatsu',
                'model'         => 'Xenia',
                'year'          => 2020,
                'plate_number'  => 'B 3456 JKL',
                'type'          => 'MPV',
                'capacity'      => 7,
                'price_per_day' => 350000,
                'status'        => 'rented', // sedang disewa (ada booking ongoing)
                'features'      => ['AC', 'Musik'],
                'images'        => [],
                'rating_avg'    => 4.3,
                'total_bookings'=> 55,
            ],
            [
                'name'          => 'Mitsubishi Pajero Sport',
                'brand'         => 'Mitsubishi',
                'model'         => 'Pajero Sport',
                'year'          => 2022,
                'plate_number'  => 'B 7890 MNO',
                'type'          => 'SUV',
                'capacity'      => 7,
                'price_per_day' => 900000,
                'status'        => 'available',
                'features'      => ['AC', 'Musik', 'GPS', 'Kamera Mundur', '4WD'],
                'images'        => [],
                'rating_avg'    => 4.7,
                'total_bookings'=> 18,
            ],
            [
                'name'          => 'Toyota Hiace',
                'brand'         => 'Toyota',
                'model'         => 'Hiace Premio',
                'year'          => 2021,
                'plate_number'  => 'B 2345 PQR',
                'type'          => 'Van',
                'capacity'      => 14,
                'price_per_day' => 1200000,
                'status'        => 'maintenance',
                'features'      => ['AC', 'Musik', 'Kursi Captain'],
                'images'        => [],
                'rating_avg'    => 4.6,
                'total_bookings'=> 22,
            ],
        ];

        foreach ($vehicles as $v) {
            Vehicle::create($v);
        }

        $this->command->info('✅ VehicleSeeder: 6 kendaraan dibuat.');
    }
}
