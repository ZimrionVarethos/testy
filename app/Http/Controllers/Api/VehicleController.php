<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreVehicleRequest;
use App\Http\Requests\Api\UpdateVehicleRequest;
use App\Models\Asset;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class VehicleController extends Controller
{
    /**
     * GET /api/v1/vehicles
     * Query params: status, type, min_price, max_price, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $query = Vehicle::query();

        if ($request->filled('status'))    $query->where('status', $request->status);
        if ($request->filled('type'))      $query->where('type', $request->type);
        if ($request->filled('min_price')) $query->where('price_per_day', '>=', (int) $request->min_price);
        if ($request->filled('max_price')) $query->where('price_per_day', '<=', (int) $request->max_price);

        $perPage  = min((int) $request->get('per_page', 12), 50);
        $vehicles = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $vehicles->map(fn($v) => $this->vehicleResource($v)),
            'meta'    => [
                'current_page' => $vehicles->currentPage(),
                'last_page'    => $vehicles->lastPage(),
                'per_page'     => $vehicles->perPage(),
                'total'        => $vehicles->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/vehicles/{id}
     */
    public function show(string $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $this->vehicleResource($vehicle),
        ]);
    }

    /**
     * POST /api/v1/vehicles (Admin)
     */
    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->filled('features_raw')) {
            $data['features'] = array_filter(array_map('trim', explode(',', $request->features_raw)));
            unset($data['features_raw']);
        }

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $focalX = (int) $request->get('new_focal_x', 50);
                $focalY = (int) $request->get('new_focal_y', 50);
                $name   = 'vehicles/' . uniqid() . "_{$focalX}-{$focalY}." . $img->extension();
                $img->storeAs('public', $name);
                $imagePaths[] = $name;
            }
        }

        $data['images']         = $imagePaths;
        $data['status']         = 'available';
        $data['rating_avg']     = 0;
        $data['total_bookings'] = 0;

        $vehicle = Vehicle::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Kendaraan berhasil ditambahkan.',
            'data'    => $this->vehicleResource($vehicle),
        ], 201);
    }

    /**
     * PUT /api/v1/vehicles/{id} (Admin)
     */
    public function update(UpdateVehicleRequest $request, string $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);
        $data    = $request->validated();

        if ($request->filled('features_raw')) {
            $data['features'] = array_filter(array_map('trim', explode(',', $request->features_raw)));
            unset($data['features_raw']);
        }

        if ($request->hasFile('images')) {
            foreach ($vehicle->images ?? [] as $oldPath) {
                Storage::delete('public/' . $oldPath);
            }
            $imagePaths = [];
            foreach ($request->file('images') as $img) {
                $focalX = (int) $request->get('new_focal_x', 50);
                $focalY = (int) $request->get('new_focal_y', 50);
                $name   = 'vehicles/' . uniqid() . "_{$focalX}-{$focalY}." . $img->extension();
                $img->storeAs('public', $name);
                $imagePaths[] = $name;
            }
            $data['images'] = $imagePaths;
        } elseif ($request->has('kept_images')) {
            $data['images'] = $request->kept_images;
        }

        $vehicle->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Kendaraan berhasil diperbarui.',
            'data'    => $this->vehicleResource($vehicle->fresh()),
        ]);
    }

    /**
     * DELETE /api/v1/vehicles/{id} (Admin)
     */
    public function destroy(string $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);

        if ($vehicle->status === 'rented') {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa hapus kendaraan yang sedang disewa.',
            ], 422);
        }

        foreach ($vehicle->images ?? [] as $path) {
            Storage::delete('public/' . $path);
        }

        $vehicle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kendaraan berhasil dihapus.',
        ]);
    }

    public function adminStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'brand'         => ['required', 'string', 'max:50'],
            'model'         => ['required', 'string', 'max:50'],
            'year'          => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 1)],
            'plate_number'  => ['required', 'string', 'max:20', 'unique:vehicles,plate_number'],
            'type'          => ['required', 'in:MPV,SUV,Van,Sedan,Minibus'],
            'capacity'      => ['required', 'integer', 'min:2', 'max:20'],
            'price_per_day' => ['required', 'integer', 'min:100000'],
            'features_raw'  => ['nullable', 'string'],
            'asset_id'      => ['nullable', 'string'],
            'focal_x'       => ['nullable', 'integer', 'min:0', 'max:100'],
            'focal_y'       => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $assetId = $data['asset_id'] ?? null;
        $data['features'] = $this->parseFeatures($data['features_raw'] ?? '');
        $data['status'] = 'available';
        $data['rating_avg'] = 0;
        $data['total_bookings'] = 0;
        $data['images'] = [];

        if ($assetId) {
            $asset = $this->getAssetForPickerForWeb($assetId);
            if (!$asset) {
                return response()->json(['success' => false, 'message' => 'Asset tidak ditemukan.'], 422);
            }

            $data['images'][] = $this->applyFocalToUrl(
                $asset['url'],
                (int) ($data['focal_x'] ?? 50),
                (int) ($data['focal_y'] ?? 50),
                $asset['width'] ?? null,
                $asset['height'] ?? null,
            );
        }

        unset($data['features_raw'], $data['asset_id'], $data['focal_x'], $data['focal_y']);

        $result = $this->storeVehicleForWeb($data, null, $assetId);
        Cache::forget('welcome:landing:v1');

        return response()->json([
            'success' => true,
            'message' => 'Kendaraan berhasil ditambahkan.',
            'data'    => ['id' => $result['id']],
        ], 201);
    }

    public function adminUpdate(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'brand'         => ['required', 'string', 'max:50'],
            'model'         => ['required', 'string', 'max:50'],
            'year'          => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 1)],
            'plate_number'  => ['required', 'string', 'max:20'],
            'type'          => ['required', 'in:MPV,SUV,Van,Sedan,Minibus'],
            'capacity'      => ['required', 'integer', 'min:2', 'max:20'],
            'price_per_day' => ['required', 'integer', 'min:100000'],
            'status'        => ['required', 'in:available,rented,maintenance'],
            'features_raw'  => ['nullable', 'string'],
            'asset_id'      => ['nullable', 'string'],
            'kept_image'    => ['nullable', 'string'],
            'focal_x'       => ['nullable', 'integer', 'min:0', 'max:100'],
            'focal_y'       => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $vehicleData = $this->getVehicleDataForWeb($id);
        $oldPublicIds = collect($vehicleData['images'] ?? [])
            ->map(fn($url) => \App\Services\CloudinaryService::publicIdFromUrl($url))
            ->filter()
            ->values()
            ->all();

        $assetId = $data['asset_id'] ?? null;
        $finalImages = [];

        if ($assetId) {
            $asset = $this->getAssetForPickerForWeb($assetId);
            if (!$asset) {
                return response()->json(['success' => false, 'message' => 'Asset tidak ditemukan.'], 422);
            }

            $finalImages[] = $this->applyFocalToUrl(
                $asset['url'],
                (int) ($data['focal_x'] ?? 50),
                (int) ($data['focal_y'] ?? 50),
                $asset['width'] ?? null,
                $asset['height'] ?? null,
            );
        } elseif (!empty($data['kept_image'])) {
            $finalImages[] = $data['kept_image'];
        }

        $data['features'] = $this->parseFeatures($data['features_raw'] ?? '');
        $data['images'] = $finalImages;
        unset($data['features_raw'], $data['asset_id'], $data['kept_image'], $data['focal_x'], $data['focal_y']);

        $newPublicIds = collect($finalImages)
            ->map(fn($url) => \App\Services\CloudinaryService::publicIdFromUrl($url))
            ->filter()
            ->values()
            ->all();
        $removedPublicIds = array_values(array_diff($oldPublicIds, $newPublicIds));

        $vehicle = $this->updateVehicleForWeb($id, $data, null, $removedPublicIds, $assetId);
        Cache::forget('welcome:landing:v1');

        return response()->json([
            'success' => true,
            'message' => 'Kendaraan berhasil diperbarui.',
            'data'    => $this->vehicleResource($vehicle),
        ]);
    }

    public function adminDestroy(string $id): JsonResponse
    {
        $vehicleData = $this->getVehicleDataForWeb($id);
        $removedPublicIds = collect($vehicleData['images'] ?? [])
            ->map(fn($url) => \App\Services\CloudinaryService::publicIdFromUrl($url))
            ->filter()
            ->values()
            ->all();

        $result = $this->deleteVehicleForWeb($id, $removedPublicIds);
        Cache::forget('welcome:landing:v1');

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $result['success'] ? 200 : 422);
    }

    // ── ForWeb (dipakai web controller langsung, bukan HTTP) ─────────

    /** Untuk web: daftar kendaraan tersedia di rentang tanggal */
    public function indexForWeb(Request $request): array
    {
        $query = Vehicle::query();

        if ($request->filled('status'))    $query->where('status', $request->status);
        if ($request->filled('type'))      $query->where('type', $request->type);
        if ($request->filled('min_price')) $query->where('price_per_day', '>=', (int) $request->min_price);
        if ($request->filled('max_price')) $query->where('price_per_day', '<=', (int) $request->max_price);

        $vehicles = $query->orderBy($request->get('sort', 'price_per_day'))->paginate(
            min((int) $request->get('per_page', 9), 50)
        );

        return compact('vehicles');
    }

    /** Untuk web: detail satu kendaraan */
    public function showForWeb(string $id): Vehicle
    {
        return Vehicle::findOrFail($id);
    }

    // ── ForWeb CRUD — dipakai Admin/VehicleController (tanpa HTTP, in-process) ───

    /**
     * Ambil data kendaraan (images + status) untuk keperluan edit/destroy di Web controller.
     * Web controller butuh daftar URL lama sebelum menghapus dari Cloudinary.
     */
    public function getVehicleDataForWeb(string $id): array
    {
        $vehicle = Vehicle::findOrFail($id);
        return [
            'id'     => (string) $vehicle->_id,
            'status' => $vehicle->status,
            'images' => $vehicle->images ?? [],
        ];
    }

    /**
     * Ambil data asset dari picker (URL + public_id) — Web controller butuh URL ini
     * sebelum bisa memanggil applyFocalToUrl().
     */
    public function getAssetForPickerForWeb(string $assetId): ?array
    {
        $asset = Asset::find($assetId);
        return $asset ? [
            'url'       => $asset->url,
            'public_id' => $asset->public_id,
            'width'     => $asset->width,
            'height'    => $asset->height,
        ] : null;
    }

    /**
     * Tambah usage ke Asset berdasarkan public_id (setelah Cloudinary upload di Web).
     */
    public function addAssetUsageByPublicIdForWeb(string $publicId, string $type, string $entityId): void
    {
        $asset = Asset::where('public_id', $publicId)->first();
        if ($asset) $asset->addUsage($type, $entityId);
    }

    /**
     * Hapus usage dari Asset berdasarkan public_id (setelah Cloudinary delete di Web).
     */
    public function removeAssetUsageByPublicIdForWeb(string $publicId, string $type, string $entityId): void
    {
        $asset = Asset::where('public_id', $publicId)->first();
        if ($asset) $asset->removeUsage($type, $entityId);
    }

    /**
     * Tambah usage ke Asset berdasarkan _id (untuk asset picker path).
     */
    public function addAssetUsageByIdForWeb(string $assetId, string $type, string $entityId): void
    {
        $asset = Asset::find($assetId);
        if ($asset) $asset->addUsage($type, $entityId);
    }

    /**
     * Buat record Vehicle baru di MongoDB.
     * Web controller sudah handle Cloudinary upload + focal point sebelum memanggil ini.
     */
    public function storeVehicleForWeb(array $vehicleData, ?string $cloudinaryPublicId, ?string $pickedAssetId): array
    {
        $vehicle = Vehicle::create($vehicleData);
        $vehicleId = (string) $vehicle->_id;

        if ($cloudinaryPublicId) {
            $this->addAssetUsageByPublicIdForWeb($cloudinaryPublicId, 'vehicle', $vehicleId);
        } elseif ($pickedAssetId) {
            $this->addAssetUsageByIdForWeb($pickedAssetId, 'vehicle', $vehicleId);
        }

        return ['id' => $vehicleId];
    }

    /**
     * Update record Vehicle di MongoDB.
     * Web controller sudah handle Cloudinary (hapus lama, upload baru) sebelum memanggil ini.
     */
    public function updateVehicleForWeb(string $id, array $vehicleData, ?string $newCloudinaryPublicId, array $removedPublicIds, ?string $pickedAssetId): Vehicle
    {
        $vehicle = Vehicle::findOrFail($id);

        // Bersihkan usage asset yang dihapus
        foreach ($removedPublicIds as $publicId) {
            $this->removeAssetUsageByPublicIdForWeb($publicId, 'vehicle', $id);
        }

        $vehicle->update($vehicleData);

        // Catat usage asset baru
        if ($newCloudinaryPublicId) {
            $this->addAssetUsageByPublicIdForWeb($newCloudinaryPublicId, 'vehicle', $id);
        } elseif ($pickedAssetId) {
            $this->addAssetUsageByIdForWeb($pickedAssetId, 'vehicle', $id);
        }

        return $vehicle->fresh();
    }

    /**
     * Hapus record Vehicle dari MongoDB.
     * Sebelum memanggil ini, Web controller sudah hapus gambar dari Cloudinary.
     * Return: ['success', 'message']
     */
    public function deleteVehicleForWeb(string $id, array $removedPublicIds): array
    {
        $vehicle = Vehicle::findOrFail($id);

        if ($vehicle->status === 'rented') {
            return ['success' => false, 'message' => 'Tidak bisa hapus kendaraan yang sedang disewa.'];
        }

        foreach ($removedPublicIds as $publicId) {
            $this->removeAssetUsageByPublicIdForWeb($publicId, 'vehicle', $id);
        }

        $vehicle->delete();

        return ['success' => true, 'message' => 'Kendaraan berhasil dihapus.'];
    }

    // ── Helper ────────────────────────────────────────────────────

    private function vehicleResource(Vehicle $v): array
    {
        return [
            'id'            => (string) $v->_id,
            'name'          => $v->name,
            'brand'         => $v->brand,
            'model'         => $v->model,
            'year'          => $v->year,
            'plate_number'  => $v->plate_number,
            'type'          => $v->type,
            'capacity'      => $v->capacity,
            'price_per_day' => $v->price_per_day,
            'status'        => $v->status,
            'features'      => $v->features ?? [],
            'rating_avg'    => $v->rating_avg ?? 0,
            'total_bookings'=> $v->total_bookings ?? 0,
            'images'        => collect($v->images ?? [])->map(
                fn($path) => str_starts_with($path, 'http') ? $path : url('storage/' . $path)
            )->values()->all(),
            'created_at'    => $v->created_at?->toIso8601String(),
        ];
    }

    private function parseFeatures(string $raw): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    private function applyFocalToUrl(string $url, int $x, int $y, ?int $sourceWidth = null, ?int $sourceHeight = null): string
    {
        if ($sourceWidth && $sourceHeight) {
            $px = max(1, min($sourceWidth, (int) round($sourceWidth * $x / 100)));
            $py = max(1, min($sourceHeight, (int) round($sourceHeight * $y / 100)));
            return \App\Services\CloudinaryService::transformUrl($url, "c_fill,w_1200,h_800,g_xy_center,x_{$px},y_{$py},f_auto,q_auto");
        }

        return \App\Services\CloudinaryService::transformUrl($url, 'c_fill,w_1200,h_800,g_auto,f_auto,q_auto');
    }
}
