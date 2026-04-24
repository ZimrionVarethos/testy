<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ChatMessage extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'chat_messages';

    protected $fillable = [
        'booking_id',
        'sender_id',
        'sender_name',
        'sender_role',  // pengguna | driver
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /** 50 pesan terakhir untuk booking tertentu */
    public static function forBooking(string $bookingId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('booking_id', $bookingId)
            ->orderBy('created_at', 'asc')
            ->limit(50)
            ->get();
    }

    /** Tandai semua pesan dari lawan bicara sebagai sudah dibaca */
    public static function markReadForBooking(string $bookingId, string $readerRole): void
    {
        static::where('booking_id', $bookingId)
            ->where('sender_role', '!=', $readerRole)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    /** Hitung pesan belum dibaca dari lawan bicara */
    public static function unreadCount(string $bookingId, string $readerRole): int
    {
        return static::where('booking_id', $bookingId)
            ->where('sender_role', '!=', $readerRole)
            ->where('is_read', false)
            ->count();
    }
}