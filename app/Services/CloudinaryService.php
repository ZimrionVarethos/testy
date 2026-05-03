<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    protected Cloudinary $cloudinary;
    protected UploadApi  $upload;
    protected AdminApi   $admin;

    /** Root folder di Cloudinary, misal "rental" */
    protected string $root;

    public function __construct()
    {
        $this->root = rtrim(config('services.cloudinary.folder', 'rental'), '/');

        // Bangun Cloudinary URL: cloudinary://api_key:api_secret@cloud_name
        $cloudUrl = sprintf(
            'cloudinary://%s:%s@%s',
            config('services.cloudinary.api_key'),
            config('services.cloudinary.api_secret'),
            config('services.cloudinary.cloud_name')
        );

        $this->cloudinary = new Cloudinary($cloudUrl);
        $this->upload     = new UploadApi($cloudUrl);
        $this->admin      = new AdminApi($cloudUrl);
    }

    // ── Upload ─────────────────────────────────────────────────────────────

    /**
     * Upload UploadedFile ke Cloudinary.
     *
     * @param  UploadedFile  $file
     * @param  string        $subfolder  misal "vehicles", "landing", "admin"
     * @param  array         $options    transformasi tambahan, tag, dsb.
     * @return array  ['url' => '...', 'public_id' => '...', 'bytes' => ..., 'format' => '...']
     */
    public function upload(UploadedFile $file, string $subfolder = '', array $options = []): array
    {
        $folder = $this->buildFolder($subfolder);

        $result = $this->upload->upload($file->getRealPath(), array_merge([
            'folder'          => $folder,
            'use_filename'    => true,
            'unique_filename' => true,
            'overwrite'       => false,
            'resource_type'   => 'image',
            // Auto-quality + auto-format untuk hemat bandwidth
            'transformation'  => [['quality' => 'auto', 'fetch_format' => 'auto']],
        ], $options));

        return [
            'url'       => $result['secure_url'],
            'public_id' => $result['public_id'],
            'bytes'     => $result['bytes'],
            'format'    => $result['format'],
            'width'     => $result['width']  ?? null,
            'height'    => $result['height'] ?? null,
            'folder'    => $folder,
        ];
    }

    /**
     * Upload dari URL eksternal / path absolut.
     */
    public function uploadFromPath(string $path, string $subfolder = '', array $options = []): array
    {
        $folder = $this->buildFolder($subfolder);

        $result = $this->upload->upload($path, array_merge([
            'folder'          => $folder,
            'use_filename'    => true,
            'unique_filename' => true,
            'resource_type'   => 'image',
        ], $options));

        return [
            'url'       => $result['secure_url'],
            'public_id' => $result['public_id'],
            'bytes'     => $result['bytes'],
            'format'    => $result['format'],
            'width'     => $result['width']  ?? null,
            'height'    => $result['height'] ?? null,
            'folder'    => $folder,
        ];
    }

    // ── Delete ─────────────────────────────────────────────────────────────

    /**
     * Hapus aset dari Cloudinary via public_id.
     */
    public function delete(string $publicId): bool
    {
        try {
            $result = $this->upload->destroy($publicId, ['resource_type' => 'image']);
            return ($result['result'] ?? '') === 'ok';
        } catch (\Throwable $e) {
            Log::warning("[Cloudinary] Delete failed: {$publicId} — {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Hapus beberapa aset sekaligus (maks 100 per batch).
     *
     * @param  string[]  $publicIds
     */
    public function deleteMany(array $publicIds): void
    {
        foreach (array_chunk($publicIds, 100) as $chunk) {
            try {
                $this->admin->deleteAssets($chunk, ['resource_type' => 'image']);
            } catch (\Throwable $e) {
                Log::warning('[Cloudinary] Batch delete error: ' . $e->getMessage());
            }
        }
    }

    // ── List ───────────────────────────────────────────────────────────────

    /**
     * Daftar semua aset dalam subfolder tertentu (atau seluruh root).
     *
     * @param  string  $subfolder  kosong = root folder
     * @param  int     $maxResults  maks per panggilan (Cloudinary maks 500)
     * @return array[]  setiap item: ['url', 'public_id', 'bytes', 'format', 'folder', 'created_at']
     */
    public function listAssets(string $subfolder = '', int $maxResults = 200): array
    {
        $folder = $this->buildFolder($subfolder);

        try {
            $response = $this->admin->assets([
                'type'        => 'upload',
                'prefix'      => $folder . '/',
                'max_results' => $maxResults,
                'fields'      => ['secure_url', 'public_id', 'bytes', 'format', 'width', 'height', 'created_at', 'folder'],
            ]);

            return collect($response['resources'] ?? [])->map(fn($r) => [
                'url'        => $r['secure_url'],
                'public_id'  => $r['public_id'],
                'bytes'      => $r['bytes'],
                'format'     => $r['format'],
                'width'      => $r['width']  ?? null,
                'height'     => $r['height'] ?? null,
                'folder'     => $r['folder']  ?? $folder,
                'created_at' => $r['created_at'] ?? null,
            ])->all();
        } catch (\Throwable $e) {
            Log::error('[Cloudinary] listAssets error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Daftar subfolder langsung di bawah root atau subfolder tertentu.
     *
     * @return string[]
     */
    public function listFolders(string $parent = ''): array
    {
        $path = $parent ? $this->buildFolder($parent) : $this->root;

        try {
            $response = $this->admin->subFolders($path);
            return collect($response['folders'] ?? [])->pluck('name')->all();
        } catch (\Throwable $e) {
            Log::error('[Cloudinary] listFolders error: ' . $e->getMessage());
            return [];
        }
    }

    // ── Usage / Monitoring ─────────────────────────────────────────────────

    /**
     * Statistik pemakaian akun Cloudinary.
     *
     * @return array{
     *   credits_usage: float,
     *   credits_limit: float,
     *   storage_bytes: int,
     *   storage_limit_bytes: int,
     *   bandwidth_bytes: int,
     *   bandwidth_limit_bytes: int,
     *   transformations: int,
     *   objects: int,
     *   storage_pct: float,
     *   bandwidth_pct: float,
     * }
     */
    public function usage(): array
    {
        try {
            $u = $this->admin->usage();

            $storageBytes        = ($u['storage']['usage']  ?? 0);
            $storageLimitBytes   = ($u['storage']['limit']  ?? 1) ?: 1;
            $bandwidthBytes      = ($u['bandwidth']['usage'] ?? 0);
            $bandwidthLimitBytes = ($u['bandwidth']['limit'] ?? 1) ?: 1;

            return [
                'credits_usage'        => $u['credits']['usage']  ?? 0,
                'credits_limit'        => $u['credits']['limit']   ?? 0,
                'storage_bytes'        => $storageBytes,
                'storage_limit_bytes'  => $storageLimitBytes,
                'bandwidth_bytes'      => $bandwidthBytes,
                'bandwidth_limit_bytes'=> $bandwidthLimitBytes,
                'transformations'      => $u['transformations']['usage'] ?? 0,
                'objects'              => $u['objects']['usage']         ?? 0,
                'storage_pct'          => round($storageBytes   / $storageLimitBytes   * 100, 1),
                'bandwidth_pct'        => round($bandwidthBytes / $bandwidthLimitBytes * 100, 1),
                'plan'                 => $u['plan'] ?? 'free',
                'last_updated'         => $u['last_updated'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('[Cloudinary] usage() error: ' . $e->getMessage());
            return [
                'storage_pct'   => 0,
                'bandwidth_pct' => 0,
                'error'         => $e->getMessage(),
            ];
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /**
     * Ekstrak public_id dari Cloudinary URL.
     * https://res.cloudinary.com/{cloud}/{type}/{delivery}/v{version}/{public_id}.{ext}
     */
    public static function publicIdFromUrl(string $url): ?string
    {
        // Match everything after /upload/v{digits}/ or /upload/
        if (preg_match('~/upload/(?:v\d+/)?(.+?)(?:\.\w+)?$~', $url, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * Cek apakah URL adalah Cloudinary URL.
     */
    public static function isCloudinaryUrl(string $url): bool
    {
        return str_contains($url, 'res.cloudinary.com');
    }

    /**
     * Cek apakah URL masih path lokal /storage/...
     */
    public static function isLocalPath(string $url): bool
    {
        return str_starts_with($url, '/storage/');
    }

    /**
     * Build folder path: {root}/{subfolder}
     */
    protected function buildFolder(string $subfolder): string
    {
        $sub = trim($subfolder, '/');
        return $sub ? "{$this->root}/{$sub}" : $this->root;
    }
}