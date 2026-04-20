<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Carbon\Carbon;

class Payment extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'payments';

    protected $fillable = [
        'booking_id',       // string (_id dari Booking)
        'booking_code',     // e.g. BRN-20240101-ABCDE
        'user_id',          // string (_id dari User)
        'amount',           // integer (rupiah)
        'method',           // snap | manual | dll
        'status',           // pending | paid | failed | expired | cancelled
        'midtrans',         // array: snap_token, order_id, payment_type, transaction_id, dst
        'paid_at',          // datetime
        'expired_at',  // ← BARU
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount'  => 'integer',
        'expired_at' => 'datetime',  // ← BARU
    ];



    // ── Status constants ─────────────────────────────────────
    const STATUS_PENDING   = 'pending';
    const STATUS_PAID      = 'paid';
    const STATUS_FAILED    = 'failed';
    const STATUS_EXPIRED   = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    // ── Scopes ───────────────────────────────────────────────
    public function scopePending($q)   { return $q->where('status', self::STATUS_PENDING); }
    public function scopePaid($q)      { return $q->where('status', self::STATUS_PAID); }
    public function scopeFailed($q)    { return $q->where('status', self::STATUS_FAILED); }
    public function scopeExpiredPending($q)
    {
        $now = \Carbon\Carbon::now();
    
        return $q->where('status', self::STATUS_PENDING)
            ->where(function ($q) use ($now) {
                // Payment baru: pakai expired_at
                $q->where('expired_at', '<=', $now)
                // Payment lama tanpa expired_at: fallback ke created_at + 24 jam
                  ->orWhere(function ($q) use ($now) {
                      $q->whereNull('expired_at')
                        ->where('created_at', '<=', $now->copy()->subHours(24));
                  });
            });
    }

    // ── Helpers ──────────────────────────────────────────────
    public function isPaid(): bool     { return $this->status === self::STATUS_PAID; }
    public function isPending(): bool  { return $this->status === self::STATUS_PENDING; }
    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) return true;
    
        // Jika expired_at ada, pakai itu
        if ($this->expired_at !== null) {
            return \Carbon\Carbon::parse($this->expired_at)->isPast();
        }
    
        // Fallback untuk payment lama tanpa expired_at:
        // anggap expired setelah 24 jam dari created_at
        return \Carbon\Carbon::parse($this->created_at)->addHours(24)->isPast();
    }


    /**
     * Ambil payment yang aktif (pending atau paid) untuk booking tertentu.
     */
    public static function activeForBooking(string $bookingId): ?self
    {
        return self::where('booking_id', $bookingId)
                   ->whereIn('status', [self::STATUS_PENDING, self::STATUS_PAID])
                   ->latest()
                   ->first();
    }


    /**
     * Label status dalam bahasa Indonesia.
     */
    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING   => 'Menunggu Pembayaran',
            self::STATUS_PAID      => 'Lunas',
            self::STATUS_FAILED    => 'Gagal',
            self::STATUS_EXPIRED   => 'Kedaluwarsa',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default                => ucfirst($this->status),
        };
    }

    /**
     * Badge color class (Tailwind) untuk status.
     */
    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PAID      => 'bg-green-100 text-green-700',
            self::STATUS_PENDING   => 'bg-yellow-100 text-yellow-700',
            self::STATUS_FAILED,
            self::STATUS_CANCELLED => 'bg-red-100 text-red-700',
            self::STATUS_EXPIRED   => 'bg-gray-100 text-gray-600',
            default                => 'bg-gray-100 text-gray-600',
        };
    }


    public function expiryLabel(): string
    {
        if ($this->isExpired()) return 'sudah kedaluwarsa';
    
        $deadline = $this->expired_at
            ? \Carbon\Carbon::parse($this->expired_at)
            : \Carbon\Carbon::parse($this->created_at)->addHours(24); // fallback
    
        return 'tersisa ' . $deadline->diffForHumans(absolute: true);
    }

}