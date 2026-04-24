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
        'type',           // MPV | SUV | Van | Sedan | Minibus
        'capacity',
        'price_per_day',
        'status',         // available | rented | maintenance
        'features',       // array
        'images',         // array of path strings
        'rating_avg',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'year'          => 'integer',
            'capacity'      => 'integer',
            'price_per_day' => 'integer',
            'rating_avg'    => 'float',

        ];
    }
}