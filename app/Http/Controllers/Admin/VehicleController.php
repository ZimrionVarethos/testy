<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\VehicleController as ApiVehicle;
use App\Http\Controllers\Controller;
use App\Http\Traits\WebApiProxy;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class VehicleController extends Controller
{
    use WebApiProxy;

    public function __construct(protected CloudinaryService $cloudinary) {}

    // ── PUBLIC ACTIONS ─────────────────────────────────────────────────────

    public function index(Request $request, ApiVehicle $api)
    {
        $status = $request->query('status');
        $req    = $this->makeApiRequest(array_filter(['status' => $status]));
        ['vehicles' => $vehicles] = $api->indexForWeb($req);

        return view('admin.vehicles.index', compact('vehicles', 'status'));
    }

    public function create()
    {
        return view('admin.vehicles.create');
    }

    public function store(Request $request, ApiVehicle $api)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'brand'         => 'required|string|max:50',
            'model'         => 'required|string|max:50',
            'year'          => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'plate_number'  => 'required|string|unique:mongodb.vehicles,plate_number',
            'type'          => 'required|in:MPV,SUV,Van,Sedan,Minibus',
            'capacity'      => 'required|integer|min:2|max:20',
            'price_per_day' => 'required|integer|min:100000',
            'images.0'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'asset_id'      => 'nullable|string',
        ]);

        $data['features']       = $this->parseFeatures($request->input('features_raw', ''));
        $data['status']         = 'available';
        $data['rating_avg']     = 0;
        $data['total_bookings'] = 0;
        $data['images']         = [];

        $cloudinaryPublicId = null;
        $pickedAssetId      = null;

        // Infrastruktur: upload ke Cloudinary ada di Web layer
        if ($request->hasFile('images.0')) {
            $result = $this->uploadVehicleImage(
                $request->file('images.0'),
                (int) $request->input('new_focal_x', 50),
                (int) $request->input('new_focal_y', 50),
            );
            $data['images'][]   = $this->applyFocalToUrl($result['url'], (int) $request->input('new_focal_x', 50), (int) $request->input('new_focal_y', 50), $result['width'] ?? null, $result['height'] ?? null);
            $cloudinaryPublicId = $result['public_id'];

        } elseif ($assetId = $request->input('asset_id')) {
            // Ambil URL asset dari API — TIDAK query langsung ke MongoDB
            $assetData = $api->getAssetForPickerForWeb($assetId);
            if ($assetData) {
                $data['images'][] = $this->applyFocalToUrl(
                    $assetData['url'],
                    (int) $request->input('new_focal_x', 50),
                    (int) $request->input('new_focal_y', 50),
                    $assetData['width'] ?? null,
                    $assetData['height'] ?? null,
                );
                $pickedAssetId = $assetId;
            }
        }

        unset($data['features_raw'], $data['asset_id']);

        // Semua operasi MongoDB ada di API layer
        $api->storeVehicleForWeb($data, $cloudinaryPublicId, $pickedAssetId);
        Cache::forget('welcome:landing:v1');

        return redirect()->route('admin.vehicles.index')
            ->with('success', 'Kendaraan berhasil ditambahkan.');
    }

    public function edit(string $id, ApiVehicle $api)
    {
        $vehicle = $api->showForWeb($id);
        return view('admin.vehicles.edit', compact('vehicle'));
    }

    public function update(Request $request, string $id, ApiVehicle $api)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'brand'         => 'required|string|max:50',
            'model'         => 'required|string|max:50',
            'year'          => 'required|integer|min:2000',
            'plate_number'  => 'required|string',
            'type'          => 'required|in:MPV,SUV,Van,Sedan,Minibus',
            'capacity'      => 'required|integer|min:2|max:20',
            'price_per_day' => 'required|integer|min:100000',
            'status'        => 'required|in:available,rented,maintenance',
            'images.0'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'asset_id'      => 'nullable|string',
        ]);

        $data['features'] = $this->parseFeatures($request->input('features_raw', ''));

        $keptUrl  = $request->input('kept_images.0');

        // Ambil data kendaraan lama dari API — TIDAK query MongoDB langsung
        $vehicleData = $api->getVehicleDataForWeb($id);
        $oldImages   = $vehicleData['images'];

        // Infrastruktur: hapus gambar lama dari Cloudinary (di Web layer)
        $removedPublicIds = [];
        foreach ($oldImages as $oldUrl) {
            if ($oldUrl === $keptUrl) continue;
            $publicId = CloudinaryService::publicIdFromUrl($oldUrl);
            if ($publicId) {
                $this->cloudinary->delete($publicId);
                $removedPublicIds[] = $publicId;
            }
        }

        $finalImages        = [];
        $newCloudinaryPubId = null;
        $pickedAssetId      = null;

        if ($keptUrl) {
            $x = (int) $request->input('kept_focal_x', 50);
            $y = (int) $request->input('kept_focal_y', 50);
            $finalImages[] = $this->applyFocalToUrl($keptUrl, $x, $y);
        }

        // Infrastruktur: upload gambar baru ke Cloudinary (di Web layer)
        if ($request->hasFile('images.0')) {
            $result = $this->uploadVehicleImage(
                $request->file('images.0'),
                (int) $request->input('new_focal_x', 50),
                (int) $request->input('new_focal_y', 50),
            );
            $finalImages[]      = $this->applyFocalToUrl($result['url'], (int) $request->input('new_focal_x', 50), (int) $request->input('new_focal_y', 50), $result['width'] ?? null, $result['height'] ?? null);
            $newCloudinaryPubId = $result['public_id'];

        } elseif ($assetId = $request->input('asset_id')) {
            // Ambil URL asset dari API — TIDAK query MongoDB langsung
            $assetData = $api->getAssetForPickerForWeb($assetId);
            if ($assetData) {
                $finalImages[] = $this->applyFocalToUrl(
                    $assetData['url'],
                    (int) $request->input('new_focal_x', 50),
                    (int) $request->input('new_focal_y', 50),
                    $assetData['width'] ?? null,
                    $assetData['height'] ?? null,
                );
                $pickedAssetId = $assetId;
            }
        }

        $data['images'] = $finalImages;
        unset($data['features_raw'], $data['asset_id']);

        // Semua operasi MongoDB ada di API layer
        $api->updateVehicleForWeb($id, $data, $newCloudinaryPubId, $removedPublicIds, $pickedAssetId);
        Cache::forget('welcome:landing:v1');

        return redirect()->route('admin.vehicles.index')
            ->with('success', 'Kendaraan berhasil diupdate.');
    }

    public function destroy(string $id, ApiVehicle $api)
    {
        // Ambil data kendaraan dari API — TIDAK query MongoDB langsung
        $vehicleData = $api->getVehicleDataForWeb($id);

        if ($vehicleData['status'] === 'rented') {
            return back()->withErrors(['error' => 'Tidak bisa hapus kendaraan yang sedang disewa.']);
        }

        // Infrastruktur: hapus gambar dari Cloudinary (di Web layer)
        $removedPublicIds = [];
        foreach ($vehicleData['images'] as $url) {
            $publicId = CloudinaryService::publicIdFromUrl($url);
            if ($publicId) {
                $this->cloudinary->delete($publicId);
                $removedPublicIds[] = $publicId;
            }
        }

        // Operasi MongoDB (hapus vehicle + cleanup asset usage) ada di API layer
        $result = $api->deleteVehicleForWeb($id, $removedPublicIds);

        if (!$result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        Cache::forget('welcome:landing:v1');

        return redirect()->route('admin.vehicles.index')
            ->with('success', 'Kendaraan dihapus.');
    }

    // ── PRIVATE HELPERS ────────────────────────────────────────────────────

    private function uploadVehicleImage(UploadedFile $file, int $x, int $y): array
    {
        return $this->cloudinary->upload($file, 'vehicles', [
            'context' => "focal_x={$x}|focal_y={$y}",
        ]);
    }

    private function applyFocalToUrl(string $url, int $x, int $y, ?int $sourceWidth = null, ?int $sourceHeight = null): string
    {
        if ($sourceWidth && $sourceHeight) {
            $px = max(1, min($sourceWidth, (int) round($sourceWidth * $x / 100)));
            $py = max(1, min($sourceHeight, (int) round($sourceHeight * $y / 100)));
            return CloudinaryService::transformUrl($url, "c_fill,w_1200,h_800,g_xy_center,x_{$px},y_{$py},f_auto,q_auto");
        }

        return CloudinaryService::transformUrl($url, 'c_fill,w_1200,h_800,g_auto,f_auto,q_auto');
    }

    private function parseFeatures(string $raw): array
    {
        return array_values(array_filter(
            array_map('trim', explode(',', $raw))
        ));
    }
}
