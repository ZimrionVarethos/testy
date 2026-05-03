<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * MongoDB collection: assets
 *
 * Menyimpan metadata semua aset yang sudah diupload ke Cloudinary.
 * Digunakan oleh Asset Manager dan Asset Picker.
 *
 * @property string  $public_id    Cloudinary public_id
 * @property string  $url          Secure URL Cloudinary
 * @property string  $folder       Folder di Cloudinary (misal "rental/vehicles")
 * @property string  $subfolder    Subfolder pendek (misal "vehicles", "landing", "admin")
 * @property string  $original_name  Nama file asli saat upload
 * @property string  $format       jpg, png, webp, dsb.
 * @property int     $bytes        Ukuran file dalam bytes
 * @property int|null $width
 * @property int|null $height
 * @property array   $used_by      [ ['type'=>'vehicle','id'=>'...'], ... ]
 * @property string  $uploaded_by  admin user id (opsional)
 */
class Asset extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'assets';

    protected $fillable = [
        'public_id',
        'url',
        'folder',
        'subfolder',
        'original_name',
        'format',
        'bytes',
        'width',
        'height',
        'used_by',
        'uploaded_by',
        'tags',
    ];

    protected $casts = [
        'bytes'       => 'integer',
        'width'       => 'integer',
        'height'      => 'integer',
        'used_by'     => 'array',
        'tags'        => 'array',
    ];

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeInFolder($query, string $subfolder)
    {
        return $query->where('subfolder', $subfolder);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where('original_name', 'like', "%{$term}%");
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Ukuran file dalam format manusia: "2.3 MB"
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->bytes ?? 0;
        if ($bytes >= 1_048_576) return round($bytes / 1_048_576, 1) . ' MB';
        if ($bytes >= 1_024)     return round($bytes / 1_024, 0) . ' KB';
        return $bytes . ' B';
    }

    /**
     * Tambahkan referensi pemakaian (agar tahu gambar ini dipakai di mana).
     *
     * @param  string  $type  'vehicle' | 'landing'
     * @param  string  $id    ID entitas
     */
    public function addUsage(string $type, string $id): void
    {
        $used = $this->used_by ?? [];
        $entry = ['type' => $type, 'id' => $id];
        if (!in_array($entry, $used)) {
            $used[] = $entry;
            $this->update(['used_by' => $used]);
        }
    }

    /**
     * Hapus referensi pemakaian.
     */
    public function removeUsage(string $type, string $id): void
    {
        $used = collect($this->used_by ?? [])
            ->reject(fn($u) => $u['type'] === $type && $u['id'] === $id)
            ->values()
            ->all();
        $this->update(['used_by' => $used]);
    }

    /**
     * Apakah asset ini masih digunakan di suatu tempat?
     */
    public function isInUse(): bool
    {
        return !empty($this->used_by);
    }
}