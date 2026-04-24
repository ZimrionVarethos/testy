<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Ticket extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'tickets';

    protected $fillable = [
        'booking_id',
        'booking_code',
        'user_id',
        'user_name',
        'subject',
        'message',
        'status',       // open | in_progress | resolved | closed
        'priority',     // normal | urgent
        'replies',      // array of {sender_role, sender_name, message, created_at}
        'admin_notes',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'replies'     => 'array',
    ];

    const STATUS_OPEN        = 'open';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED    = 'resolved';
    const STATUS_CLOSED      = 'closed';

    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_URGENT = 'urgent';

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN        => 'Terbuka',
            self::STATUS_IN_PROGRESS => 'Diproses',
            self::STATUS_RESOLVED    => 'Diselesaikan',
            self::STATUS_CLOSED      => 'Ditutup',
            default                  => ucfirst($this->status),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN        => 'bg-blue-100 text-blue-700',
            self::STATUS_IN_PROGRESS => 'bg-amber-100 text-amber-700',
            self::STATUS_RESOLVED    => 'bg-green-100 text-green-700',
            self::STATUS_CLOSED      => 'bg-gray-100 text-gray-500',
            default                  => 'bg-gray-100 text-gray-500',
        };
    }

    public function priorityBadgeClass(): string
    {
        return $this->priority === self::PRIORITY_URGENT
            ? 'bg-red-100 text-red-600'
            : 'bg-gray-100 text-gray-500';
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_IN_PROGRESS]);
    }

    public function addReply(string $senderRole, string $senderName, string $message): void
    {
        $replies   = $this->replies ?? [];
        $replies[] = [
            'sender_role' => $senderRole,
            'sender_name' => $senderName,
            'message'     => $message,
            'created_at'  => now()->toDateTimeString(),
        ];
        $this->update(['replies' => $replies]);
    }
}