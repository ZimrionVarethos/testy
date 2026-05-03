<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\LandingSetting;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LandingPageController extends Controller
{
    private array $fields = [
        'how_it_works_image' => [
            'label'       => 'Gambar Cara Kerja',
            'section'     => 'Cara Kerja',
            'aspect'      => '4/5',
            'ratio_label' => '4 : 5  •  Portrait',
            'recommended' => '800 × 1000px',
            'note'        => 'Gambar portrait. Tampil di sebelah kanan langkah-langkah cara kerja.',
        ],
        'why_us_mockup' => [
            'label'       => 'Mockup Aplikasi',
            'section'     => 'Keunggulan',
            'aspect'      => '9/19',
            'ratio_label' => '9 : 19  •  Phone Portrait',
            'recommended' => '450 × 950px',
            'note'        => 'Ukuran layar HP. Pastikan UI aplikasi terlihat jelas di tengah frame.',
        ],
        'cta_image' => [
            'label'       => 'Gambar CTA',
            'section'     => 'Call to Action',
            'aspect'      => '3/2',
            'ratio_label' => '3 : 2  •  Landscape',
            'recommended' => '900 × 600px',
            'note'        => 'Gambar landscape. Tampil di sisi kanan tombol ajakan bertindak.',
        ],
    ];

    public function __construct(protected CloudinaryService $cloudinary) {}

    public function index()
    {
        $heroSlides = LandingSetting::where('key', 'regexp', '/^hero_slide_\d+$/')
            ->orderBy('key', 'asc')
            ->get()
            ->mapWithKeys(fn($s) => [$s->key => $s->value]);

        $settings = [];
        foreach ($this->fields as $key => $meta) {
            $settings[$key] = LandingSetting::get($key);
        }

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
            // Asset Picker: key => asset_id
            'asset_ids'    => 'nullable|array',
            'asset_ids.*'  => 'nullable|string',
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
                $asset = Asset::find($assetId);
                if (!$asset) continue;

                // Hapus gambar Cloudinary lama jika ada
                $this->deleteOldCloudinaryUrl($key);

                LandingSetting::set($key, $asset->url);
                $asset->addUsage('landing', $key);
                $saved[] = $key;
                Log::info("[LandingPage] Updated via picker: $key => {$asset->url}");
            }
        }

        // Upload slide baru
        if ($request->hasFile('new_slides')) {
            foreach ($request->file('new_slides') as $file) {
                if (!$file || !$file->isValid()) continue;
                $nextKey = $this->nextSlideKey();

                $result = $this->cloudinary->upload($file, 'landing/slides');
                LandingSetting::set($nextKey, $result['url']);

                // Simpan ke Asset collection
                Asset::create([
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

        if (empty($saved)) {
            return back()->with('info', 'Tidak ada file yang dipilih untuk diupload.');
        }

        return back()->with('success', 'Berhasil disimpan: ' . implode(', ', $saved));
    }

    public function destroySlide(string $key)
    {
        $this->deleteFile($key);
        return back()->with('success', "Slide '$key' berhasil dihapus.");
    }

    public function destroy(string $key)
    {
        $this->deleteFile($key);
        return back()->with('success', "Gambar '$key' dihapus, akan kembali ke gambar default.");
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function replaceFile(string $key, $file): void
    {
        // Hapus file lama dari Cloudinary (jika ada)
        $this->deleteOldCloudinaryUrl($key);

        // Upload ke Cloudinary
        $result = $this->cloudinary->upload($file, 'landing');
        LandingSetting::set($key, $result['url']);

        // Simpan/update Asset record
        Asset::updateOrCreate(
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

    private function deleteFile(string $key): void
    {
        $setting = LandingSetting::where('key', $key)->first();
        if (!$setting) return;

        $this->deleteOldCloudinaryUrl($key);
        $setting->delete();
    }

    /**
     * Hapus URL Cloudinary lama yang tersimpan untuk key ini (jika ada).
     */
    private function deleteOldCloudinaryUrl(string $key): void
    {
        $old = LandingSetting::get($key);
        if (!$old) return;

        if (CloudinaryService::isCloudinaryUrl($old)) {
            $publicId = CloudinaryService::publicIdFromUrl($old);
            if ($publicId) {
                $this->cloudinary->delete($publicId);

                if ($asset = Asset::where('public_id', $publicId)->first()) {
                    $asset->removeUsage('landing', $key);
                }
            }
        } elseif (CloudinaryService::isLocalPath($old)) {
            // Legacy: hapus file lokal juga
            $relativePath = ltrim(str_replace('/storage/', '', $old), '/');
            \Illuminate\Support\Facades\Storage::disk('public')->delete($relativePath);
        }
    }

    private function nextSlideKey(): string
    {
        $keys = LandingSetting::where('key', 'regexp', '/^hero_slide_\d+$/')->pluck('key');
        $max  = $keys->map(fn($k) => (int) str_replace('hero_slide_', '', $k))->max() ?? 0;
        return 'hero_slide_' . ($max + 1);
    }
}