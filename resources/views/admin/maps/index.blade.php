{{-- resources/views/admin/maps/index.blade.php --}}
<x-app-layout>
<x-slot name="header">Peta Armada</x-slot>

@push('head-scripts')
<link rel="stylesheet" href="{{ asset('css/admin/maps.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"/>
@endpush

@php
$pinColors    = ['ongoing'=>'#16a34a','available'=>'#111827','maintenance'=>'#d97706','rented'=>'#111827'];
$statusLabels = ['ongoing'=>'Berjalan','available'=>'Tersedia','maintenance'=>'Maintenance','rented'=>'Disewa'];
$listVehicles     = collect($vehicles ?? [])->map(fn($v)=>is_array($v)?$v:(array)$v)->values()->all();
$mappableVehicles = collect($listVehicles)->filter(fn($v)=>!empty($v['lat'])&&!empty($v['lon']))->values()->all();
@endphp

<div class="mp">

    <div>
        <h2 style="font-family:'Epilogue',sans-serif;font-size:22px;font-weight:800;color:var(--text-1);letter-spacing:-.5px">Peta Armada</h2>
        <p style="font-size:13px;color:var(--text-2);margin-top:3px">Lokasi kendaraan real-time · hanya tampil saat bertugas</p>
    </div>

    <div class="mp-stats">
        <div class="mp-stat">
            <span class="mp-stat-dot" style="background:var(--text-2)"></span>
            <div><div class="mp-stat-val">{{ $stats['total'] }}</div><div class="mp-stat-lbl">Total</div></div>
        </div>
        <div class="mp-stat">
            <span class="mp-stat-dot" style="background:var(--s-green)"></span>
            <div><div class="mp-stat-val">{{ $stats['ongoing'] }}</div><div class="mp-stat-lbl">Disewa</div></div>
        </div>
        <div class="mp-stat">
            <span class="mp-stat-dot" style="background:var(--s-blue)"></span>
            <div><div class="mp-stat-val">{{ $stats['available'] }}</div><div class="mp-stat-lbl">Tersedia</div></div>
        </div>
        <div class="mp-stat">
            <span class="mp-stat-dot" style="background:var(--s-amber)"></span>
            <div><div class="mp-stat-val">{{ $stats['maintenance'] }}</div><div class="mp-stat-lbl">Maintenance</div></div>
        </div>
    </div>

    <div class="mp-grid">

        <div class="mp-map-card">
            <div class="mp-map-hdr">
                <div class="mp-map-hdr-left">
                    <span class="mp-live-dot"></span>
                    <span class="mp-map-title">Live Map</span>
                    @if(count($mappableVehicles) === 0)
                    <span class="mp-no-loc-badge">Tidak ada yang bertugas</span>
                    @endif
                </div>
                <span class="mp-map-hint">Klik pin · scroll zoom</span>
            </div>
            <div class="mp-map-frame">
                <div id="fleet-map"></div>
            </div>
        </div>

        <div class="mp-sidebar">
            <div class="mp-sidebar-head">
                <div class="mp-sidebar-head-title">Semua Kendaraan</div>
                <input type="text" id="vehicleSearch" class="mp-search" placeholder="Cari plat / nama…">
            </div>
            <div class="mp-list" id="vehicleList">
                @forelse($listVehicles as $v)
                @php
                    $hasLoc   = !empty($v['lat']) && !empty($v['lon']);
                    $dotColor = $hasLoc
                        ? ($v['is_stale'] ? '#d97706' : ($pinColors[$v['status']] ?? 'var(--text-3)'))
                        : 'var(--border-md)';
                @endphp
                <div class="mp-item {{ !$hasLoc ? 'no-loc' : '' }}"
                     data-id="{{ $v['id'] }}"
                     @if($hasLoc) onclick="focusVehicle('{{ $v['id'] }}')" @endif>
                    <span class="mp-item-dot" style="background:{{ $dotColor }}"></span>
                    <div style="flex:1;min-width:0">
                        <div class="mp-item-plate">{{ $v['plate'] }}</div>
                        <div class="mp-item-label">
                            @if($hasLoc && $v['has_active_booking'])
                                {{ $v['driver'] }}
                                @if($v['is_stale'])· <span style="color:var(--s-amber)">Lokasi lama</span>@endif
                            @elseif($v['has_active_booking'])
                                <span style="color:var(--s-amber-text)">Menunggu lokasi</span>
                            @else
                                {{ $v['label'] }} · Tidak bertugas
                            @endif
                        </div>
                    </div>
                    <span class="mp-badge mp-b-{{ $v['status'] }}">{{ $statusLabels[$v['status']] ?? $v['status'] }}</span>
                </div>
                @empty
                <div style="padding:32px 16px;text-align:center;font-size:12px;color:var(--text-3)">Belum ada kendaraan.</div>
                @endforelse
            </div>
            <div class="mp-footer">
                {{ count($listVehicles) }} kendaraan · {{ count($mappableVehicles) }} terlacak
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>window.MapData = { vehicles: @json($mappableVehicles) };</script>
<script src="{{ asset('js/admin/maps.js') }}"></script>
@endpush

</x-app-layout>
