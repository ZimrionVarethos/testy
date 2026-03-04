<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Vehicle extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'vehicles';

    protected $fillable = [
        'name',
        'brand',
        'model',
        'year',
        'plate_number',
        'type',
        'capacity',
        'price_per_day',
        'status',        // available | rented | maintenance
        'features',
        'images',
        'rating_avg',
        'total_bookings',
    ];

    // Status hanya berubah jadi "rented" saat booking ongoing, bukan saat booking dibuat
    public function markAsRented(): void
    {
        $this->update(['status' => 'rented']);
    }

    public function markAsAvailable(): void
    {
        $this->update(['status' => 'available']);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }
}