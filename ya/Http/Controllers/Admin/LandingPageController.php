<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingSetting;
use Illuminate\Http\Request;
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
        ]);

        $saved = [];

        // Ganti gambar existing (static maupun slide existing)
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $key => $file) {
                if (!$file || !$file->isValid()) continue;
                $this->replaceFile($key, $file);
                $saved[] = $key;
                Log::info("[LandingPage] Updated: $key");
            }
        }

        // Upload slide baru
        if ($request->hasFile('new_slides')) {
            foreach ($request->file('new_slides') as $file) {
                if (!$file || !$file->isValid()) continue;
                $nextKey = $this->nextSlideKey();
                $path    = $file->store('landing/slides', 'public');
                LandingSetting::set($nextKey, Storage::url($path));
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

    // ── Helpers ──────────────────────────────────

    private function replaceFile(string $key, $file): void
    {
        // Hapus file lama dari storage (hanya jika path lokal /storage/...)
        $old = LandingSetting::get($key);
        if ($old && str_starts_with($old, '/storage/')) {
            $relativePath = ltrim(str_replace('/storage/', '', $old), '/');
            Storage::disk('public')->delete($relativePath);
        }

        // Simpan file baru
        $path = $file->store('landing', 'public');
        $url  = Storage::url($path);

        LandingSetting::set($key, $url);
    }

    private function deleteFile(string $key): void
    {
        $setting = LandingSetting::where('key', $key)->first();
        if (!$setting) return;

        if (str_starts_with($setting->value, '/storage/')) {
            $relativePath = ltrim(str_replace('/storage/', '', $setting->value), '/');
            Storage::disk('public')->delete($relativePath);
        }

        $setting->delete();
    }

    private function nextSlideKey(): string
    {
        $keys = LandingSetting::where('key', 'regexp', '/^hero_slide_\d+$/')->pluck('key');
        $max  = $keys->map(fn($k) => (int) str_replace('hero_slide_', '', $k))->max() ?? 0;
        return 'hero_slide_' . ($max + 1);
    }
}