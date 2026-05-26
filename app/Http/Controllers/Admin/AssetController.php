<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\AssetController as ApiAsset;
use App\Http\Controllers\Controller;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AssetController extends Controller
{
    public function __construct(
        protected CloudinaryService $cloudinary,
        protected ApiAsset $assetApi,
    ) {}

    // ── Halaman utama Asset Manager ────────────────────────────────────────

    public function index(Request $request)
    {
        // Semua query MongoDB ada di Api/AssetController::indexForWeb()
        ['assets' => $assets, 'folders' => $folders] = $this->assetApi->indexForWeb($request);

        $usage = $this->cloudinary->usage();

        $subfolder = $request->query('folder', '');
        $search    = $request->query('search', '');

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
        $created   = [];
        $errors    = [];

        foreach ($request->file('files', []) as $file) {
            try {
                // Upload ke Cloudinary (infrastruktur — Web layer)
                $result = $this->cloudinary->upload($file, $subfolder);

                // Buat record di MongoDB lewat API
                $asset = $this->assetApi->createForWeb([
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

                $uploaded[] = $result['public_id'];
                $created[] = [
                    'id'        => (string) $asset->id,
                    'url'       => $result['url'],
                    'thumb_url' => CloudinaryService::transformUrl($result['url'], 'c_fill,w_360,h_260,g_auto,f_auto,q_auto'),
                    'name'      => $file->getClientOriginalName(),
                    'size'      => isset($result['bytes']) ? round($result['bytes'] / 1024) . ' KB' : '',
                    'subfolder' => $subfolder,
                    'width'     => $result['width'],
                    'height'    => $result['height'],
                ];
                Log::info("[Assets] Uploaded: {$result['public_id']}");
            } catch (\Throwable $e) {
                Log::error('[Assets] Upload error: ' . $e->getMessage());
                $errors[] = $file->getClientOriginalName() . ': ' . $e->getMessage();
            }
        }

        if ($errors) {
            if ($request->expectsJson()) {
                return response()->json(['message' => count($uploaded) . ' file berhasil, ' . count($errors) . ' gagal.', 'assets' => $created ?? [], 'errors' => $errors], 422);
            }
            return back()->withErrors($errors)->with('warning', count($uploaded) . ' file berhasil, ' . count($errors) . ' gagal.');
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => count($uploaded) . ' file berhasil diupload ke Cloudinary.', 'assets' => $created ?? []]);
        }

        return back()->with('success', count($uploaded) . ' file berhasil diupload ke Cloudinary.');
    }

    // ── Delete ─────────────────────────────────────────────────────────────

    public function destroy(string $id)
    {
        // Cek asset dan status penggunaan dari API — TIDAK query MongoDB langsung
        $assetData = $this->assetApi->findForDestroyForWeb($id);

        if (!$assetData) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'Asset tidak ditemukan.'], 404);
            }
            return back()->withErrors(['Asset tidak ditemukan.']);
        }

        if ($assetData['is_in_use']) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'Gambar ini masih digunakan. Hapus dari entitas terlebih dahulu.'], 422);
            }
            return back()->withErrors(['Gambar ini masih digunakan. Hapus dari entitas terlebih dahulu.']);
        }

        // Hapus dari Cloudinary (infrastruktur — Web layer)
        $deleted = $this->cloudinary->delete($assetData['public_id']);

        if (!$deleted) {
            Log::warning("[Assets] Cloudinary delete failed for: {$assetData['public_id']}");
        }

        // Hapus record dari MongoDB lewat API
        $this->assetApi->deleteForWeb($id);

        if (request()->expectsJson()) {
            return response()->json(['message' => "Gambar '{$assetData['original_name']}' dihapus."]);
        }

        return back()->with('success', "Gambar '{$assetData['original_name']}' dihapus.");
    }

    /**
     * Hapus banyak asset sekaligus (bulk delete dari checkbox).
     */
    public function destroyBulk(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('info', 'Tidak ada yang dipilih.');

        // Hapus record MongoDB lewat API, dapat daftar public_id yang perlu dihapus dari Cloudinary
        $result    = $this->assetApi->deleteBulkForWeb($ids);
        $publicIds = $result['deleted_public_ids'];
        $skipped   = $result['skipped'];

        // Hapus dari Cloudinary (infrastruktur — Web layer)
        if ($publicIds) {
            $this->cloudinary->deleteMany($publicIds);
        }

        $msg = count($publicIds) . ' gambar dihapus.';
        if ($skipped) $msg .= " {$skipped} dilewati (masih digunakan).";

        return back()->with('success', $msg);
    }

    // ── Asset Picker (AJAX / JSON) ─────────────────────────────────────────
    // Endpoint ini dipanggil via AJAX dari halaman Vehicle/Landing edit.
    // Tetap di Admin namespace agar URL tidak berubah dan frontend tidak perlu diubah.

    public function pickerData(Request $request)
    {
        // Semua query MongoDB ada di Api/AssetController::pickerDataForWeb()
        $data = $this->assetApi->pickerDataForWeb($request);

        return response()->json($data);
    }

    // ── Refresh usage dari Cloudinary (AJAX) ───────────────────────────────

    public function usageRefresh()
    {
        // Cloudinary usage API call — tidak ada MongoDB di sini
        $usage = $this->cloudinary->usage();
        return response()->json($usage);
    }
}
