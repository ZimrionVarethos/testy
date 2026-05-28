<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Booking;
use App\Models\LandingSetting;
use App\Models\Rating;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CloudinaryService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LandingController extends Controller
{
    private array $fields = [
        'how_it_works_image' => [
            'label'       => 'Gambar Cara Kerja',
            'section'     => 'Cara Kerja',
            'aspect'      => '4/5',
            'ratio_label' => '4 : 5 - Portrait',
            'recommended' => '800 x 1000px',
            'width'       => 800,
            'height'      => 1000,
            'note'        => 'Gambar portrait. Tampil di sebelah kanan langkah-langkah cara kerja.',
        ],
        'why_us_mockup' => [
            'label'       => 'Mockup Aplikasi',
            'section'     => 'Keunggulan',
            'aspect'      => '9/19',
            'ratio_label' => '9 : 19 - Phone Portrait',
            'recommended' => '450 x 950px',
            'width'       => 450,
            'height'      => 950,
            'note'        => 'Ukuran layar HP. Pastikan UI aplikasi terlihat jelas di tengah frame.',
        ],
        'cta_image' => [
            'label'       => 'Gambar CTA',
            'section'     => 'Call to Action',
            'aspect'      => '3/2',
            'ratio_label' => '3 : 2 - Landscape',
            'recommended' => '900 x 600px',
            'width'       => 900,
            'height'      => 600,
            'note'        => 'Gambar landscape. Tampil di sisi kanan tombol ajakan bertindak.',
        ],
    ];

    private array $defaults = [
        'hero_slides' => [
            'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&q=85&w=1920',
            'https://images.unsplash.com/photo-1544636331-e26879cd4d9b?auto=format&fit=crop&q=85&w=1920',
            'https://images.unsplash.com/photo-1555215695-3004980ad54e?auto=format&fit=crop&q=85&w=1920',
        ],
        'how_it_works_image' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?auto=format&fit=crop&q=80&w=800',
        'cta_image'          => 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?auto=format&fit=crop&q=80&w=900',
    ];

    /**
     * GET /api/v1/landing
     * Data publik untuk halaman welcome/landing page.
     */
    public function index(): JsonResponse
    {
        $heroSlides = LandingSetting::where('key', 'regexp', '/^hero_slide_\d+$/')
            ->orderBy('key', 'asc')
            ->pluck('value')
            ->values()
            ->toArray();

        if (empty($heroSlides)) {
            $heroSlides = $this->defaults['hero_slides'];
        }

        $imageSettings = LandingSetting::whereIn('key', ['how_it_works_image', 'why_us_mockup', 'cta_image'])
            ->get()->keyBy('key');
        $landingImages = [
            'how_it_works_image' => $imageSettings->get('how_it_works_image')?->value ?? $this->defaults['how_it_works_image'],
            'why_us_mockup'      => $imageSettings->get('why_us_mockup')?->value ?? asset('image/mockup.png'),
            'cta_image'          => $imageSettings->get('cta_image')?->value ?? $this->defaults['cta_image'],
        ];

        $stats = [
            'total_vehicles'  => Vehicle::where('status', 'available')->count(),
            'total_bookings'  => Booking::where('status', 'completed')->count(),
            'happy_customers' => User::where('role', 'pengguna')->count(),
        ];

        $vehicles = Vehicle::where('status', '!=', 'maintenance')->limit(6)->get()
            ->map(fn($v) => [
                'id'             => (string) $v->_id,
                '_id'            => (string) $v->_id,
                'name'           => $v->name,
                'brand'          => $v->brand,
                'model'          => $v->model,
                'year'           => $v->year,
                'type'           => $v->type,
                'capacity'       => $v->capacity,
                'price_per_day'  => $v->price_per_day,
                'status'         => $v->status,
                'features'       => $v->features ?? [],
                'rating_avg'     => $v->rating_avg ?? 0,
                'total_bookings' => $v->total_bookings ?? 0,
                'images'         => collect($v->images ?? [])->map(
                    fn($p) => str_starts_with($p, 'http') ? $p : url('storage/' . $p)
                )->values()->all(),
            ])->values();

        $realRatings = Rating::where('score', '>=', 4)
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $ratingUserIds    = $realRatings->pluck('user_id')->filter()->unique()->values()->toArray();
        $ratingBookingIds = $realRatings->pluck('booking_id')->filter()->unique()->values()->toArray();
        $usersMap         = User::whereIn('_id', $ratingUserIds)->get()->keyBy(fn($u) => (string) $u->_id);
        $bookingsMap      = Booking::whereIn('_id', $ratingBookingIds)->get(['_id', 'vehicle'])->keyBy(fn($b) => (string) $b->_id);

        $testimonials = $realRatings->map(function ($rating) use ($usersMap, $bookingsMap) {
            $user    = $usersMap->get((string) $rating->user_id);
            $booking = $bookingsMap->get((string) $rating->booking_id);
            $name    = $user?->name ?? 'Pelanggan';
            $vehicle = $booking?->vehicle['name'] ?? null;
            $role    = $vehicle ? 'Pengguna ' . $vehicle : 'Pelanggan Setia';
            $initials = strtoupper(
                collect(explode(' ', $name))->take(2)->map(fn($w) => $w[0] ?? '')->implode('')
            );

            return [
                'init'   => $initials,
                'name'   => $name,
                'role'   => $role,
                'stars'  => $rating->score,
                'text'   => $rating->comment,
                'avatar' => $user?->avatar,
            ];
        })->values();

        $ratingAvg   = $realRatings->avg('score') ?: 4.9;
        $ratingCount = Rating::count();

        return response()->json([
            'success' => true,
            'data'    => compact(
                'heroSlides', 'landingImages', 'stats', 'vehicles',
                'testimonials', 'ratingAvg', 'ratingCount'
            ),
        ]);
    }

    // ── ForWeb Methods (dipakai Admin LandingPageController langsung) ─────────

    /**
     * GET /api/v1/landing/available-vehicles
     * Public availability checker untuk landing page guest.
     */
    public function availableVehicles(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date'   => ['required', 'date', 'after:start_date'],
        ]);

        $start = Carbon::parse($request->start_date);
        $end   = Carbon::parse($request->end_date);
        $recentPendingCutoff = now()->subMinutes(30);

        $bookedVehicleIds = Booking::where('start_date', '<', $end)
            ->where('end_date', '>', $start)
            ->where('end_date', '>', now())
            ->whereNotNull('vehicle.vehicle_id')
            ->where(function ($query) use ($recentPendingCutoff) {
                $query->whereIn('status', [Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING])
                    ->orWhere(function ($pending) use ($recentPendingCutoff) {
                        $pending->where('status', Booking::STATUS_PENDING)
                            ->where('created_at', '>=', $recentPendingCutoff);
                    });
            })
            ->get()
            ->pluck('vehicle.vehicle_id')
            ->map(fn($id) => (string) $id)
            ->unique()
            ->values()
            ->all();

        $vehicles = Vehicle::where('status', '!=', 'maintenance')
            ->whereNotIn('_id', $bookedVehicleIds)
            ->orderBy('created_at', 'desc')
            ->limit(12)
            ->get()
            ->map(fn($v) => [
                'id'             => (string) $v->_id,
                '_id'            => (string) $v->_id,
                'name'           => $v->name,
                'brand'          => $v->brand,
                'model'          => $v->model,
                'year'           => $v->year,
                'type'           => $v->type,
                'capacity'       => $v->capacity,
                'price_per_day'  => $v->price_per_day,
                'status'         => $v->status,
                'features'       => $v->features ?? [],
                'rating_avg'     => $v->rating_avg ?? 0,
                'total_bookings' => $v->total_bookings ?? 0,
                'images'         => collect($v->images ?? [])->map(
                    fn($p) => str_starts_with($p, 'http') ? $p : url('storage/' . $p)
                )->values()->all(),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data'    => $vehicles,
        ]);
    }

    /** Data settings untuk halaman admin landing page */
    public function adminIndex(): JsonResponse
    {
        ['heroSlides' => $heroSlides, 'settings' => $settings] = $this->settingsForWeb(array_keys($this->fields));

        return response()->json([
            'success' => true,
            'data'    => [
                'fields'     => $this->fields,
                'heroSlides' => $heroSlides->map(fn($url, $key) => [
                    'key' => $key,
                    'url' => $url,
                ])->values(),
                'settings'   => $settings,
            ],
        ]);
    }

    public function adminUpdate(Request $request, string $key): JsonResponse
    {
        if (!$this->isAllowedLandingKey($key)) {
            return response()->json(['success' => false, 'message' => 'Key landing tidak valid.'], 422);
        }

        $data = $request->validate([
            'asset_id' => ['required', 'string'],
            'focal_x'  => ['nullable', 'integer', 'min:0', 'max:100'],
            'focal_y'  => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $asset = $this->findAssetForWeb($data['asset_id']);
        if (!$asset) {
            return response()->json(['success' => false, 'message' => 'Asset tidak ditemukan.'], 422);
        }

        $this->removeOldUsageForKey($key);
        $this->setSettingForWeb($key, $this->transformLandingUrl(
            $key,
            $asset['url'],
            (int) ($data['focal_x'] ?? 50),
            (int) ($data['focal_y'] ?? 50),
            $asset['width'] ?? null,
            $asset['height'] ?? null,
        ));
        $this->addAssetUsageByIdForWeb($data['asset_id'], 'landing', $key);
        Cache::forget('welcome:landing:v1');

        return response()->json([
            'success' => true,
            'message' => 'Gambar landing berhasil diperbarui.',
            'data'    => ['key' => $key, 'url' => $this->getSettingValueForWeb($key)],
        ]);
    }

    public function adminStoreSlide(Request $request): JsonResponse
    {
        $data = $request->validate([
            'asset_id' => ['required', 'string'],
            'focal_x'  => ['nullable', 'integer', 'min:0', 'max:100'],
            'focal_y'  => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $asset = $this->findAssetForWeb($data['asset_id']);
        if (!$asset) {
            return response()->json(['success' => false, 'message' => 'Asset tidak ditemukan.'], 422);
        }

        $key = $this->nextSlideKeyForWeb();
        $this->setSettingForWeb($key, $this->transformLandingUrl(
            $key,
            $asset['url'],
            (int) ($data['focal_x'] ?? 50),
            (int) ($data['focal_y'] ?? 50),
            $asset['width'] ?? null,
            $asset['height'] ?? null,
        ));
        $this->addAssetUsageByIdForWeb($data['asset_id'], 'landing', $key);
        Cache::forget('welcome:landing:v1');

        return response()->json([
            'success' => true,
            'message' => 'Slide berhasil ditambahkan.',
            'data'    => ['key' => $key, 'url' => $this->getSettingValueForWeb($key)],
        ], 201);
    }

    public function adminDestroy(string $key): JsonResponse
    {
        if (!$this->isAllowedLandingKey($key)) {
            return response()->json(['success' => false, 'message' => 'Key landing tidak valid.'], 422);
        }

        $this->removeOldUsageForKey($key);
        $this->deleteSettingForWeb($key);
        Cache::forget('welcome:landing:v1');

        return response()->json([
            'success' => true,
            'message' => 'Gambar landing berhasil dihapus.',
        ]);
    }

    public function settingsForWeb(array $fieldKeys): array
    {
        $heroSlides = LandingSetting::where('key', 'regexp', '/^hero_slide_\d+$/')
            ->orderBy('key', 'asc')
            ->get()
            ->mapWithKeys(fn($s) => [$s->key => $s->value]);

        $settingsRows = LandingSetting::whereIn('key', $fieldKeys)->get()->keyBy('key');
        $settings     = array_fill_keys($fieldKeys, null);
        foreach ($fieldKeys as $key) {
            $settings[$key] = $settingsRows->get($key)?->value;
        }

        return compact('heroSlides', 'settings');
    }

    /** Ambil nilai satu setting key */
    public function getSettingValueForWeb(string $key): ?string
    {
        return LandingSetting::get($key);
    }

    /** Simpan nilai satu setting key */
    public function setSettingForWeb(string $key, string $url): void
    {
        LandingSetting::set($key, $url);
    }

    /** Hapus satu setting */
    public function deleteSettingForWeb(string $key): void
    {
        $setting = LandingSetting::where('key', $key)->first();
        if ($setting) $setting->delete();
    }

    /** Key slide berikutnya (untuk upload slide baru) */
    public function nextSlideKeyForWeb(): string
    {
        $keys = LandingSetting::where('key', 'regexp', '/^hero_slide_\d+$/')->pluck('key');
        $max  = $keys->map(fn($k) => (int) str_replace('hero_slide_', '', $k))->max() ?? 0;
        return 'hero_slide_' . ($max + 1);
    }

    /** Cari Asset berdasarkan public_id (untuk cleanup usage setelah Cloudinary delete) */
    public function findAssetByPublicIdForWeb(string $publicId): ?array
    {
        $asset = Asset::where('public_id', $publicId)->first();
        return $asset ? ['id' => (string) $asset->_id, 'url' => $asset->url] : null;
    }

    /** Hapus usage suatu asset dari konteks landing page */
    public function removeAssetUsageForWeb(string $publicId, string $type, string $key): void
    {
        $asset = Asset::where('public_id', $publicId)->first();
        if ($asset) $asset->removeUsage($type, $key);
    }

    /** Buat record Asset baru */
    public function createAssetForWeb(array $data): string
    {
        $asset = Asset::create($data);
        return (string) $asset->_id;
    }

    /** Buat atau update record Asset */
    public function upsertAssetForWeb(array $match, array $data): void
    {
        Asset::updateOrCreate($match, $data);
    }

    /** Cari Asset berdasarkan ID, return data dasar */
    public function findAssetForWeb(string $id): ?array
    {
        $asset = Asset::find($id);
        return $asset ? [
            'id'        => (string) $asset->_id,
            'url'       => $asset->url,
            'public_id' => $asset->public_id,
            'width'     => $asset->width,
            'height'    => $asset->height,
        ] : null;
    }

    /** Tambah usage ke Asset berdasarkan ID */
    public function addAssetUsageByIdForWeb(string $assetId, string $type, string $key): void
    {
        $asset = Asset::find($assetId);
        if ($asset) $asset->addUsage($type, $key);
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

    private function isAllowedLandingKey(string $key): bool
    {
        return array_key_exists($key, $this->fields) || preg_match('/^hero_slide_\d+$/', $key);
    }

    private function removeOldUsageForKey(string $key): void
    {
        $old = $this->getSettingValueForWeb($key);
        if (!$old) return;

        $publicId = CloudinaryService::publicIdFromUrl($old);
        if ($publicId) {
            $this->removeAssetUsageForWeb($publicId, 'landing', $key);
        }
    }
}
