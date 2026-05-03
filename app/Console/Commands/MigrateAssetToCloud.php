<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\LandingSetting;
use App\Models\Vehicle;
use App\Services\CloudinaryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Migrasikan semua gambar lokal (/storage/...) ke Cloudinary.
 *
 * Jalankan: php artisan assets:migrate-to-cloud
 * Dry run:  php artisan assets:migrate-to-cloud --dry-run
 * Rollback: php artisan assets:migrate-to-cloud --rollback
 */
class MigrateAssetsToCloud extends Command
{
    protected $signature   = 'assets:migrate-to-cloud
                                {--dry-run : Preview saja, tidak melakukan perubahan}
                                {--rollback : Kembalikan URL dari migration_log ke URL lama}
                                {--force : Lewati konfirmasi}';

    protected $description = 'Migrasi gambar lokal /storage/... ke Cloudinary dan update MongoDB';

    protected CloudinaryService $cloudinary;
    protected array $migrationLog = [];

    public function __construct(CloudinaryService $cloudinary)
    {
        parent::__construct();
        $this->cloudinary = $cloudinary;
    }

    public function handle(): int
    {
        if ($this->option('rollback')) {
            return $this->handleRollback();
        }

        $dry = $this->option('dry-run');

        $this->info('');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  Migrasi Gambar Lokal → Cloudinary');
        if ($dry) $this->warn('  MODE DRY-RUN: tidak ada perubahan yang disimpan');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('');

        if (!$dry && !$this->option('force')) {
            if (!$this->confirm('Yakin ingin melanjutkan migrasi? (log tersimpan di migration_logs collection)')) {
                $this->info('Dibatalkan.');
                return 0;
            }
        }

        $totalOk  = 0;
        $totalFail = 0;

        // ── 1. Vehicle.images ─────────────────────────────────────────────
        $this->info('1. Memproses Vehicle.images...');

        $vehicles = Vehicle::all();
        foreach ($vehicles as $vehicle) {
            $images    = $vehicle->images ?? [];
            $newImages = [];
            $changed   = false;

            foreach ($images as $url) {
                if (!CloudinaryService::isLocalPath($url)) {
                    $newImages[] = $url;
                    continue;
                }

                $localPath = public_path(ltrim($url, '/'));

                if (!file_exists($localPath)) {
                    $this->warn("  ✗ File tidak ada: $url");
                    $newImages[] = $url; // tetap simpan URL lama
                    $totalFail++;
                    continue;
                }

                if ($dry) {
                    $this->line("  [dry] Vehicle {$vehicle->id}: $url → Cloudinary");
                    $newImages[] = $url;
                    $totalOk++;
                    continue;
                }

                try {
                    $result = $this->cloudinary->uploadFromPath($localPath, 'vehicles');

                    $this->migrationLog[] = [
                        'type'       => 'vehicle',
                        'entity_id'  => $vehicle->id,
                        'old_url'    => $url,
                        'new_url'    => $result['url'],
                        'public_id'  => $result['public_id'],
                    ];

                    Asset::updateOrCreate(
                        ['public_id' => $result['public_id']],
                        [
                            'url'           => $result['url'],
                            'folder'        => $result['folder'],
                            'subfolder'     => 'vehicles',
                            'original_name' => basename($localPath),
                            'format'        => $result['format'],
                            'bytes'         => $result['bytes'],
                            'width'         => $result['width'],
                            'height'        => $result['height'],
                            'used_by'       => [['type' => 'vehicle', 'id' => $vehicle->id]],
                        ]
                    );

                    $newImages[] = $result['url'];
                    $changed     = true;
                    $totalOk++;
                    $this->line("  <info>✓</info> Vehicle {$vehicle->id}: {$result['url']}");

                    // Hapus file lokal setelah sukses
                    Storage::disk('public')->delete(
                        ltrim(str_replace('/storage/', '', $url), '/')
                    );

                } catch (\Throwable $e) {
                    $this->error("  ✗ Vehicle {$vehicle->id} — $url: {$e->getMessage()}");
                    $newImages[] = $url;
                    $totalFail++;
                    Log::error("[MigrateToCloud] Vehicle {$vehicle->id}: {$e->getMessage()}");
                }
            }

            if ($changed && !$dry) {
                $vehicle->update(['images' => $newImages]);
            }
        }

        // ── 2. LandingSetting.value ────────────────────────────────────────
        $this->info('');
        $this->info('2. Memproses LandingSetting...');

        $settings = LandingSetting::all();
        foreach ($settings as $setting) {
            $url = $setting->value;
            if (!CloudinaryService::isLocalPath($url)) continue;

            $localPath = public_path(ltrim($url, '/'));

            if (!file_exists($localPath)) {
                $this->warn("  ✗ File tidak ada: $url (key: {$setting->key})");
                $totalFail++;
                continue;
            }

            if ($dry) {
                $this->line("  [dry] {$setting->key}: $url → Cloudinary");
                $totalOk++;
                continue;
            }

            try {
                $subfolder = str_contains($setting->key, 'hero_slide') ? 'landing/slides' : 'landing';
                $result    = $this->cloudinary->uploadFromPath($localPath, $subfolder);

                $this->migrationLog[] = [
                    'type'      => 'landing',
                    'key'       => $setting->key,
                    'old_url'   => $url,
                    'new_url'   => $result['url'],
                    'public_id' => $result['public_id'],
                ];

                Asset::updateOrCreate(
                    ['public_id' => $result['public_id']],
                    [
                        'url'           => $result['url'],
                        'folder'        => $result['folder'],
                        'subfolder'     => $subfolder,
                        'original_name' => basename($localPath),
                        'format'        => $result['format'],
                        'bytes'         => $result['bytes'],
                        'width'         => $result['width'],
                        'height'        => $result['height'],
                        'used_by'       => [['type' => 'landing', 'id' => $setting->key]],
                    ]
                );

                $setting->update(['value' => $result['url']]);
                $totalOk++;
                $this->line("  <info>✓</info> {$setting->key}: {$result['url']}");

                Storage::disk('public')->delete(
                    ltrim(str_replace('/storage/', '', $url), '/')
                );

            } catch (\Throwable $e) {
                $this->error("  ✗ {$setting->key}: {$e->getMessage()}");
                $totalFail++;
                Log::error("[MigrateToCloud] {$setting->key}: {$e->getMessage()}");
            }
        }

        // ── Simpan migration log ──────────────────────────────────────────
        if (!$dry && !empty($this->migrationLog)) {
            \DB::connection('mongodb')->collection('migration_logs')->insert([
                'migration'  => 'assets_to_cloud',
                'run_at'     => now()->toDateTimeString(),
                'records'    => $this->migrationLog,
            ]);
            $this->info('');
            $this->info('Log migrasi tersimpan di collection migration_logs.');
        }

        $this->info('');
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("  Selesai: {$totalOk} berhasil, {$totalFail} gagal");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        return $totalFail > 0 ? 1 : 0;
    }

    protected function handleRollback(): int
    {
        $this->warn('Rollback: mengembalikan URL ke path lokal dari log terakhir...');

        $log = \DB::connection('mongodb')
            ->collection('migration_logs')
            ->where('migration', 'assets_to_cloud')
            ->orderBy('run_at', 'desc')
            ->first();

        if (!$log) {
            $this->error('Tidak ada migration log yang ditemukan.');
            return 1;
        }

        $this->info("Log dari: {$log['run_at']} — " . count($log['records']) . ' record.');

        if (!$this->confirm('Lanjutkan rollback?')) {
            return 0;
        }

        foreach ($log['records'] as $r) {
            if ($r['type'] === 'vehicle') {
                $vehicle = Vehicle::find($r['entity_id']);
                if ($vehicle) {
                    $images = collect($vehicle->images)->map(
                        fn($url) => $url === $r['new_url'] ? $r['old_url'] : $url
                    )->all();
                    $vehicle->update(['images' => $images]);
                    $this->line("  ✓ Vehicle {$r['entity_id']} dikembalikan");
                }
            } elseif ($r['type'] === 'landing') {
                LandingSetting::where('key', $r['key'])->update(['value' => $r['old_url']]);
                $this->line("  ✓ LandingSetting {$r['key']} dikembalikan");
            }
        }

        $this->info('Rollback selesai.');
        return 0;
    }
}