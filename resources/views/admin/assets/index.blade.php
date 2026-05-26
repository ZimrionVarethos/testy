{{-- resources/views/admin/assets/index.blade.php --}}
<x-app-layout>
<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Asset Manager</h2>
</x-slot>

@push('head-scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="{{ asset('css/admin/assets.css') }}">
@endpush

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
        <button class="btn btn-sm btn-primary" type="button" onclick="window.CloudinaryPicker.open({ uploadFolder: '{{ $subfolder ?: 'admin' }}' })">
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
    <div class="card border-0 mb-3">
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
    <div class="card border-0 mb-3">
        <div class="card-body">
            <h6 class="fw-semibold mb-2"><i class="bi bi-folder2 me-2 text-primary"></i>Folder</h6>
            <ul class="list-unstyled mb-0">
                <li>
                    <a href="{{ route('admin.assets.index') }}"
                       class="d-block px-2 py-1 text-decoration-none {{ !$subfolder ? 'fw-semibold text-primary bg-primary bg-opacity-10' : 'text-body' }}">
                        <i class="bi bi-grid me-1"></i> Semua
                    </a>
                </li>
                @foreach($folders as $f)
                <li>
                    <a href="{{ route('admin.assets.index', ['folder' => $f]) }}"
                       class="d-block px-2 py-1 text-decoration-none {{ $subfolder === $f ? 'fw-semibold text-primary bg-primary bg-opacity-10' : 'text-body' }}">
                        <i class="bi bi-folder me-1"></i> {{ $f }}
                    </a>
                </li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Bulk Actions --}}
    <div class="card border-0" id="bulk-panel" style="display:none!important">
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

@include('admin.partials.cloudinary-picker')

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
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.AssetData = { usageRefreshUrl: '{{ route("admin.assets.usage-refresh") }}' };</script>
<script src="{{ asset('js/admin/assets.js') }}"></script>
@endpush
</x-app-layout>
