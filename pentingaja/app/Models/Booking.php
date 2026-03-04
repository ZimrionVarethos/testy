<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Carbon\Carbon;

class Booking extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'bookings';

    protected $fillable = [
        'booking_code',
        'status',        // pending | accepted | confirmed | ongoing | completed | cancelled
        'user',          // embedded snapshot
        'vehicle',       // embedded snapshot
        'driver',        // embedded snapshot (nullable sampai driver ambil)
        'pickup',
        'dropoff',
        'start_date',    // tanggal mulai sewa
        'end_date',      // tanggal selesai sewa
        'duration_days',
        'total_price',
        'notes',
        'accepted_at',   // waktu driver accept
        'confirmed_at',  // waktu admin confirm
        'started_at',    // waktu status jadi ongoing (otomatis via scheduler)
        'completed_at',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'start_date'   => 'datetime',
        'end_date'     => 'datetime',
        'accepted_at'  => 'datetime',
        'confirmed_at' => 'datetime',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // ── Scopes ──────────────────────────────────────────────
    public function scopePending($q)       { return $q->where('status', 'pending'); }
    public function scopeAccepted($q)      { return $q->where('status', 'accepted'); }
    public function scopeConfirmed($q)     { return $q->where('status', 'confirmed'); }
    public function scopeOngoing($q)       { return $q->where('status', 'ongoing'); }
    public function scopeCompleted($q)     { return $q->where('status', 'completed'); }

    // Booking yang sudah confirmed & start_date-nya sudah lewat tapi belum ongoing
    public function scopeReadyToStart($q)
    {
        return $q->where('status', 'confirmed')
                 ->where('start_date', '<=', Carbon::now());
    }

    // Booking yang ongoing & end_date-nya sudah lewat
    public function scopeReadyToComplete($q)
    {
        return $q->where('status', 'ongoing')
                 ->where('end_date', '<=', Carbon::now());
    }

    // ── Helpers ─────────────────────────────────────────────
    public function isOwnedBy(string $userId): bool
    {
        return (string) $this->user['user_id'] === $userId;
    }

    public static function generateCode(): string
    {
        return 'BRN-' . now()->format('Ymd') . '-' . strtoupper(\Str::random(5));
    }
}