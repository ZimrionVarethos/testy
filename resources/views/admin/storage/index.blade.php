<x-app-layout>
    <x-slot name="header">Storage Monitor</x-slot>

    @push('head-scripts')
    <link rel="stylesheet" href="{{ asset('css/admin/storage-index.css') }}">
    @endpush

    @php
    // Warna bar berdasarkan persentase
    $barColor = $usedPercent < 60 ? '#16a34a' : ($usedPercent < 85 ? '#d97706' : '#dc2626');

    // Total storage seluruh koleksi untuk proporsi inline bar
    $totalCollStorage = array_sum(array_column($collections, 'storage'))
                      + array_sum(array_column($collections, 'index_size'));
    $totalCollStorage = max($totalCollStorage, 1);

    $protected = ['users', 'personal_access_tokens'];
    @endphp

    <div class="st-root">
    <div class="st-wrap">

        {{-- Header --}}
        <div class="st-header">
            <div class="st-header-left">
                <h1>storage_monitor</h1>
                <p>MongoDB Atlas Free Tier · 512 MB limit</p>
            </div>
            <a href="{{ route('admin.storage.index') }}" class="st-refresh">↺ Refresh</a>
        </div>

        {{-- Flash --}}
        @if(session('success'))
        <div class="st-flash success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="st-flash error">{{ session('error') }}</div>
        @endif

        {{-- Storage Overview --}}
        <div class="st-overview">
            <div class="st-ov-top">
                <div>
                    <div class="st-ov-title">Total Pemakaian</div>
                    <div class="st-ov-used">{{ $fmt($totalUsed) }}</div>
                    <div class="st-ov-limit">dari 512.00 MB</div>
                </div>
                <div style="text-align:right">
                    <div class="st-ov-pct" style="color:{{ $barColor }}">{{ $usedPercent }}% terpakai</div>
                    <div style="font-size:12px;color:var(--muted);font-family:var(--mono);margin-top:4px">
                        {{ $fmt(512 * 1024 * 1024 - $totalUsed) }} tersisa
                    </div>
                </div>
            </div>

            <div class="st-bar-track">
                <div class="st-bar-fill" style="width:{{ min($usedPercent, 100) }}%;background:{{ $barColor }}"></div>
            </div>

            <div class="st-ov-stats">
                <div class="st-ov-stat">
                    <div class="st-ov-stat-lbl">Data Size</div>
                    <div class="st-ov-stat-val" style="color:#111827">{{ $fmt($dataSize) }}</div>
                </div>
                <div class="st-ov-stat">
                    <div class="st-ov-stat-lbl">Storage Size</div>
                    <div class="st-ov-stat-val" style="color:#16a34a">{{ $fmt($storageSize) }}</div>
                </div>
                <div class="st-ov-stat">
                    <div class="st-ov-stat-lbl">Index Size</div>
                    <div class="st-ov-stat-val" style="color:#d97706">{{ $fmt($indexSize) }}</div>
                </div>
                <div class="st-ov-stat">
                    <div class="st-ov-stat-lbl">Koleksi</div>
                    <div class="st-ov-stat-val">{{ count($collections) }}</div>
                </div>
            </div>
        </div>

        {{-- Collections --}}
        <div class="st-section-title">koleksi · {{ count($collections) }} total</div>
        <div class="st-table-wrap">
            <table class="st-table">
                <thead>
                    <tr>
                        <th>Nama Koleksi</th>
                        <th>Dokumen</th>
                        <th>Ukuran</th>
                        <th>Avg Doc</th>
                        <th>Proporsi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($collections as $c)
                    @php
                        $collTotal  = $c['storage'] + $c['index_size'];
                        $proportion = round(($collTotal / $totalCollStorage) * 100, 1);
                        $isProtected = in_array($c['name'], $protected);
                        $fillColor  = $proportion > 40 ? '#dc2626' : ($proportion > 20 ? '#d97706' : '#111827');
                    @endphp
                    <tr>
                        <td class="td-name">{{ $c['name'] }}</td>
                        <td class="td-num">{{ number_format($c['count']) }}</td>
                        <td class="td-num">{{ $c['size_fmt'] }}</td>
                        <td class="td-muted">{{ $fmt($c['avg_obj']) }}</td>
                        <td>
                            <div class="st-inline-bar">
                                <div class="st-inline-track">
                                    <div class="st-inline-fill" style="width:{{ $proportion }}%;background:{{ $fillColor }}"></div>
                                </div>
                                <span class="td-muted">{{ $proportion }}%</span>
                            </div>
                        </td>
                        <td>
                            <div class="td-actions">
                                <a href="{{ route('admin.storage.show', $c['name']) }}" class="btn-view">Lihat</a>
                                @if(!$isProtected)
                                <button class="btn-del"
                                        onclick="confirmDrop('{{ $c['name'] }}', {{ $c['count'] }})">
                                    Hapus
                                </button>
                                @else
                                <span class="badge-protected">protected</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="padding:32px;text-align:center;color:var(--muted);font-family:var(--mono);">Tidak ada koleksi ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
    </div>

    {{-- Confirm Modal --}}
    <div class="st-modal-overlay" id="dropModal" style="display:none" onclick="closeModal(event)">
        <div class="st-modal">
            <h3>Hapus Koleksi</h3>
            <p>Tindakan ini akan menghapus <strong id="modalCount" style="color:var(--text)"></strong> dokumen secara permanen dan tidak bisa dibatalkan.</p>
            <code class="st-modal-code" id="modalCode"></code>
            <div class="st-modal-actions">
                <button class="btn-cancel" onclick="document.getElementById('dropModal').style.display='none'">Batal</button>
                <form id="dropForm" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-confirm-del">Ya, Hapus Semua</button>
                </form>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/admin/storage-index.js') }}"></script>
</x-app-layout>
