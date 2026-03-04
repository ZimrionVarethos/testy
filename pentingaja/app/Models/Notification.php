<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Notification extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'notifications';

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',        // booking | payment | tracking | system
        'is_read',
        'related_id',  // booking_id / payment_id terkait
        'action_url',  // link redirect saat notif diklik
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // Kirim notif ke satu user
    public static function send(string $userId, string $title, string $message, string $type = 'system', ?string $relatedId = null, ?string $actionUrl = null): self
    {
        return self::create([
            'user_id'    => $userId,
            'title'      => $title,
            'message'    => $message,
            'type'       => $type,
            'is_read'    => false,
            'related_id' => $relatedId,
            'action_url' => $actionUrl,
        ]);
    }

    // Kirim notif ke banyak user sekaligus (misal broadcast ke semua driver)
    public static function sendToMany(array $userIds, string $title, string $message, string $type = 'system', ?string $relatedId = null): void
    {
        $data = array_map(fn($id) => [
            'user_id'    => $id,
            'title'      => $title,
            'message'    => $message,
            'type'       => $type,
            'is_read'    => false,
            'related_id' => $relatedId,
            'created_at' => now(),
            'updated_at' => now(),
        ], $userIds);

        self::insert($data);
    }

    public function scopeUnread($q)
    {
        return $q->where('is_read', false);
    }

    public function scopeForUser($q, string $userId)
    {
        return $q->where('user_id', $userId);
    }
}