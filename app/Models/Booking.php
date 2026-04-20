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
        'status',
        'user',
        'vehicle',
        'driver',
        'pickup',
        'dropoff',
        'start_date',
        'end_date',
        'duration_days',
        'total_price',
        'notes',
        'assigned_at',   // ← baru: waktu admin assign driver
        'confirmed_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'start_date'   => 'datetime',
        'end_date'     => 'datetime',
        'assigned_at'  => 'datetime',
        'confirmed_at' => 'datetime',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // ── Scopes ──────────────────────────────────────────────
    public function scopePending($q)    { return $q->where('status', 'pending'); }
    public function scopeConfirmed($q)  { return $q->where('status', 'confirmed'); }
    public function scopeOngoing($q)    { return $q->where('status', 'ongoing'); }
    public function scopeCompleted($q)  { return $q->where('status', 'completed'); }

    public function scopeReadyToStart($q)
    {
        return $q->where('status', 'confirmed')
                 ->where('start_date', '<=', Carbon::now());
    }

    public function scopeReadyToComplete($q)
    {
        return $q->where('status', 'ongoing')
                 ->where('end_date', '<=', Carbon::now());
    }

    // Booking aktif milik driver tertentu
    public function scopeActiveByDriver($q, string $driverId)
    {
        return $q->whereIn('status', ['confirmed', 'ongoing'])
                 ->where('driver.driver_id', $driverId);
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

    /**
     * Kembalikan array string ID driver yang sedang punya pesanan aktif.
     * Dipakai controller untuk menyaring driver tersedia.
     */
    public static function busyDriverIds(): array
    {
        return static::whereIn('status', ['confirmed', 'ongoing'])
            ->whereNotNull('driver.driver_id')
            ->get()
            ->pluck('driver.driver_id')
            ->map(fn($id) => (string) $id)
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Hitung deadline konfirmasi:
     * mana yang lebih awal antara created_at+24jam dan start_date.
     */
    public function confirmationDeadline(): \Carbon\Carbon
    {
        $byTime    = \Carbon\Carbon::parse($this->created_at)->addHours(24);
        $byStart   = \Carbon\Carbon::parse($this->start_date);
    
        return $byTime->lt($byStart) ? $byTime : $byStart;
    }
    
    /**
     * Sisa waktu sebelum deadline, dalam format human-readable.
     * Contoh: "tersisa 3 jam 45 menit" atau "sudah terlewat"
     */
    public function confirmationDeadlineLabel(): string
    {
        $deadline = $this->confirmationDeadline();
    
        if ($deadline->isPast()) {
            return 'sudah terlewat';
        }
    
        return 'tersisa ' . $deadline->diffForHumans(absolute: true);
    }
}