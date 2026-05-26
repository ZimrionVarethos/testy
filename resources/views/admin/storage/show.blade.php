<x-app-layout>
    <x-slot name="header">Storage · {{ $collection }}</x-slot>

    @push('head-scripts')
    <link rel="stylesheet" href="{{ asset('css/admin/storage-show.css') }}">
    @endpush

    @php
    // Helper: buat preview singkat dari doc array
    function docPreview(array $doc): string {
        $skip = ['_id','updated_at','created_at','password'];
        $parts = [];
        foreach ($doc as $k => $v) {
            if (in_array($k, $skip)) continue;
            if (is_array($v)) continue;
            $parts[] = "$k: " . (is_string($v) ? "\"$v\"" : json_encode($v));
            if (count($parts) >= 3) break;
        }
        return implode('  ·  ', $parts) ?: '(kosong)';
    }
    @endphp

    <div class="st-root">
    <div class="st-wrap">

        {{-- Header --}}
        <div class="st-header">
            <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                <a href="{{ route('admin.storage.index') }}" class="st-back">Storage</a>
                <div>
                    <div class="st-title">{{ $collection }}</div>
                    <div class="st-subtitle">{{ number_format($total) }} dokumen total</div>
                </div>
            </div>
        </div>

        {{-- Flash --}}
        @if(session('success'))
        <div class="st-flash success">{{ session('success') }}</div>
        @endif

        {{-- Info bar --}}
        <div class="st-infobar">
            <div class="st-infobar-item">Halaman <strong>{{ $page }}</strong> dari <strong>{{ $totalPages }}</strong></div>
            <div class="st-infobar-item">Menampilkan <strong>{{ count($docs) }}</strong> dari <strong>{{ number_format($total) }}</strong> dokumen</div>
            <div class="st-infobar-item">Urutan: <strong>_id desc</strong></div>
        </div>

        {{-- Document list --}}
        <div class="st-doc-list">
            @forelse($docs as $doc)
            @php $docId = $doc['_id']['$oid'] ?? ($doc['_id'] ?? '?'); @endphp
            <div class="st-doc-card" id="card-{{ $loop->index }}">
                <div class="st-doc-header" onclick="toggleDoc({{ $loop->index }})">
                    <span class="st-doc-id">{{ $docId }}</span>
                    <span class="st-doc-preview">{{ docPreview($doc) }}</span>
                    <div class="st-doc-actions" onclick="event.stopPropagation()">
                        <button class="st-toggle" onclick="toggleDoc({{ $loop->index }})">JSON</button>
                        <button class="btn-del-doc" onclick="confirmDelDoc('{{ $docId }}')">Hapus</button>
                    </div>
                </div>
                <div class="st-doc-body" id="body-{{ $loop->index }}">
                    <div class="st-json" id="json-{{ $loop->index }}"></div>
                </div>
            </div>
            @php
                // Store docs as JSON for JS rendering
                echo '<script>window.__docs = window.__docs || {}; window.__docs["' . $loop->index . '"] = ' . json_encode($doc) . ';</script>';
            @endphp
            @empty
            <div style="padding:48px;text-align:center;color:var(--muted);font-family:var(--mono);background:var(--surface);border:1px solid var(--border);border-radius:0;">
                Koleksi ini kosong.
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($totalPages > 1)
        <div class="st-pagination">
            <span class="st-page-info">{{ number_format($total) }} dokumen · halaman {{ $page }}/{{ $totalPages }}</span>
            <div class="st-page-btns">
                @if($page > 1)
                    <a href="{{ route('admin.storage.show', [$collection, 'page' => $page - 1]) }}" class="st-page-btn">Prev</a>
                @else
                    <span class="st-page-btn disabled">Prev</span>
                @endif
                <span class="st-page-current">{{ $page }}</span>
                @if($page < $totalPages)
                    <a href="{{ route('admin.storage.show', [$collection, 'page' => $page + 1]) }}" class="st-page-btn">Next</a>
                @else
                    <span class="st-page-btn disabled">Next</span>
                @endif
            </div>
        </div>
        @endif

    </div>
    </div>

    {{-- Delete single doc modal --}}
    <div class="st-modal-overlay" id="delDocModal" style="display:none" onclick="closeModal(event)">
        <div class="st-modal">
            <h3>Hapus Dokumen</h3>
            <p>Dokumen ini akan dihapus permanen.</p>
            <code class="st-modal-code" id="delDocCode"></code>
            <div class="st-modal-actions">
                <button class="btn-cancel" onclick="document.getElementById('delDocModal').style.display='none'">Batal</button>
                <form id="delDocForm" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-confirm-del">Hapus</button>
                </form>
            </div>
        </div>
    </div>

    <script>window.StorageData = { collection: @json($collection) };</script>
    <script src="{{ asset('js/admin/storage-show.js') }}"></script>
</x-app-layout>
