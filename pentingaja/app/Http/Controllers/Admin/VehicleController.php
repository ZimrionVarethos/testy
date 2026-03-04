<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class VehicleController extends Controller
{
    // ──────────────────────────────────────────────
    //  PUBLIC ACTIONS
    // ──────────────────────────────────────────────

    public function index(Request $request)
    {
        $status   = $request->query('status');
        $query    = Vehicle::orderBy('created_at', 'desc');
        if ($status) $query->where('status', $status);
        $vehicles = $query->paginate(12);

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
            // Satu file saja
            'images.0'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data['features'] = $this->parseFeatures($request->input('features_raw', ''));

        $imagePaths = [];
        if ($request->hasFile('images.0')) {
            $x            = (int) $request->input('new_focal_x', 50);
            $y            = (int) $request->input('new_focal_y', 50);
            $imagePaths[] = $this->storeImageWithFocal($request->file('images.0'), $x, $y);
        }

        $data['status']         = 'available';
        $data['rating_avg']     = 0;
        $data['total_bookings'] = 0;
        $data['images']         = $imagePaths;

        unset($data['features_raw']);
        Vehicle::create($data);

        return redirect()->route('admin.vehicles.index')
                         ->with('success', 'Kendaraan berhasil ditambahkan.');
    }

    public function edit(string $id)
    {
        $vehicle = Vehicle::findOrFail($id);
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
            // Satu file saja
            'images.0'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data['features'] = $this->parseFeatures($request->input('features_raw', ''));

        $keptPath = $request->input('kept_images.0'); // satu path lama (atau null)

        // Hapus gambar lama dari storage jika user menghapusnya
        foreach ($vehicle->images ?? [] as $oldPath) {
            if ($oldPath !== $keptPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $finalImages = [];

        if ($keptPath) {
            // Gambar lama dipertahankan — cek apakah focal berubah
            $x = (int) $request->input('kept_focal_x', 50);
            $y = (int) $request->input('kept_focal_y', 50);

            [$oldX, $oldY] = $this->parseFocalFromPath($keptPath);

            if ($x !== $oldX || $y !== $oldY) {
                $finalImages[] = $this->renameWithNewFocal($keptPath, $x, $y);
            } else {
                $finalImages[] = $keptPath;
            }
        }

        // Upload gambar baru (menggantikan yang dihapus)
        if ($request->hasFile('images.0')) {
            $x             = (int) $request->input('new_focal_x', 50);
            $y             = (int) $request->input('new_focal_y', 50);
            $finalImages[] = $this->storeImageWithFocal($request->file('images.0'), $x, $y);
        }

        $data['images'] = $finalImages;

        unset($data['features_raw']);
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

        foreach ($vehicle->images ?? [] as $img) {
            Storage::disk('public')->delete($img);
        }

        $vehicle->delete();

        return redirect()->route('admin.vehicles.index')
                         ->with('success', 'Kendaraan dihapus.');
    }

    // ──────────────────────────────────────────────
    //  PRIVATE HELPERS
    // ──────────────────────────────────────────────

    /**
     * Upload gambar ke storage dengan focal point disimpan di nama file.
     * Format: {safeName}_{x}-{y}_{uniqid}.{ext}
     * Contoh: innova_reborn_50-30_6612a3f.jpg
     */
    private function storeImageWithFocal(UploadedFile $file, int $x, int $y): string
    {
        $ext      = $file->getClientOriginalExtension();
        $rawName  = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $rawName);
        $filename = $safeName . '_' . $x . '-' . $y . '_' . uniqid() . '.' . $ext;

        $file->storeAs('vehicles', $filename, 'public');

        return 'vehicles/' . $filename;
    }

    /**
     * Rename file lama dengan focal point baru.
     */
    private function renameWithNewFocal(string $oldPath, int $x, int $y): string
    {
        $ext     = pathinfo($oldPath, PATHINFO_EXTENSION);
        $dir     = pathinfo($oldPath, PATHINFO_DIRNAME);
        $base    = pathinfo($oldPath, PATHINFO_FILENAME);

        $newBase = preg_replace('/_\d+-\d+(_[a-f0-9]+)?$/', '', $base);
        $newName = $newBase . '_' . $x . '-' . $y . '_' . uniqid() . '.' . $ext;
        $newPath = ($dir !== '.' ? $dir . '/' : '') . $newName;

        Storage::disk('public')->move($oldPath, $newPath);

        return $newPath;
    }

    /**
     * Parse focal point dari nama file.
     * "vehicles/innova_50-75_abc.jpg" → [50, 75]
     */
    private function parseFocalFromPath(string $path): array
    {
        $base = pathinfo($path, PATHINFO_FILENAME);
        if (preg_match('/_(\d+)-(\d+)/', $base, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }
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