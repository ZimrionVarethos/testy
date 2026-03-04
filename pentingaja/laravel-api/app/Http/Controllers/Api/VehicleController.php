<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreVehicleRequest;
use App\Http\Requests\Api\UpdateVehicleRequest;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VehicleController extends Controller
{
    /**
     * Daftar kendaraan (dengan filter & pagination).
     *
     * Query params:
     *   status   : available | rented | maintenance
     *   type     : MPV | SUV | Van | Sedan | Minibus
     *   min_price: integer
     *   max_price: integer
     *   per_page : integer (default 12)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Vehicle::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('min_price')) {
            $query->where('price_per_day', '>=', (int) $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price_per_day', '<=', (int) $request->max_price);
        }

        $perPage   = min((int) $request->get('per_page', 12), 50);
        $vehicles  = $query->orderBy('created_at', 'desc')->paginate($perPage);

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
     * Detail kendaraan.
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
     * Tambah kendaraan baru (Admin).
     */
    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Handle features
        if ($request->filled('features_raw')) {
            $data['features'] = array_map('trim', explode(',', $request->features_raw));
            unset($data['features_raw']);
        }

        // Handle foto
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
        $data['images']     = $imagePaths;
        $data['status']     = 'available';
        $data['rating_avg'] = 0;

        $vehicle = Vehicle::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Kendaraan berhasil ditambahkan.',
            'data'    => $this->vehicleResource($vehicle),
        ], 201);
    }

    /**
     * Update kendaraan (Admin).
     */
    public function update(UpdateVehicleRequest $request, string $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);
        $data    = $request->validated();

        if ($request->filled('features_raw')) {
            $data['features'] = array_map('trim', explode(',', $request->features_raw));
            unset($data['features_raw']);
        }

        // Handle foto baru
        if ($request->hasFile('images')) {
            // Hapus foto lama
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
     * Hapus kendaraan (Admin).
     */
    public function destroy(string $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);

        // Hapus foto
        foreach ($vehicle->images ?? [] as $path) {
            Storage::delete('public/' . $path);
        }

        $vehicle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kendaraan berhasil dihapus.',
        ]);
    }

    // ── Helper ───────────────────────────────────────────────────────────

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
            'images'        => collect($v->images ?? [])->map(
                fn($path) => url('storage/' . $path)
            )->values()->all(),
            'created_at'    => $v->created_at?->toIso8601String(),
        ];
    }
}
