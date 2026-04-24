<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StorageController extends Controller
{
    // ── Developer-only gate ────────────────────────────────────────
    private function authorizeDeveloper(): void
    {
        if (
            !auth()->check() ||
            auth()->user()->role !== 'admin' ||
            auth()->user()->email !== 'mochfarelaz@gmail.com'
        ) {
            abort(403, 'Akses ditolak.');
        }
    }

    // ── Helper: ambil raw MongoDB database object ──────────────────
    private function mongodb()
    {
        return DB::connection('mongodb')->getMongoDB();
    }

    // ── Helper: byte → human readable ─────────────────────────────
    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B','KB','MB','GB'];
        $i = (int) floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    // ── Index: ringkasan storage + daftar koleksi ──────────────────
    public function index()
    {
        $this->authorizeDeveloper();
        $db = $this->mongodb();

        // dbStats (skala byte)
        $raw = iterator_to_array($db->command(['dbStats' => 1, 'scale' => 1]))[0];

        $totalLimit   = 512 * 1024 * 1024;  // 512 MB free tier Atlas
        $dataSize     = (int)($raw['dataSize']    ?? 0);
        $storageSize  = (int)($raw['storageSize'] ?? 0);
        $indexSize    = (int)($raw['indexSize']   ?? 0);
        $totalUsed    = $storageSize + $indexSize;
        $usedPercent  = round(($totalUsed / $totalLimit) * 100, 1);

        // Per-koleksi
        $collectionNames = iterator_to_array($db->listCollectionNames());
        $collections = [];

        foreach ($collectionNames as $name) {
            $cs = iterator_to_array($db->command(['collStats' => $name, 'scale' => 1]))[0];

            $collections[] = [
                'name'       => $name,
                'count'      => (int)($cs['count']       ?? 0),
                'size'       => (int)($cs['size']        ?? 0),
                'storage'    => (int)($cs['storageSize'] ?? 0),
                'index_size' => (int)($cs['totalIndexSize'] ?? 0),
                'avg_obj'    => (int)($cs['avgObjSize']  ?? 0),
                'size_fmt'   => $this->formatBytes((int)($cs['storageSize'] ?? 0) + (int)($cs['totalIndexSize'] ?? 0)),
            ];
        }

        // Urutkan terbesar dulu
        usort($collections, fn($a,$b) => ($b['storage'] + $b['index_size']) - ($a['storage'] + $a['index_size']));

        return view('admin.storage.index', [
            'totalLimit'  => $totalLimit,
            'dataSize'    => $dataSize,
            'storageSize' => $storageSize,
            'indexSize'   => $indexSize,
            'totalUsed'   => $totalUsed,
            'usedPercent' => $usedPercent,
            'collections' => $collections,
            'fmt'         => fn($b) => $this->formatBytes($b),
        ]);
    }

    // ── Show: isi dokumen satu koleksi ─────────────────────────────
    public function show(Request $request, string $collection)
    {
        $this->authorizeDeveloper();
        $db       = $this->mongodb();
        $coll     = $db->selectCollection($collection);
        $page     = max(1, (int)$request->get('page', 1));
        $perPage  = 20;
        $skip     = ($page - 1) * $perPage;

        $total    = $coll->countDocuments();
        $cursor   = $coll->find([], ['limit' => $perPage, 'skip' => $skip, 'sort' => ['_id' => -1]]);
        $docs     = iterator_to_array($cursor);

        // Konversi BSON ke array biasa
        $docs = array_map(fn($d) => json_decode(json_encode($d), true), $docs);

        $totalPages = (int) ceil($total / $perPage);

        return view('admin.storage.show', [
            'collection' => $collection,
            'docs'       => $docs,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => $totalPages,
        ]);
    }

    // ── Hapus SATU dokumen ─────────────────────────────────────────
    public function destroyDocument(Request $request, string $collection, string $id)
    {
        $this->authorizeDeveloper();
        $db   = $this->mongodb();
        $coll = $db->selectCollection($collection);
        $coll->deleteOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);

        return redirect()
            ->route('admin.storage.show', $collection)
            ->with('success', "Dokumen $id berhasil dihapus.");
    }

    // ── Hapus SEMUA dokumen dalam koleksi (drop collection) ────────
    public function destroyCollection(string $collection)
    {
        $this->authorizeDeveloper();
        // Daftar koleksi yang TIDAK BOLEH dihapus
        $protected = ['users', 'personal_access_tokens'];

        if (in_array($collection, $protected)) {
            return redirect()
                ->route('admin.storage.index')
                ->with('error', "Koleksi '$collection' tidak dapat dihapus.");
        }

        $db = $this->mongodb();
        $db->selectCollection($collection)->drop();

        return redirect()
            ->route('admin.storage.index')
            ->with('success', "Koleksi '$collection' berhasil dihapus.");
    }
}