<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\VehicleController as ApiVehicle;
use App\Http\Traits\WebApiProxy;
use App\Models\Asset;
use App\Models\Vehicle;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

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

    public function store(Request $request)
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
            // Upload baru
            'images.0'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            // Atau pilih dari Asset Picker (public_id dari koleksi Asset)
            'asset_id'      => 'nullable|string',
        ]);

        $data['features']       = $this->parseFeatures($request->input('features_raw', ''));
        $data['status']         = 'available';
        $data['rating_avg']     = 0;
        $data['total_bookings'] = 0;
        $data['images']         = [];

        // Prioritas: upload baru → pilih dari asset picker
        if ($request->hasFile('images.0')) {
            $result = $this->uploadVehicleImage(
                $request->file('images.0'),
                (int) $request->input('new_focal_x', 50),
                (int) $request->input('new_focal_y', 50),
            );
            $data['images'][] = $result['url'];

            // Catat pemakaian di Asset record
            if ($asset = Asset::where('public_id', $result['public_id'])->first()) {
                $asset->addUsage('vehicle', 'new');
            }

        } elseif ($assetId = $request->input('asset_id')) {
            $asset = Asset::findOrFail($assetId);
            $data['images'][] = $this->applyFocalToUrl(
                $asset->url,
                (int) $request->input('new_focal_x', 50),
                (int) $request->input('new_focal_y', 50),
            );
        }

        unset($data['features_raw'], $data['asset_id']);
        $vehicle = Vehicle::create($data);

        // Update usage setelah vehicle punya ID
        if ($assetId ?? false) {
            $asset->addUsage('vehicle', $vehicle->id);
        }

        return redirect()->route('admin.vehicles.index')
            ->with('success', 'Kendaraan berhasil ditambahkan.');
    }

    public function edit(string $id, ApiVehicle $api)
    {
        $vehicle = $api->showForWeb($id);
        return view('admin.vehicles.edit', compact('vehicle'));
    }

    public function update(Request $request, string $id)
    {
        $vehicle = Vehicle::findOrFail($id);

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

        $keptUrl  = $request->input('kept_images.0'); // URL Cloudinary yang dipertahankan
        $oldImages = $vehicle->images ?? [];

        // Hapus gambar lama yang tidak dipertahankan dari Cloudinary + Asset usage
        foreach ($oldImages as $oldUrl) {
            if ($oldUrl === $keptUrl) continue;

            $publicId = CloudinaryService::publicIdFromUrl($oldUrl);
            if ($publicId) {
                $this->cloudinary->delete($publicId);

                // Bersihkan usage di Asset collection
                if ($asset = Asset::where('public_id', $publicId)->first()) {
                    $asset->removeUsage('vehicle', $vehicle->id);
                }
            }
        }

        $finalImages = [];

        if ($keptUrl) {
            // Gambar lama dipertahankan — update focal jika berubah
            $x = (int) $request->input('kept_focal_x', 50);
            $y = (int) $request->input('kept_focal_y', 50);
            $finalImages[] = $this->applyFocalToUrl($keptUrl, $x, $y);
        }

        // Upload baru
        if ($request->hasFile('images.0')) {
            $result = $this->uploadVehicleImage(
                $request->file('images.0'),
                (int) $request->input('new_focal_x', 50),
                (int) $request->input('new_focal_y', 50),
            );
            $finalImages[] = $result['url'];

            if ($asset = Asset::where('public_id', $result['public_id'])->first()) {
                $asset->addUsage('vehicle', $vehicle->id);
            }

        } elseif ($assetId = $request->input('asset_id')) {
            // Pilih dari Asset Picker
            $asset = Asset::findOrFail($assetId);
            $finalImages[] = $this->applyFocalToUrl(
                $asset->url,
                (int) $request->input('new_focal_x', 50),
                (int) $request->input('new_focal_y', 50),
            );
            $asset->addUsage('vehicle', $vehicle->id);
        }

        $data['images'] = $finalImages;
        unset($data['features_raw'], $data['asset_id']);
        $vehicle->update($data);

        return redirect()->route('admin.vehicles.index')
            ->with('success', 'Kendaraan berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        if ($vehicle->status === 'rented') {
            return back()->withErrors(['error' => 'Tidak bisa hapus kendaraan yang sedang disewa.']);
        }

        // Hapus gambar dari Cloudinary
        foreach ($vehicle->images ?? [] as $url) {
            $publicId = CloudinaryService::publicIdFromUrl($url);
            if ($publicId) {
                $this->cloudinary->delete($publicId);
                if ($asset = Asset::where('public_id', $publicId)->first()) {
                    $asset->removeUsage('vehicle', $vehicle->id);
                }
            }
        }

        $vehicle->delete();

        return redirect()->route('admin.vehicles.index')
            ->with('success', 'Kendaraan dihapus.');
    }

    // ── PRIVATE HELPERS ────────────────────────────────────────────────────

    /**
     * Upload gambar kendaraan ke Cloudinary dengan focal point di context metadata.
     * Focal point disimpan di context Cloudinary, bukan di nama file lagi.
     */
    private function uploadVehicleImage(UploadedFile $file, int $x, int $y): array
    {
        return $this->cloudinary->upload($file, 'vehicles', [
            'context' => "focal_x={$x}|focal_y={$y}",
        ]);
    }

    /**
     * Sisipkan focal-point sebagai Cloudinary transformation parameter di URL.
     * Format: .../upload/c_fill,g_xy_center,x_{x},y_{y}/...
     * Untuk sekarang kita simpan sebagai query param custom di URL (non-transformasi)
     * karena focal point sudah di context Cloudinary — cukup return URL apa adanya.
     */
    private function applyFocalToUrl(string $url, int $x, int $y): string
    {
        // Focal point di Cloudinary bisa disisipkan via `g_auto` atau custom crop.
        // Untuk sederhananya: kita tambahkan transformation c_fill,g_auto ke URL.
        // Contoh: .../upload/c_fill,ar_4:3,g_auto/...
        // Karena crop tergantung konteks tampilan, return URL asli + context saja.
        return $url;
    }

    /**
     * Parse focal point dari URL (legacy support).
     */
    private function parseFocalFromUrl(string $url): array
    {
        // Coba baca dari Cloudinary context — untuk sekarang default 50/50
        return [50, 50];
    }

    /**
     * Parse string CSV fitur menjadi array bersih.
     */
    private function parseFeatures(string $raw): array
    {
        return array_values(array_filter(
            array_map('trim', explode(',', $raw))
        ));
    }
}