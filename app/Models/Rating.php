<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Rating extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'ratings';

    protected $fillable = [
        'booking_id',
        'driver_id',
        'user_id',
        'score',    // integer 1–5
        'comment',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    /** Cek apakah booking sudah dirating */
    public static function existsForBooking(string $bookingId): bool
    {
        return static::where('booking_id', $bookingId)->exists();
    }

    /** Ambil rating untuk booking tertentu */
    public static function forBooking(string $bookingId): ?self
    {
        return static::where('booking_id', $bookingId)->first();
    }

    /** Rata-rata score driver */
    public static function averageForDriver(string $driverId): float
    {
        $ratings = static::where('driver_id', $driverId)->get();
        if ($ratings->isEmpty()) return 0.0;
        return round($ratings->avg('score'), 1);
    }

    /** Total rating yang diterima driver */
    public static function countForDriver(string $driverId): int
    {
        return static::where('driver_id', $driverId)->count();
    }

    public function starLabel(): string
    {
        return str_repeat('★', $this->score) . str_repeat('☆', 5 - $this->score);
    }
}