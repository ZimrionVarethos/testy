<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\LandingController as ApiLanding;
use App\Http\Controllers\Controller;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LandingPageController extends Controller
{
    private array $fields = [
        'how_it_works_image' => [
            'label'       => 'Gambar Cara Kerja',
            'section'     => 'Cara Kerja',
            'aspect'      => '4/5',
            'ratio_label' => '4 : 5  •  Portrait',
            'recommended' => '800 × 1000px',
            'width'       => 800,
            'height'      => 1000,
            'note'        => 'Gambar portrait. Tampil di sebelah kanan langkah-langkah cara kerja.',
        ],
        'why_us_mockup' => [
            'label'       => 'Mockup Aplikasi',
            'section'     => 'Keunggulan',
            'aspect'      => '9/19',
            'ratio_label' => '9 : 19  •  Phone Portrait',
            'recommended' => '450 × 950px',
            'width'       => 450,
            'height'      => 950,
            'note'        => 'Ukuran layar HP. Pastikan UI aplikasi terlihat jelas di tengah frame.',
        ],
        'cta_image' => [
            'label'       => 'Gambar CTA',
            'section'     => 'Call to Action',
            'aspect'      => '3/2',
            'ratio_label' => '3 : 2  •  Landscape',
            'recommended' => '900 × 600px',
            'width'       => 900,
            'height'      => 600,
            'note'        => 'Gambar landscape. Tampil di sisi kanan tombol ajakan bertindak.',
        ],
    ];

    public function __construct(
        protected CloudinaryService $cloudinary,
        protected ApiLanding $landingApi,
    ) {}

    public function index()
    {
        // Semua query MongoDB ada di Api/LandingController::settingsForWeb()
        $fieldKeys = array_keys($this->fields);
        ['heroSlides' => $heroSlides, 'settings' => $settings] = $this->landingApi->settingsForWeb($fieldKeys);

        return view('admin.manajemenpage.index', [
            'heroSlides' => $heroSlides,
            'fields'     => $this->fields,
            'settings'   => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'images.*'     => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'new_slides.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'asset_ids'    => 'nullable|array',
            'asset_ids.*'  => 'nullable|string',
            'asset_focal_x' => 'nullable|array',
            'asset_focal_y' => 'nullable|array',
            'new_slide_asset_ids'   => 'nullable|array',
            'new_slide_asset_ids.*' => 'nullable|string',
            'new_slide_asset_focal_x'   => 'nullable|array',
            'new_slide_asset_focal_y'   => 'nullable|array',
        ]);

        $saved = [];

        // Ganti gambar static (upload baru)
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $key => $file) {
                if (!$file || !$file->isValid()) continue;
                $this->replaceFile($key, $file);
                $saved[] = $key;
                Log::info("[LandingPage] Updated via upload: $key");
            }
        }

        // Ganti gambar static (dari Asset Picker)
        if ($request->filled('asset_ids')) {
            foreach ($request->input('asset_ids') as $key => $assetId) {
                if (!$assetId) continue;

                // Ambil data asset dari API — TIDAK query MongoDB langsung
                $assetData = $this->landingApi->findAssetForWeb($assetId);
                if (!$assetData) continue;

                // Hapus gambar Cloudinary lama (Cloudinary ada di Web layer)
                $this->deleteOldCloudinaryFile($key);

                // Simpan ke MongoDB lewat API
                $this->landingApi->setSettingForWeb($key, $this->transformLandingUrl(
                    $key,
                    $assetData['url'],
                    (int) $request->input("asset_focal_x.$key", 50),
                    (int) $request->input("asset_focal_y.$key", 50),
                    $assetData['width'] ?? null,
                    $assetData['height'] ?? null,
                ));
                $this->landingApi->addAssetUsageByIdForWeb($assetId, 'landing', $key);

                $saved[] = $key;
                Log::info("[LandingPage] Updated via picker: $key => {$assetData['url']}");
            }
        }

        // Upload slide baru
        if ($request->hasFile('new_slides')) {
            foreach ($request->file('new_slides') as $file) {
                if (!$file || !$file->isValid()) continue;

                // Key slide berikutnya dari API
                $nextKey = $this->landingApi->nextSlideKeyForWeb();

                // Upload ke Cloudinary (infrastruktur — di Web layer)
                $result = $this->cloudinary->upload($file, 'landing/slides');

                // Simpan ke MongoDB lewat API
                $this->landingApi->setSettingForWeb($nextKey, $this->transformLandingUrl($nextKey, $result['url'], 50, 50, $result['width'] ?? null, $result['height'] ?? null));
                $this->landingApi->createAssetForWeb([
                    'public_id'     => $result['public_id'],
                    'url'           => $result['url'],
                    'folder'        => $result['folder'],
                    'subfolder'     => 'landing/slides',
                    'original_name' => $file->getClientOriginalName(),
                    'format'        => $result['format'],
                    'bytes'         => $result['bytes'],
                    'width'         => $result['width'],
                    'height'        => $result['height'],
                    'used_by'       => [['type' => 'landing', 'id' => $nextKey]],
                ]);

                $saved[] = $nextKey;
                Log::info("[LandingPage] New slide: $nextKey");
            }
        }

        // Tambah slide baru dari Asset Picker
        foreach ($request->input('new_slide_asset_ids', []) as $idx => $assetId) {
            if (!$assetId) continue;

            $assetData = $this->landingApi->findAssetForWeb($assetId);
            if (!$assetData) continue;

            $nextKey = $this->landingApi->nextSlideKeyForWeb();
            $this->landingApi->setSettingForWeb($nextKey, $this->transformLandingUrl(
                $nextKey,
                $assetData['url'],
                (int) $request->input("new_slide_asset_focal_x.$idx", 50),
                (int) $request->input("new_slide_asset_focal_y.$idx", 50),
                $assetData['width'] ?? null,
                $assetData['height'] ?? null,
            ));
            $this->landingApi->addAssetUsageByIdForWeb($assetId, 'landing', $nextKey);

            $saved[] = $nextKey;
            Log::info("[LandingPage] New slide from picker: $nextKey => {$assetData['url']}");
        }

        if (empty($saved)) {
            return back()->with('info', 'Tidak ada file yang dipilih untuk diupload.');
        }

        Cache::forget('welcome:landing:v1');

        return back()->with('success', 'Berhasil disimpan: ' . implode(', ', $saved));
    }

    public function destroySlide(string $key)
    {
        $this->deleteFileAndSetting($key);
        Cache::forget('welcome:landing:v1');
        return back()->with('success', "Slide '$key' berhasil dihapus.");
    }

    public function destroy(string $key)
    {
        $this->deleteFileAndSetting($key);
        Cache::forget('welcome:landing:v1');
        return back()->with('success', "Gambar '$key' dihapus, akan kembali ke gambar default.");
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function replaceFile(string $key, $file): void
    {
        // Hapus file lama dari Cloudinary (infrastruktur — Web layer)
        $this->deleteOldCloudinaryFile($key);

        // Upload ke Cloudinary (infrastruktur — Web layer)
        $result = $this->cloudinary->upload($file, 'landing');

        // Simpan/update ke MongoDB lewat API
        $this->landingApi->setSettingForWeb($key, $this->transformLandingUrl($key, $result['url'], 50, 50, $result['width'] ?? null, $result['height'] ?? null));
        $this->landingApi->upsertAssetForWeb(
            ['public_id' => $result['public_id']],
            [
                'url'           => $result['url'],
                'folder'        => $result['folder'],
                'subfolder'     => 'landing',
                'original_name' => $file->getClientOriginalName(),
                'format'        => $result['format'],
                'bytes'         => $result['bytes'],
                'width'         => $result['width'],
                'height'        => $result['height'],
                'used_by'       => [['type' => 'landing', 'id' => $key]],
            ]
        );
    }

    private function deleteFileAndSetting(string $key): void
    {
        // Hapus dari Cloudinary (infrastruktur — Web layer)
        $this->deleteOldCloudinaryFile($key);

        // Hapus setting dari MongoDB lewat API
        $this->landingApi->deleteSettingForWeb($key);
    }

    /**
     * Hapus URL Cloudinary lama untuk key ini.
     * Semua query MongoDB (LandingSetting::get, Asset::) dilakukan lewat API.
     */
    private function deleteOldCloudinaryFile(string $key): void
    {
        // Ambil URL lama dari MongoDB lewat API
        $old = $this->landingApi->getSettingValueForWeb($key);
        if (!$old) return;

        if (CloudinaryService::isCloudinaryUrl($old)) {
            $publicId = CloudinaryService::publicIdFromUrl($old);
            if ($publicId) {
                // Hapus dari Cloudinary (infrastruktur — Web layer)
                $this->cloudinary->delete($publicId);

                // Cleanup asset usage lewat API
                $this->landingApi->removeAssetUsageForWeb($publicId, 'landing', $key);
            }
        } elseif (CloudinaryService::isLocalPath($old)) {
            // Legacy: hapus file lokal
            $relativePath = ltrim(str_replace('/storage/', '', $old), '/');
            Storage::disk('public')->delete($relativePath);
        }
    }

    private function transformLandingUrl(string $key, string $url, int $x = 50, int $y = 50, ?int $sourceWidth = null, ?int $sourceHeight = null): string
    {
        $map = [
            'how_it_works_image' => 'c_fill,w_800,h_1000,g_auto,f_auto,q_auto',
            'why_us_mockup'      => 'c_fill,w_450,h_950,g_auto,f_auto,q_auto',
            'cta_image'          => 'c_fill,w_900,h_600,g_auto,f_auto,q_auto',
        ];

        $transformation = str_starts_with($key, 'hero_slide_')
            ? 'c_fill,w_1920,h_1080,g_auto,f_auto,q_auto'
            : ($map[$key] ?? 'f_auto,q_auto');

        if ($sourceWidth && $sourceHeight && preg_match('/w_(\d+),h_(\d+)/', $transformation)) {
            $px = max(1, min($sourceWidth, (int) round($sourceWidth * $x / 100)));
            $py = max(1, min($sourceHeight, (int) round($sourceHeight * $y / 100)));
            $transformation = str_replace('g_auto', "g_xy_center,x_{$px},y_{$py}", $transformation);
        }

        return CloudinaryService::transformUrl($url, $transformation);
    }
}
