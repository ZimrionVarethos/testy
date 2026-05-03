{{-- resources/views/admin/assets/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Asset Manager')

@push('styles')
<style>
.asset-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
.asset-card { position: relative; border-radius: 10px; overflow: hidden; background: var(--bs-secondary-bg); border: 2px solid transparent; cursor: pointer; transition: border-color .15s, box-shadow .15s; }
.asset-card:hover { border-color: var(--bs-primary); box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb),.15); }
.asset-card.selected { border-color: var(--bs-primary); }
.asset-card.selected .asset-check { display: flex; }
.asset-img-wrap { aspect-ratio: 1; overflow: hidden; background: var(--bs-tertiary-bg); }
.asset-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
.asset-meta { padding: 6px 8px; }
.asset-meta .name { font-size: .72rem; color: var(--bs-secondary-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.asset-meta .size { font-size: .68rem; color: var(--bs-tertiary-color); }
.asset-check { display: none; position: absolute; top: 6px; left: 6px; width: 22px; height: 22px; background: var(--bs-primary); border-radius: 50%; align-items: center; justify-content: center; color: #fff; font-size: 12px; }
.asset-del-btn { position: absolute; top: 6px; right: 6px; width: 24px; height: 24px; background: rgba(220,53,69,.85); border: none; border-radius: 50%; color: #fff; font-size: 11px; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity .15s; }
.asset-card:hover .asset-del-btn { opacity: 1; }
.usage-bar-wrap { background: var(--bs-tertiary-bg); border-radius: 8px; overflow: hidden; height: 10px; }
.usage-bar { height: 100%; border-radius: 8px; transition: width .4s; }
.drop-zone { border: 2px dashed var(--bs-border-color); border-radius: 12px; padding: 40px; text-align: center; transition: border-color .2s, background .2s; cursor: pointer; }
.drop-zone.drag-over { border-color: var(--bs-primary); background: rgba(var(--bs-primary-rgb),.05); }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0 fw-semibold">Asset Manager</h4>
        <p class="text-muted small mb-0">Kelola semua gambar yang diupload ke Cloudinary</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-secondary" id="btn-refresh-usage">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh Usage
        </button>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-upload">
            <i class="bi bi-cloud-upload me-1"></i> Upload Gambar
        </button>
    </div>
</div>

{{-- Alerts --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">{{ session('warning') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ $errors->first() }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-4">

{{-- ── SIDEBAR MONITORING ────────────────────────────────────────────────── --}}
<div class="col-lg-3">

    {{-- Storage Usage --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h6 class="fw-semibold mb-3"><i class="bi bi-hdd me-2 text-primary"></i>Storage</h6>

            @php
                $storagePct  = $usage['storage_pct']   ?? 0;
                $bandwidthPct= $usage['bandwidth_pct'] ?? 0;
                $storageColor= $storagePct > 85 ? 'danger' : ($storagePct > 60 ? 'warning' : 'success');
                $bwColor     = $bandwidthPct > 85 ? 'danger' : ($bandwidthPct > 60 ? 'warning' : 'success');
            @endphp

            <div class="mb-3">
                <div class="d-flex justify-content-between small mb-1">
                    <span class="text-muted">Storage</span>
                    <span class="fw-medium" id="stat-storage-pct">{{ $storagePct }}%</span>
                </div>
                <div class="usage-bar-wrap">
                    <div class="usage-bar bg-{{ $storageColor }}" id="bar-storage" style="width: {{ $storagePct }}%"></div>
                </div>
                <div class="text-muted" style="font-size:.7rem;margin-top:3px" id="stat-storage-bytes">
                    {{ number_format(($usage['storage_bytes'] ?? 0) / 1048576, 1) }} MB
                    / {{ number_format(($usage['storage_limit_bytes'] ?? 0) / 1048576, 0) }} MB
                </div>
            </div>

            <div class="mb-2">
                <div class="d-flex justify-content-between small mb-1">
                    <span class="text-muted">Bandwidth</span>
                    <span class="fw-medium" id="stat-bw-pct">{{ $bandwidthPct }}%</span>
                </div>
                <div class="usage-bar-wrap">
                    <div class="usage-bar bg-{{ $bwColor }}" id="bar-bandwidth" style="width: {{ $bandwidthPct }}%"></div>
                </div>
                <div class="text-muted" style="font-size:.7rem;margin-top:3px" id="stat-bw-bytes">
                    {{ number_format(($usage['bandwidth_bytes'] ?? 0) / 1048576, 1) }} MB
                    / {{ number_format(($usage['bandwidth_limit_bytes'] ?? 0) / 1048576, 0) }} MB
                </div>
            </div>

            <hr class="my-2">
            <div class="d-flex justify-content-between small">
                <span class="text-muted">Objects</span>
                <span id="stat-objects">{{ number_format($usage['objects'] ?? 0) }}</span>
            </div>
            <div class="d-flex justify-content-between small mt-1">
                <span class="text-muted">Plan</span>
                <span class="badge bg-secondary">{{ strtoupper($usage['plan'] ?? 'free') }}</span>
            </div>

            @if(isset($usage['error']))
                <div class="alert alert-warning p-2 mt-2 mb-0 small">Gagal ambil usage: {{ $usage['error'] }}</div>
            @endif
        </div>
    </div>

    {{-- Folder Filter --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h6 class="fw-semibold mb-2"><i class="bi bi-folder2 me-2 text-primary"></i>Folder</h6>
            <ul class="list-unstyled mb-0">
                <li>
                    <a href="{{ route('admin.assets.index') }}"
                       class="d-block px-2 py-1 rounded text-decoration-none {{ !$subfolder ? 'fw-semibold text-primary bg-primary bg-opacity-10' : 'text-body' }}">
                        <i class="bi bi-grid me-1"></i> Semua
                    </a>
                </li>
                @foreach($folders as $f)
                <li>
                    <a href="{{ route('admin.assets.index', ['folder' => $f]) }}"
                       class="d-block px-2 py-1 rounded text-decoration-none {{ $subfolder === $f ? 'fw-semibold text-primary bg-primary bg-opacity-10' : 'text-body' }}">
                        <i class="bi bi-folder me-1"></i> {{ $f }}
                    </a>
                </li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Bulk Actions --}}
    <div class="card border-0 shadow-sm" id="bulk-panel" style="display:none!important">
        <div class="card-body">
            <h6 class="fw-semibold mb-2">Aksi Bulk</h6>
            <p class="small text-muted mb-2"><span id="selected-count">0</span> gambar dipilih</p>
            <form method="POST" action="{{ route('admin.assets.destroy-bulk') }}" id="form-bulk-delete"
                  onsubmit="return confirm('Hapus semua yang dipilih?')">
                @csrf @method('DELETE')
                <div id="bulk-ids-inputs"></div>
                <button class="btn btn-danger btn-sm w-100">
                    <i class="bi bi-trash me-1"></i> Hapus Dipilih
                </button>
            </form>
            <button class="btn btn-outline-secondary btn-sm w-100 mt-2" onclick="clearSelection()">
                Batalkan Pilihan
            </button>
        </div>
    </div>

</div>

{{-- ── MAIN CONTENT ──────────────────────────────────────────────────────── --}}
<div class="col-lg-9">

    {{-- Search + Select All --}}
    <form method="GET" action="{{ route('admin.assets.index') }}" class="d-flex gap-2 mb-3">
        @if($subfolder)
            <input type="hidden" name="folder" value="{{ $subfolder }}">
        @endif
        <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm"
               placeholder="Cari nama file...">
        <button class="btn btn-sm btn-outline-secondary">Cari</button>
        @if($search)
            <a href="{{ route('admin.assets.index', $subfolder ? ['folder'=>$subfolder] : []) }}"
               class="btn btn-sm btn-outline-secondary">✕</a>
        @endif
        <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" onclick="toggleSelectAll()">
            Pilih Semua
        </button>
    </form>

    {{-- Asset Grid --}}
    @if($assets->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-images" style="font-size:3rem;opacity:.3"></i>
            <p class="mt-2">Belum ada gambar{{ $subfolder ? " di folder '$subfolder'" : '' }}.</p>
        </div>
    @else
    <div class="asset-grid" id="asset-grid">
        @foreach($assets as $asset)
        <div class="asset-card" data-id="{{ $asset->id }}" onclick="toggleSelect(this)">
            <span class="asset-check"><i class="bi bi-check"></i></span>
            <div class="asset-img-wrap">
                <img src="{{ $asset->url }}" alt="{{ $asset->original_name }}" loading="lazy">
            </div>
            <div class="asset-meta">
                <div class="name" title="{{ $asset->original_name }}">{{ $asset->original_name }}</div>
                <div class="size">{{ $asset->human_size }} · {{ strtoupper($asset->format) }}</div>
            </div>
            {{-- Delete button --}}
            <form method="POST" action="{{ route('admin.assets.destroy', $asset->id) }}"
                  onsubmit="event.stopPropagation(); return confirm('Hapus gambar ini?')"
                  onclick="event.stopPropagation()">
                @csrf @method('DELETE')
                <button class="asset-del-btn" title="Hapus">
                    <i class="bi bi-trash3"></i>
                </button>
            </form>
            @if($asset->isInUse())
            <span class="position-absolute bottom-0 start-0 m-1 badge bg-primary" style="font-size:.6rem">
                Dipakai
            </span>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="mt-4 d-flex justify-content-center">
        {{ $assets->links() }}
    </div>
    @endif

</div>
</div>
</div>

{{-- ── MODAL UPLOAD ─────────────────────────────────────────────────────── --}}
<div class="modal fade" id="modal-upload" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Gambar ke Cloudinary</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.assets.store') }}" enctype="multipart/form-data" id="form-upload">
                @csrf
                <div class="modal-body">
                    {{-- Folder selector --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Folder Tujuan</label>
                        <select class="form-select form-select-sm" name="subfolder">
                            <option value="admin" {{ $subfolder === 'admin' ? 'selected' : '' }}>admin</option>
                            <option value="vehicles" {{ $subfolder === 'vehicles' ? 'selected' : '' }}>vehicles</option>
                            <option value="landing" {{ $subfolder === 'landing' ? 'selected' : '' }}>landing</option>
                            <option value="landing/slides">landing/slides</option>
                            @foreach($folders as $f)
                                @if(!in_array($f, ['admin','vehicles','landing','landing/slides']))
                                    <option value="{{ $f }}" {{ $subfolder === $f ? 'selected' : '' }}>{{ $f }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    {{-- Drop zone --}}
                    <div class="drop-zone" id="drop-zone" onclick="document.getElementById('file-input').click()">
                        <i class="bi bi-cloud-upload" style="font-size:2.5rem;opacity:.4"></i>
                        <p class="mt-2 mb-0 text-muted">Klik atau drag & drop gambar di sini</p>
                        <p class="small text-muted">JPG, PNG, WEBP — maks 5 MB per file</p>
                    </div>
                    <input type="file" name="files[]" id="file-input" multiple accept="image/*"
                           class="d-none" onchange="previewFiles(this)">

                    {{-- Preview --}}
                    <div id="file-preview" class="row row-cols-4 g-2 mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-upload-submit" disabled>
                        <i class="bi bi-cloud-upload me-1"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ── Selection ─────────────────────────────────────────────────────────────
let selected = new Set();

function toggleSelect(card) {
    const id = card.dataset.id;
    if (selected.has(id)) { selected.delete(id); card.classList.remove('selected'); }
    else                   { selected.add(id);    card.classList.add('selected'); }
    updateBulkPanel();
}

function clearSelection() {
    selected.clear();
    document.querySelectorAll('.asset-card.selected').forEach(c => c.classList.remove('selected'));
    updateBulkPanel();
}

function toggleSelectAll() {
    const cards = document.querySelectorAll('.asset-card');
    if (selected.size === cards.length) {
        clearSelection();
    } else {
        cards.forEach(c => { selected.add(c.dataset.id); c.classList.add('selected'); });
        updateBulkPanel();
    }
}

function updateBulkPanel() {
    const panel = document.getElementById('bulk-panel');
    document.getElementById('selected-count').textContent = selected.size;
    panel.style.display = selected.size > 0 ? '' : 'none!important';
    if (selected.size > 0) panel.removeAttribute('style');
    else panel.setAttribute('style','display:none!important');

    const wrap = document.getElementById('bulk-ids-inputs');
    wrap.innerHTML = '';
    selected.forEach(id => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = id;
        wrap.appendChild(inp);
    });
}

// ── Drag & Drop Upload ────────────────────────────────────────────────────
const dz = document.getElementById('drop-zone');
dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('drag-over'); });
dz.addEventListener('dragleave', () => dz.classList.remove('drag-over'));
dz.addEventListener('drop', e => {
    e.preventDefault(); dz.classList.remove('drag-over');
    const fi = document.getElementById('file-input');
    fi.files = e.dataTransfer.files;
    previewFiles(fi);
});

function previewFiles(input) {
    const preview = document.getElementById('file-preview');
    preview.innerHTML = '';
    const btn = document.getElementById('btn-upload-submit');
    if (!input.files.length) { btn.disabled = true; return; }
    btn.disabled = false;

    Array.from(input.files).forEach(f => {
        const reader = new FileReader();
        reader.onload = e => {
            const col = document.createElement('div');
            col.className = 'col';
            col.innerHTML = `<div class="rounded overflow-hidden border" style="aspect-ratio:1">
                <img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover">
            </div>
            <div class="text-muted text-truncate" style="font-size:.68rem">${f.name}</div>`;
            preview.appendChild(col);
        };
        reader.readAsDataURL(f);
    });
}

// ── Refresh Usage ─────────────────────────────────────────────────────────
document.getElementById('btn-refresh-usage').addEventListener('click', async function() {
    this.disabled = true;
    this.querySelector('i').classList.add('spin');
    try {
        const r = await fetch('{{ route("admin.assets.usage-refresh") }}');
        const d = await r.json();
        if (d.error) { alert('Gagal: ' + d.error); return; }

        ['storage', 'bandwidth'].forEach(k => {
            const pct = k === 'storage' ? d.storage_pct : d.bandwidth_pct;
            const bytes = k === 'storage' ? d.storage_bytes : d.bandwidth_bytes;
            const limit = k === 'storage' ? d.storage_limit_bytes : d.bandwidth_limit_bytes;
            document.getElementById(`stat-${k}-pct`).textContent = pct + '%';
            document.getElementById(`bar-${k}`).style.width = pct + '%';
            document.getElementById(`stat-${k}-bytes`).textContent =
                (bytes / 1048576).toFixed(1) + ' MB / ' + (limit / 1048576).toFixed(0) + ' MB';
        });
        document.getElementById('stat-objects').textContent = d.objects?.toLocaleString() ?? '–';
    } finally {
        this.disabled = false;
        this.querySelector('i').classList.remove('spin');
    }
});
</script>
<style>
@keyframes spin { to { transform: rotate(360deg); } }
.spin { display: inline-block; animation: spin .7s linear infinite; }
</style>
@endpush