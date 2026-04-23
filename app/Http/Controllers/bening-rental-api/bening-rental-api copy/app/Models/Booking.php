<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * Booking Model (MongoDB)
 *
 * File ini HILANG dari project — BookingController memanggil Booking::query(),
 * Booking::create(), Booking::findOrFail(), dll. tapi model-nya tidak ada.
 */
class Booking extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'bookings';

    protected $fillable = [
        'booking_code',
        'user_id',
        'vehicle_id',
        'driver_id',
        'status',         
        'start_date',
        'end_date',
        'duration_days',
        'total_price',
        'pickup',          
        'dropoff',         
        'notes',
        'user',           
        'vehicle',         
        'driver',          
        'accepted_at',
        'confirmed_at',
        'cancelled_at',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
            'total_price'   => 'integer',

        ];
    }
}