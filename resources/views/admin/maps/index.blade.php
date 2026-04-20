{{-- resources/views/admin/maps/index.blade.php --}}
<x-app-layout>
<x-slot name="header">Peta Armada</x-slot>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<style>
/* ── Inherits tokens dari app.blade.php ── */
.mp { max-width:1200px; margin:0 auto; padding:24px 24px 48px; display:flex; flex-direction:column; gap:20px; }

/* ── Stat strip ── */
.mp-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
.mp-stat  {
    background:var(--white); border:1px solid var(--border);
    border-radius:14px; padding:16px 18px;
    display:flex; align-items:center; gap:12px;
}
.mp-stat-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.mp-stat-val {
    font-family:'DM Mono',monospace;
    font-size:24px; font-weight:500;
    color:var(--text-1); line-height:1;
}
.mp-stat-lbl {
    font-size:10px; font-weight:700;
    text-transform:uppercase; letter-spacing:.07em;
    color:var(--text-3); margin-top:3px;
}

/* ── Main grid ── */
.mp-grid {
    display:grid;
    grid-template-columns:1fr 290px;
    gap:16px;
    align-items:start;
}

/* ── Map card ── */
.mp-map-card {
    background:var(--white);
    border:1px solid var(--border);
    border-radius:14px;
    overflow:hidden;
}
.mp-map-hdr {
    padding:13px 18px;
    border-bottom:1px solid var(--border);
    display:flex; align-items:center; justify-content:space-between;
}
.mp-map-hdr-left { display:flex; align-items:center; gap:8px; }
.mp-live-dot {
    width:7px; height:7px; border-radius:50%;
    background:#16a34a;
    animation:mp-pulse 2s infinite;
}
@keyframes mp-pulse { 0%,100%{opacity:1} 50%{opacity:.35} }
.mp-map-title {
    font-family:'Epilogue',sans-serif;
    font-size:13px; font-weight:700; color:var(--text-1);
}
.mp-map-hint { font-size:11px; color:var(--text-3); }
.mp-no-loc-badge {
    font-size:10px; font-weight:700;
    background:var(--s-amber-bg); border:1px solid rgba(217,119,6,.2);
    color:var(--s-amber-text); padding:2px 9px; border-radius:99px;
}

/* ── Map frame ── */
.mp-map-frame { padding:12px; }
#fleet-map {
    height:520px; width:100%;
    border-radius:10px;
    border:1px solid var(--border);
    z-index:0; display:block;
}

/* ── Sidebar ── */
.mp-sidebar {
    background:var(--white);
    border:1px solid var(--border);
    border-radius:14px;
    overflow:hidden;
    display:flex; flex-direction:column;
}
.mp-sidebar-head {
    padding:13px 16px;
    border-bottom:1px solid var(--border);
}
.mp-sidebar-head-title {
    font-family:'Epilogue',sans-serif;
    font-size:13px; font-weight:700;
    color:var(--text-1); margin-bottom:10px;
}
.mp-search {
    width:100%;
    height:34px;
    border:1px solid var(--border);
    border-radius:9px;
    padding:0 12px;
    font-size:12px;
    font-family:'DM Sans',sans-serif;
    background:var(--bg);
    color:var(--text-1);
    outline:none;
    transition:border-color .15s, background .15s;
}
.mp-search:focus { border-color:var(--border-md); background:var(--white); }
.mp-search::placeholder { color:var(--text-3); }

/* ── Vehicle list ── */
.mp-list { overflow-y:auto; flex:1; max-height:460px; }
.mp-item {
    display:flex; align-items:center; gap:10px;
    padding:10px 16px;
    border-bottom:1px solid var(--border);
    cursor:pointer;
    transition:background .1s;
}
.mp-item:last-child { border-bottom:none; }
.mp-item:hover { background:var(--bg); }
.mp-item.active { background:rgba(17,24,39,.04); }
.mp-item.no-loc { opacity:.45; cursor:default; }
.mp-item-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
.mp-item-plate {
    font-family:'DM Mono',monospace;
    font-size:12px; font-weight:500; color:var(--text-1);
}
.mp-item-label { font-size:11px; color:var(--text-3); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

/* ── Small status badge ── */
.mp-badge {
    margin-left:auto; flex-shrink:0;
    font-size:9px; font-weight:700;
    padding:2px 7px; border-radius:5px;
}
.mp-b-ongoing     { background:var(--s-green-bg);  color:var(--s-green-text); }
.mp-b-available   { background:var(--s-blue-bg);   color:var(--s-blue-text); }
.mp-b-rented      { background:var(--s-blue-bg);   color:var(--s-blue-text); }
.mp-b-maintenance { background:var(--s-amber-bg);  color:var(--s-amber-text); }

.mp-footer {
    padding:9px 16px;
    border-top:1px solid var(--border);
    font-size:10px; color:var(--text-3);
    text-align:center; font-weight:500;
}

/* ── Leaflet overrides ── */
.leaflet-container { font-family:'DM Sans',sans-serif; }
.leaflet-control-zoom { border:none !important; box-shadow:0 2px 8px rgba(17,24,39,.08) !important; }
.leaflet-control-zoom a {
    color:var(--text-1) !important; border:1px solid var(--border) !important;
    border-radius:8px !important; width:28px !important; height:28px !important;
    line-height:27px !important;
}
.leaflet-control-zoom a:hover { background:var(--bg) !important; }
.leaflet-popup-content-wrapper {
    border-radius:12px !important; padding:0 !important;
    box-shadow:0 8px 24px rgba(17,24,39,.13) !important;
    border:1px solid var(--border) !important;
}
.leaflet-popup-content { margin:0 !important; }
.leaflet-popup-tip-container { display:none; }

/* ── Popup ── */
.mp-popup { padding:13px 15px; min-width:185px; font-family:'DM Sans',sans-serif; }
.mp-popup-plate { font-family:'DM Mono',monospace; font-size:14px; font-weight:500; color:var(--dark); }
.mp-popup-vehicle { font-size:11px; color:var(--text-3); margin:2px 0 1px; }
.mp-popup-driver  { font-size:11px; color:var(--text-2); margin-bottom:8px; }
.mp-popup-status  { display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:600; padding:3px 9px; border-radius:6px; }
.mp-popup-time    { font-size:10px; color:var(--text-3); margin-top:6px; }
.mp-popup-stale   { font-size:10px; color:var(--s-amber-text); font-weight:600; margin-top:5px; display:flex; align-items:center; gap:4px; }

/* ── Map legend ── */
.mp-legend {
    background:var(--white);
    border:1px solid var(--border);
    border-radius:10px;
    padding:9px 12px;
    font-family:'DM Sans',sans-serif;
}
.mp-legend-title { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--text-3); margin-bottom:6px; }
.mp-legend-row { display:flex; align-items:center; gap:6px; font-size:11px; font-weight:500; color:var(--text-2); margin-bottom:4px; }
.mp-legend-row:last-child { margin-bottom:0; }
.leg-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }

/* ── Scrollbar ── */
.mp-list::-webkit-scrollbar { width:3px; }
.mp-list::-webkit-scrollbar-track { background:transparent; }
.mp-list::-webkit-scrollbar-thumb { background:var(--border-md); border-radius:99px; }

/* ── Responsive ── */
@media(max-width:1024px) { .mp-grid { grid-template-columns:1fr; } }
@media(max-width:640px)  { .mp-stats { grid-template-columns:1fr 1fr; } #fleet-map { height:320px; } }
</style>

@php
$pinColors    = ['ongoing'=>'#16a34a','available'=>'#2563eb','maintenance'=>'#d97706','rented'=>'#2563eb'];
$statusLabels = ['ongoing'=>'Berjalan','available'=>'Tersedia','maintenance'=>'Maintenance','rented'=>'Disewa'];
$listVehicles     = collect($vehicles ?? [])->map(fn($v)=>is_array($v)?$v:(array)$v)->values()->all();
$mappableVehicles = collect($listVehicles)->filter(fn($v)=>!empty($v['lat'])&&!empty($v['lon']))->values()->all();
@endphp

<div class="mp">

    {{-- ══ GREETING ══ --}}
    <div>
        <h2 style="font-family:'Epilogue',sans-serif;font-size:22px;font-weight:800;color:var(--text-1);letter-spacing:-.5px">Peta Armada</h2>
        <p style="font-size:13px;color:var(--text-2);margin-top:3px">
            Lokasi kendaraan real-time · hanya tampil saat bertugas
        </p>
    </div>

    {{-- ══ STAT STRIP ══ --}}
    <div class="mp-stats">
        <div class="mp-stat">
            <span class="mp-stat-dot" style="background:var(--text-2)"></span>
            <div><div class="mp-stat-val">{{ $stats['total'] }}</div><div class="mp-stat-lbl">Total</div></div>
        </div>
        <div class="mp-stat">
            <span class="mp-stat-dot" style="background:var(--s-green)"></span>
            <div><div class="mp-stat-val">{{ $stats['ongoing'] }}</div><div class="mp-stat-lbl">Berjalan</div></div>
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

    {{-- ══ MAIN GRID ══ --}}
    <div class="mp-grid">

        {{-- Map card ── --}}
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

        {{-- Sidebar ── --}}
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const vehicles = @json($mappableVehicles);

    const statusCfg = {
        ongoing:     { color:'#16a34a', bg:'#f0fdf4', text:'#14532d', label:'Berjalan'     },
        available:   { color:'#2563eb', bg:'#eff6ff', text:'#1e3a8a', label:'Tersedia'     },
        maintenance: { color:'#d97706', bg:'#fffbeb', text:'#78350f', label:'Maintenance'  },
        rented:      { color:'#2563eb', bg:'#eff6ff', text:'#1e3a8a', label:'Disewa'       },
    };

    const map = L.map('fleet-map', {
        center: [-7.2, 110.0], zoom: 7,
        minZoom: 5, maxZoom: 18,
        scrollWheelZoom: true,
        maxBounds: [[-11, 94], [6, 142]],
        maxBoundsViscosity: 0.9,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);

    function makeIcon(status, isStale) {
        const cfg   = statusCfg[status] || statusCfg.available;
        const color = isStale ? '#d97706' : cfg.color;
        const pulse = (status === 'ongoing' && !isStale) ? `
            <circle cx="18" cy="18" r="14" fill="none" stroke="${color}" stroke-width="1.5" opacity="0.4">
                <animate attributeName="r" values="14;22;14" dur="2s" repeatCount="indefinite"/>
                <animate attributeName="opacity" values="0.4;0;0.4" dur="2s" repeatCount="indefinite"/>
            </circle>` : '';
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36">
            ${pulse}
            <circle cx="18" cy="18" r="12" fill="${color}" fill-opacity="0.15"/>
            <circle cx="18" cy="18" r="8"  fill="${color}"/>
            <circle cx="18" cy="18" r="3"  fill="white"/>
        </svg>`;
        return L.divIcon({ html:svg, className:'', iconSize:[36,36], iconAnchor:[18,18], popupAnchor:[0,-20] });
    }

    const markerMap = {};

    vehicles.forEach(v => {
        if (!v.lat || !v.lon) return;
        const cfg      = statusCfg[v.status] || statusCfg.available;
        const color    = v.is_stale ? '#d97706' : cfg.color;
        const bg       = v.is_stale ? '#fffbeb' : cfg.bg;
        const txtColor = v.is_stale ? '#78350f' : cfg.text;
        const lbl      = v.is_stale ? 'Lokasi Lama' : cfg.label;

        const staleRow  = v.is_stale && v.location_updated_at
            ? `<div class="mp-popup-stale">⚠ Terakhir: ${v.location_updated_at}</div>` : '';
        const updateRow = v.location_updated_at && !v.is_stale
            ? `<div class="mp-popup-time">Update: ${v.location_updated_at}</div>` : '';

        const marker = L.marker([v.lat, v.lon], { icon: makeIcon(v.status, v.is_stale) })
            .bindPopup(`<div class="mp-popup">
                <div class="mp-popup-plate">${v.plate}</div>
                <div class="mp-popup-vehicle">${v.label}</div>
                <div class="mp-popup-driver">Driver: ${v.driver}</div>
                <span class="mp-popup-status" style="background:${bg};color:${txtColor}">
                    <span style="width:6px;height:6px;border-radius:50%;background:${color};display:inline-block"></span>
                    ${lbl}
                </span>
                ${updateRow}${staleRow}
            </div>`, { maxWidth:220 })
            .addTo(map);

        markerMap[v.id] = marker;
    });

    // ── Legend ──
    const legend = L.control({ position: 'bottomleft' });
    legend.onAdd = () => {
        const d = L.DomUtil.create('div', 'mp-legend');
        d.innerHTML = `
            <div class="mp-legend-title">Legenda</div>
            <div class="mp-legend-row"><span class="leg-dot" style="background:#16a34a"></span>Berjalan</div>
            <div class="mp-legend-row"><span class="leg-dot" style="background:#2563eb"></span>Tersedia / Disewa</div>
            <div class="mp-legend-row"><span class="leg-dot" style="background:#d97706"></span>Maintenance / Lokasi Lama</div>`;
        return d;
    };
    legend.addTo(map);
    setTimeout(() => map.invalidateSize(), 300);

    // ── Focus from sidebar ──
    window.focusVehicle = function (id) {
        document.querySelectorAll('.mp-item').forEach(el => el.classList.remove('active'));
        const item = document.querySelector(`.mp-item[data-id="${id}"]`);
        if (item) { item.classList.add('active'); item.scrollIntoView({ behavior:'smooth', block:'nearest' }); }
        const marker = markerMap[id];
        if (marker) {
            map.flyTo(marker.getLatLng(), 15, { animate:true, duration:0.8 });
            setTimeout(() => marker.openPopup(), 850);
        }
    };

    // ── Search ──
    document.getElementById('vehicleSearch').addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('.mp-item').forEach(el => {
            const plate = el.querySelector('.mp-item-plate')?.textContent.toLowerCase() || '';
            const label = el.querySelector('.mp-item-label')?.textContent.toLowerCase() || '';
            el.style.display = (plate.includes(q) || label.includes(q)) ? '' : 'none';
        });
    });
});
</script>

</x-app-layout>