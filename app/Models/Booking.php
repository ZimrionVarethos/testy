<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Carbon\Carbon;

class Booking extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'bookings';

    // ── Status constants ─────────────────────────────────────
    // Alur resmi: pending → confirmed → ongoing → completed
    // Cabang: pending/confirmed → cancelled
    const STATUS_PENDING   = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_ONGOING   = 'ongoing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

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
        'assigned_at',
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

    // ── Scopes ───────────────────────────────────────────────
    public function scopePending($q)    { return $q->where('status', self::STATUS_PENDING); }
    public function scopeConfirmed($q)  { return $q->where('status', self::STATUS_CONFIRMED); }
    public function scopeOngoing($q)    { return $q->where('status', self::STATUS_ONGOING); }
    public function scopeCompleted($q)  { return $q->where('status', self::STATUS_COMPLETED); }

    // Booking aktif milik driver tertentu (dipakai DashboardController untuk peta)
    public function scopeActiveByDriver($q, string $driverId)
    {
        return $q->whereIn('status', [self::STATUS_CONFIRMED, self::STATUS_ONGOING])
                 ->where('driver.driver_id', $driverId);
    }

    public function scopeReadyToStart($q)
    {
        return $q->where('status', self::STATUS_CONFIRMED)
                 ->where('start_date', '<=', Carbon::now());
    }

    public function scopeReadyToComplete($q)
    {
        return $q->where('status', self::STATUS_ONGOING)
                 ->where('end_date', '<=', Carbon::now());
    }

    // ── Helpers ──────────────────────────────────────────────
    public function isOwnedBy(string $userId): bool
    {
        return (string) $this->user['user_id'] === $userId;
    }

    public static function generateCode(): string
    {
        return 'BRN-' . now()->format('Ymd') . '-' . strtoupper(\Str::random(5));
    }

    /**
     * Cek apakah driver tertentu punya booking aktif yang overlap
     * dengan range [startDate, endDate].
     *
     * Dipakai admin saat mau assign driver: admin pilih driver,
     * sistem cek dulu apakah di rentang pesanan baru driver ini
     * sudah ada jadwal yang tabrakan.
     *
     * Overlap terjadi jika: booking.start_date < endDate DAN booking.end_date > startDate
     */
    public static function driverHasConflict(
        string $driverId,
        Carbon $startDate,
        Carbon $endDate,
        ?string $excludeBookingId = null  // untuk edit booking, exclude dirinya sendiri
    ): bool {
        $query = static::whereIn('status', [self::STATUS_CONFIRMED, self::STATUS_ONGOING])
            ->where('driver.driver_id', $driverId)
            ->where('start_date', '<', $endDate)
            ->where('end_date', '>', $startDate);

        if ($excludeBookingId) {
            $query->where('_id', '!=', $excludeBookingId);
        }

        return $query->exists();
    }

    /**
     * Ambil semua driver ID yang punya jadwal overlap dengan range tertentu.
     * Dipakai untuk filter tampilan daftar driver saat admin mau assign.
     *
     * PENTING: Ini berbasis range tanggal, bukan sekadar status!
     * Driver hanya dianggap "sibuk" di rentang start_date s/d end_date booking aktifnya.
     */
    public static function busyDriverIdsInRange(Carbon $startDate, Carbon $endDate): array
    {
        return static::whereIn('status', [self::STATUS_CONFIRMED, self::STATUS_ONGOING])
            ->whereNotNull('driver.driver_id')
            ->where('start_date', '<', $endDate)
            ->where('end_date', '>', $startDate)
            ->get()
            ->pluck('driver.driver_id')
            ->map(fn($id) => (string) $id)
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * @deprecated Gunakan busyDriverIdsInRange() agar status driver akurat per tanggal.
     * Method ini ditinggalkan karena tidak mempertimbangkan range tanggal.
     */
    public static function busyDriverIds(): array
    {
        return static::whereIn('status', [self::STATUS_CONFIRMED, self::STATUS_ONGOING])
            ->whereNotNull('driver.driver_id')
            ->get()
            ->pluck('driver.driver_id')
            ->map(fn($id) => (string) $id)
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Ambil jadwal booking aktif seorang driver (untuk ditampilkan di halaman assign admin).
     */
    public static function activeScheduleForDriver(string $driverId): \Illuminate\Support\Collection
    {
        return static::whereIn('status', [self::STATUS_CONFIRMED, self::STATUS_ONGOING])
            ->where('driver.driver_id', $driverId)
            ->orderBy('start_date', 'asc')
            ->get();
    }

    // ── Label helpers ─────────────────────────────────────────

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING   => 'Menunggu Konfirmasi',
            self::STATUS_CONFIRMED => 'Dikonfirmasi',
            self::STATUS_ONGOING   => 'Sedang Berjalan',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default                => ucfirst($this->status),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING   => 'bg-yellow-100 text-yellow-700',
            self::STATUS_CONFIRMED => 'bg-blue-100 text-blue-700',
            self::STATUS_ONGOING   => 'bg-green-100 text-green-700',
            self::STATUS_COMPLETED => 'bg-gray-100 text-gray-700',
            self::STATUS_CANCELLED => 'bg-red-100 text-red-700',
            default                => 'bg-gray-100 text-gray-600',
        };
    }

    public function confirmationDeadline(): Carbon
    {
        return Carbon::parse($this->created_at)->addMinutes(30);
    }

    public function confirmationDeadlineLabel(): string
    {
        $deadline = $this->confirmationDeadline();
        if ($deadline->isPast()) return 'sudah terlewat';
        return 'tersisa ' . $deadline->diffForHumans(null, true);
    }
}