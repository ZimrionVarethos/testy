<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function __construct(private CloudinaryService $cloudinary) {}

    public function index(Request $request): JsonResponse
    {
        ['assets' => $assets, 'folders' => $folders] = $this->indexForWeb($request);

        return response()->json([
            'success' => true,
            'data'    => $assets->map(fn($a) => $this->assetResource($a)),
            'folders' => $folders,
            'meta'    => [
                'current_page' => $assets->currentPage(),
                'last_page'    => $assets->lastPage(),
                'total'        => $assets->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'files.*'   => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'subfolder' => ['nullable', 'string', 'regex:/^[a-z0-9_\-\/]+$/i', 'max:50'],
        ]);

        $subfolder = trim($request->input('subfolder', 'admin'), '/');
        $created = [];
        $errors = [];

        foreach ($request->file('files', []) as $file) {
            try {
                $result = $this->cloudinary->upload($file, $subfolder);
                $asset = $this->createForWeb([
                    'public_id'     => $result['public_id'],
                    'url'           => $result['url'],
                    'folder'        => $result['folder'],
                    'subfolder'     => $subfolder,
                    'original_name' => $file->getClientOriginalName(),
                    'format'        => $result['format'],
                    'bytes'         => $result['bytes'],
                    'width'         => $result['width'],
                    'height'        => $result['height'],
                    'used_by'       => [],
                    'tags'          => [],
                    'uploaded_by'   => (string) $request->user()->_id,
                ]);

                $created[] = $this->assetResource($asset);
            } catch (\Throwable $e) {
                $errors[] = $file->getClientOriginalName() . ': ' . $e->getMessage();
            }
        }

        return response()->json([
            'success' => empty($errors),
            'message' => count($created) . ' file berhasil diupload.',
            'data'    => $created,
            'errors'  => $errors,
        ], empty($errors) ? 201 : 422);
    }

    public function destroy(string $id): JsonResponse
    {
        $assetData = $this->findForDestroyForWeb($id);

        if (!$assetData) {
            return response()->json(['success' => false, 'message' => 'Asset tidak ditemukan.'], 404);
        }

        if ($assetData['is_in_use']) {
            return response()->json([
                'success' => false,
                'message' => 'Gambar ini masih digunakan. Hapus dari entitas terlebih dahulu.',
            ], 422);
        }

        $this->cloudinary->delete($assetData['public_id']);
        $this->deleteForWeb($id);

        return response()->json([
            'success' => true,
            'message' => "Gambar '{$assetData['original_name']}' dihapus.",
        ]);
    }

    public function destroyBulk(Request $request): JsonResponse
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['string']]);

        $result = $this->deleteBulkForWeb($request->ids);
        if ($result['deleted_public_ids']) {
            $this->cloudinary->deleteMany($result['deleted_public_ids']);
        }

        return response()->json([
            'success' => true,
            'message' => count($result['deleted_public_ids']) . ' gambar dihapus.',
            'skipped' => $result['skipped'],
        ]);
    }

    public function picker(Request $request): JsonResponse
    {
        return response()->json(array_merge(['success' => true], $this->pickerDataForWeb($request)));
    }

    public function usage(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->cloudinary->usage()]);
    }

    // ── ForWeb Methods (dipakai Admin AssetController langsung, bukan HTTP) ──

    /**
     * Data daftar aset untuk halaman admin + folder list.
     */
    public function indexForWeb(Request $request): array
    {
        $subfolder = $request->query('folder', '');
        $search    = $request->query('search', '');

        $query = Asset::orderBy('created_at', 'desc');
        if ($subfolder) $query->inFolder($subfolder);
        if ($search)    $query->search($search);

        $assets  = $query->paginate(24)->withQueryString();
        $folders = $this->getFolderListForWeb();

        return compact('assets', 'folders');
    }

    /**
     * Buat record Asset baru setelah upload ke Cloudinary.
     */
    public function createForWeb(array $data): Asset
    {
        return Asset::create($data);
    }

    /**
     * Cari asset untuk proses hapus. Return null jika tidak ditemukan.
     * Returns: ['id', 'public_id', 'original_name', 'is_in_use']
     */
    public function findForDestroyForWeb(string $id): ?array
    {
        $asset = Asset::find($id);
        if (!$asset) return null;

        return [
            'id'            => (string) $asset->_id,
            'public_id'     => $asset->public_id,
            'original_name' => $asset->original_name,
            'is_in_use'     => $asset->isInUse(),
        ];
    }

    /**
     * Hapus satu record Asset dari MongoDB (Cloudinary sudah dihapus di Web controller).
     */
    public function deleteForWeb(string $id): void
    {
        $asset = Asset::find($id);
        if ($asset) $asset->delete();
    }

    /**
     * Bulk delete: hapus record-record Asset yang valid (tidak sedang dipakai).
     * Return: ['deleted_public_ids' => [...], 'skipped' => int]
     */
    public function deleteBulkForWeb(array $ids): array
    {
        $assets     = Asset::whereIn('_id', $ids)->get();
        $publicIds  = [];
        $skipped    = 0;

        foreach ($assets as $asset) {
            if ($asset->isInUse()) {
                $skipped++;
                continue;
            }
            $publicIds[] = $asset->public_id;
            $asset->delete();
        }

        return ['deleted_public_ids' => $publicIds, 'skipped' => $skipped];
    }

    /**
     * Data untuk Asset Picker (paginated JSON — dipakai pickerData di Admin controller).
     */
    public function pickerDataForWeb(Request $request): array
    {
        $subfolder = $request->query('folder', '');
        $search    = $request->query('search', '');
        $page      = max(1, (int) $request->query('page', 1));
        $perPage   = 24;

        $query = Asset::orderBy('created_at', 'desc');
        if ($subfolder) $query->inFolder($subfolder);
        if ($search)    $query->search($search);

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data'         => $paginated->map(fn($a) => [
                'id'         => $a->id,
                'url'        => $a->url,
                'thumb_url'  => CloudinaryService::transformUrl($a->url, 'c_fill,w_360,h_260,g_auto,f_auto,q_auto'),
                'public_id'  => $a->public_id,
                'name'       => $a->original_name,
                'size'       => $a->human_size,
                'subfolder'  => $a->subfolder,
                'width'      => $a->width,
                'height'     => $a->height,
                'created_at' => $a->created_at?->format('d M Y'),
            ]),
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
            'total'        => $paginated->total(),
            'folders'      => $this->getFolderListForWeb(),
        ];
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    public function getFolderListForWeb(): array
    {
        return Asset::select('subfolder')
            ->groupBy('subfolder')
            ->pluck('subfolder')
            ->filter()
            ->sort()
            ->values()
            ->all();
    }

    private function assetResource(Asset $a): array
    {
        return [
            'id'            => (string) $a->_id,
            'public_id'     => $a->public_id,
            'url'           => $a->url,
            'thumb_url'     => CloudinaryService::transformUrl($a->url, 'c_fill,w_360,h_260,g_auto,f_auto,q_auto'),
            'folder'        => $a->folder,
            'subfolder'     => $a->subfolder,
            'original_name' => $a->original_name,
            'format'        => $a->format,
            'bytes'         => $a->bytes,
            'human_size'    => $a->human_size,
            'width'         => $a->width,
            'height'        => $a->height,
            'used_by'       => $a->used_by ?? [],
            'is_in_use'     => $a->isInUse(),
            'created_at'    => $a->created_at?->toIso8601String(),
        ];
    }
}
