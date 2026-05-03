<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AssetController extends Controller
{
    public function __construct(protected CloudinaryService $cloudinary) {}

    // ── Page utama Asset Manager ────────────────────────────────────────────

    public function index(Request $request)
    {
        $subfolder = $request->query('folder', '');
        $search    = $request->query('search', '');

        $query = Asset::orderBy('created_at', 'desc');
        if ($subfolder) $query->inFolder($subfolder);
        if ($search)    $query->search($search);

        $assets  = $query->paginate(24)->withQueryString();
        $folders = $this->getFolderList();
        $usage   = $this->cloudinary->usage();

        return view('admin.assets.index', compact('assets', 'folders', 'usage', 'subfolder', 'search'));
    }

    // ── Upload ─────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'files.*'   => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'subfolder' => 'nullable|string|regex:/^[a-z0-9_\-\/]+$/i|max:50',
        ]);

        $subfolder = trim($request->input('subfolder', 'admin'), '/');
        $uploaded  = [];
        $errors    = [];

        foreach ($request->file('files', []) as $file) {
            try {
                $result = $this->cloudinary->upload($file, $subfolder);

                $asset = Asset::create([
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
                ]);

                $uploaded[] = $asset;
                Log::info("[Assets] Uploaded: {$result['public_id']}");
            } catch (\Throwable $e) {
                Log::error('[Assets] Upload error: ' . $e->getMessage());
                $errors[] = $file->getClientOriginalName() . ': ' . $e->getMessage();
            }
        }

        if ($errors) {
            return back()->withErrors($errors)->with('warning', count($uploaded) . ' file berhasil, ' . count($errors) . ' gagal.');
        }

        return back()->with('success', count($uploaded) . ' file berhasil diupload ke Cloudinary.');
    }

    // ── Delete ─────────────────────────────────────────────────────────────

    public function destroy(string $id)
    {
        $asset = Asset::findOrFail($id);

        if ($asset->isInUse()) {
            return back()->withErrors(['Gambar ini masih digunakan. Hapus dari entitas terlebih dahulu.']);
        }

        $deleted = $this->cloudinary->delete($asset->public_id);

        if (!$deleted) {
            // Tetap hapus record meski Cloudinary error (mungkin sudah tidak ada)
            Log::warning("[Assets] Cloudinary delete failed for: {$asset->public_id}");
        }

        $asset->delete();

        return back()->with('success', "Gambar '{$asset->original_name}' dihapus.");
    }

    /**
     * Hapus banyak asset sekaligus (bulk delete dari checkbox).
     */
    public function destroyBulk(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('info', 'Tidak ada yang dipilih.');

        $assets    = Asset::whereIn('_id', $ids)->get();
        $publicIds = [];
        $skipped   = 0;

        foreach ($assets as $asset) {
            if ($asset->isInUse()) {
                $skipped++;
                continue;
            }
            $publicIds[] = $asset->public_id;
            $asset->delete();
        }

        if ($publicIds) {
            $this->cloudinary->deleteMany($publicIds);
        }

        $msg = count($publicIds) . ' gambar dihapus.';
        if ($skipped) $msg .= " {$skipped} dilewati (masih digunakan).";

        return back()->with('success', $msg);
    }

    // ── Asset Picker (AJAX / JSON) ─────────────────────────────────────────

    /**
     * Dipakai oleh popup Asset Picker di halaman Vehicle / Landing.
     * Mengembalikan JSON daftar aset untuk dirender di frontend.
     */
    public function pickerData(Request $request)
    {
        $subfolder = $request->query('folder', '');
        $search    = $request->query('search', '');
        $page      = max(1, (int) $request->query('page', 1));
        $perPage   = 24;

        $query = Asset::orderBy('created_at', 'desc');
        if ($subfolder) $query->inFolder($subfolder);
        if ($search)    $query->search($search);

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'       => $paginated->map(fn($a) => [
                'id'         => $a->id,
                'url'        => $a->url,
                'name'       => $a->original_name,
                'size'       => $a->human_size,
                'subfolder'  => $a->subfolder,
                'created_at' => $a->created_at?->format('d M Y'),
            ]),
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
            'total'        => $paginated->total(),
            'folders'      => $this->getFolderList(),
        ]);
    }

    // ── Refresh usage dari Cloudinary (AJAX) ───────────────────────────────

    public function usageRefresh()
    {
        $usage = $this->cloudinary->usage();
        return response()->json($usage);
    }

    // ── Helper ─────────────────────────────────────────────────────────────

    protected function getFolderList(): array
    {
        return Asset::select('subfolder')
            ->groupBy('subfolder')
            ->pluck('subfolder')
            ->filter()
            ->sort()
            ->values()
            ->all();
    }
}