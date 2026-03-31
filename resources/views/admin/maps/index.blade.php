<x-app-layout>
    <x-slot name="header">Peta Armada</x-slot>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

    <style>
    .mp-root *, .mp-root *::before, .mp-root *::after { box-sizing: border-box; }
    .mp-root { font-family: 'Plus Jakarta Sans', sans-serif; background: #F8FAFC; padding: 24px 0; }
    .mp-wrap { max-width: 1280px; margin: 0 auto; padding: 0 24px; }

    .mp-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 20px; }
    .mp-header h2 { font-size: 20px; font-weight: 800; color: #0F172A; margin: 0; padding: 0; }
    .mp-header p  { font-size: 13px; color: #94A3B8; margin: 3px 0 0; padding: 0; }

    .mp-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px; }
    @media (max-width: 640px) { .mp-stats { grid-template-columns: repeat(2, 1fr); } }
    .mp-stat { display: flex; align-items: center; gap: 12px; background: #fff; border: 1px solid #E2E8F0; border-radius: 12px; padding: 14px 18px; }
    .mp-stat-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .mp-stat-val { font-size: 22px; font-weight: 800; color: #0F172A; line-height: 1; font-family: 'JetBrains Mono', monospace; }
    .mp-stat-lbl { font-size: 10px; font-weight: 700; color: #94A3B8; text-transform: uppercase; letter-spacing: .06em; margin-top: 3px; }

    .mp-main { display: grid; grid-template-columns: 1fr 300px; gap: 20px; align-items: start; }
    @media (max-width: 1024px) { .mp-main { grid-template-columns: 1fr; } }

    .mp-map-card { background: #fff; border: 1px solid #E2E8F0; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
    .mp-map-header { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-bottom: 1px solid #F1F5F9; }
    .mp-map-header-left { display: flex; align-items: center; gap: 8px; }
    .mp-live-dot { width: 8px; height: 8px; border-radius: 50%; background: #10B981; animation: pulse-dot 2s infinite; }
    @keyframes pulse-dot { 0%,100%{opacity:1} 50%{opacity:.4} }
    .mp-map-title { font-size: 14px; font-weight: 700; color: #1E293B; }
    .mp-map-hint  { font-size: 11px; color: #94A3B8; }
    #fleet-map { height: 540px; width: 100%; z-index: 0; display: block; }

    .mp-sidebar { background: #fff; border: 1px solid #E2E8F0; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.05); display: flex; flex-direction: column; }
    .mp-sidebar-head { padding: 14px 16px; border-bottom: 1px solid #F1F5F9; }
    .mp-sidebar-head p { font-size: 13px; font-weight: 700; color: #0F172A; margin: 0 0 10px; padding: 0; }
    .mp-search { width: 100%; border: 1px solid #E2E8F0; border-radius: 9px; padding: 8px 13px; font-size: 13px; font-family: 'Plus Jakarta Sans', sans-serif; outline: none; background: #F8FAFC; color: #0F172A; transition: border-color .15s, box-shadow .15s; }
    .mp-search:focus { border-color: #4F46E5; box-shadow: 0 0 0 3px rgba(79,70,229,.1); background: #fff; }
    .mp-list { overflow-y: auto; flex: 1; max-height: 480px; }
    .mp-item { display: flex; align-items: center; gap: 10px; padding: 10px 16px; cursor: pointer; border-bottom: 1px solid #F1F5F9; transition: background .1s; }
    .mp-item:last-child { border-bottom: none; }
    .mp-item:hover  { background: #F8FAFC; }
    .mp-item.active { background: #EEF2FF; }
    .mp-item.no-loc { opacity: .5; cursor: default; }
    .mp-item-dot   { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
    .mp-item-body  { flex: 1; min-width: 0; }
    .mp-item-plate { font-size: 12px; font-weight: 700; color: #0F172A; font-family: 'JetBrains Mono', monospace; }
    .mp-item-label { font-size: 11px; color: #94A3B8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mp-badge { margin-left: auto; flex-shrink: 0; padding: 2px 8px; border-radius: 99px; font-size: 10px; font-weight: 700; }
    .badge-ongoing     { background: #ECFDF5; color: #065F46; }
    .badge-available   { background: #EFF6FF; color: #1E40AF; }
    .badge-maintenance { background: #FFFBEB; color: #92400E; }
    .badge-rented      { background: #EFF6FF; color: #1E40AF; }
    .mp-footer { padding: 10px 16px; border-top: 1px solid #F1F5F9; font-size: 11px; color: #94A3B8; text-align: center; }

    .leaflet-container { font-family: 'Plus Jakarta Sans', sans-serif; }
    .leaflet-control-zoom { border: none !important; box-shadow: 0 2px 8px rgba(0,0,0,.12) !important; }
    .leaflet-control-zoom a { font-size: 16px !important; color: #374151 !important; border-radius: 8px !important; border: none !important; width: 30px !important; height: 30px !important; line-height: 30px !important; }
    .leaflet-control-zoom a:hover { background: #EEF2FF !important; color: #4F46E5 !important; }
    .leaflet-popup-content-wrapper { border-radius: 12px !important; padding: 0 !important; box-shadow: 0 8px 24px rgba(0,0,0,.15) !important; border: none !important; }
    .leaflet-popup-content { margin: 0 !important; }
    .leaflet-popup-tip-container { display: none; }

    .pin-popup { padding: 14px 16px; min-width: 190px; font-family: 'Plus Jakarta Sans', sans-serif; }
    .pin-popup-plate  { font-size: 14px; font-weight: 800; color: #0F172A; font-family: 'JetBrains Mono', monospace; }
    .pin-popup-label  { font-size: 11px; color: #64748B; margin: 2px 0; }
    .pin-popup-driver { font-size: 11px; color: #94A3B8; margin-bottom: 7px; }
    .pin-popup-status { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 99px; }
    .pin-popup-stale  { font-size: 10px; color: #D97706; font-weight: 600; margin-top: 6px; display: flex; align-items: center; gap: 4px; }

    .map-legend-box { background: white; border: 1px solid #E2E8F0; border-radius: 10px; padding: 10px 14px; font-family: 'Plus Jakarta Sans', sans-serif; box-shadow: 0 2px 8px rgba(0,0,0,.06); line-height: 2; }
    .map-legend-box p { font-size: 10px; font-weight: 800; color: #374151; margin: 0 0 4px; padding: 0; letter-spacing: .05em; }
    .map-legend-box span { display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; color: #475569; }
    .leg-dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
    </style>

    @php
    $pinColors    = ['ongoing'=>'#10B981','available'=>'#3B82F6','maintenance'=>'#F59E0B','rented'=>'#3B82F6'];
    $statusLabels = ['ongoing'=>'Berjalan','available'=>'Tersedia','maintenance'=>'Maintenance','rented'=>'Disewa'];

    $listVehicles     = collect($vehicles ?? [])->map(fn($v) => is_array($v) ? $v : (array)$v)->values()->all();
    $mappableVehicles = collect($listVehicles)->filter(fn($v) => !empty($v['lat']) && !empty($v['lon']))->values()->all();
    @endphp

    <div class="mp-root">
    <div class="mp-wrap">

        <div class="mp-header">
            <div>
                <h2>Peta Armada</h2>
                <p>Lokasi kendaraan · hanya tampil saat bertugas</p>
            </div>
        </div>

        <div class="mp-stats">
            <div class="mp-stat">
                <span class="mp-stat-dot" style="background:#6366F1"></span>
                <div><div class="mp-stat-val">{{ $stats['total'] }}</div><div class="mp-stat-lbl">Total</div></div>
            </div>
            <div class="mp-stat">
                <span class="mp-stat-dot" style="background:#10B981"></span>
                <div><div class="mp-stat-val">{{ $stats['ongoing'] }}</div><div class="mp-stat-lbl">Berjalan</div></div>
            </div>
            <div class="mp-stat">
                <span class="mp-stat-dot" style="background:#3B82F6"></span>
                <div><div class="mp-stat-val">{{ $stats['available'] }}</div><div class="mp-stat-lbl">Tersedia</div></div>
            </div>
            <div class="mp-stat">
                <span class="mp-stat-dot" style="background:#F59E0B"></span>
                <div><div class="mp-stat-val">{{ $stats['maintenance'] }}</div><div class="mp-stat-lbl">Maintenance</div></div>
            </div>
        </div>

        <div class="mp-main">

            <div class="mp-map-card">
                <div class="mp-map-header">
                    <div class="mp-map-header-left">
                        <span class="mp-live-dot"></span>
                        <span class="mp-map-title">Live Map</span>
                        @if(count($mappableVehicles) === 0)
                            <span style="font-size:10px;font-weight:700;background:#FFF7ED;border:1px solid #FED7AA;color:#C2410C;padding:2px 8px;border-radius:99px">
                                Tidak ada yang bertugas
                            </span>
                        @endif
                    </div>
                    <span class="mp-map-hint">Klik pin untuk detail · Scroll untuk zoom</span>
                </div>
                <div id="fleet-map"></div>
            </div>

            <div class="mp-sidebar">
                <div class="mp-sidebar-head">
                    <p>Semua Kendaraan</p>
                    <input type="text" id="vehicleSearch" class="mp-search" placeholder="Cari plat / nama…">
                </div>
                <div class="mp-list" id="vehicleList">
                    @forelse($listVehicles as $v)
                    @php
                        $hasLoc   = !empty($v['lat']) && !empty($v['lon']);
                        $dotColor = $v['is_stale'] ? '#F59E0B' : ($pinColors[$v['status']] ?? '#94A3B8');
                        $dotColor = $hasLoc ? $dotColor : '#CBD5E1';
                    @endphp
                    <div class="mp-item {{ !$hasLoc ? 'no-loc' : '' }}"
                         data-id="{{ $v['id'] }}"
                         @if($hasLoc) onclick="focusVehicle('{{ $v['id'] }}')" @endif>
                        <span class="mp-item-dot" style="background:{{ $dotColor }}"></span>
                        <div class="mp-item-body">
                            <div class="mp-item-plate">{{ $v['plate'] }}</div>
                            <div class="mp-item-label">
                                @if($hasLoc && $v['has_active_booking'])
                                    Driver: {{ $v['driver'] }}
                                    @if($v['is_stale'])
                                        · <span style="color:#D97706">Lokasi lama</span>
                                    @endif
                                @elseif($v['has_active_booking'])
                                    <span style="color:#F59E0B">Menunggu lokasi driver</span>
                                @else
                                    {{ $v['label'] }} · <span style="color:#CBD5E1">Tidak bertugas</span>
                                @endif
                            </div>
                        </div>
                        <span class="mp-badge badge-{{ $v['status'] }}">{{ $statusLabels[$v['status']] ?? $v['status'] }}</span>
                    </div>
                    @empty
                    <div style="padding:32px 16px;text-align:center;color:#94A3B8;font-size:13px;">Belum ada kendaraan.</div>
                    @endforelse
                </div>
                <div class="mp-footer">{{ count($listVehicles) }} kendaraan terdaftar · {{ count($mappableVehicles) }} terlacak</div>
            </div>

        </div>
    </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const vehicles = @json($mappableVehicles);

        const statusConfig = {
            ongoing:     { color: '#10B981', bg: '#ECFDF5', text: '#065F46', label: 'Berjalan' },
            available:   { color: '#3B82F6', bg: '#EFF6FF', text: '#1E40AF', label: 'Tersedia' },
            maintenance: { color: '#F59E0B', bg: '#FFFBEB', text: '#92400E', label: 'Maintenance' },
            rented:      { color: '#3B82F6', bg: '#EFF6FF', text: '#1E40AF', label: 'Disewa' },
        };

        const jawaBounds = L.latLngBounds(L.latLng(-8.8, 105.0), L.latLng(-5.8, 115.0));
        const map = L.map('fleet-map', {
            center: [-7.2, 110.0], zoom: 7, minZoom: 6, maxZoom: 18,
            scrollWheelZoom: true,
            maxBounds: jawaBounds, maxBoundsViscosity: 1.0,
        });
        map.fitBounds(jawaBounds);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(map);

        function makeIcon(status, isStale) {
            const cfg   = statusConfig[status] || statusConfig.available;
            const color = isStale ? '#F59E0B' : cfg.color;
            const pulse = (status === 'ongoing' && !isStale) ? `
                <circle cx="18" cy="18" r="15" fill="none" stroke="${color}" stroke-width="2" opacity="0.5">
                    <animate attributeName="r" values="15;24;15" dur="2s" repeatCount="indefinite"/>
                    <animate attributeName="opacity" values="0.5;0;0.5" dur="2s" repeatCount="indefinite"/>
                </circle>` : '';
            const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36">
                ${pulse}
                <circle cx="18" cy="18" r="14" fill="${color}" fill-opacity="0.18"/>
                <circle cx="18" cy="18" r="9" fill="${color}"/>
                <circle cx="18" cy="18" r="3.5" fill="white"/>
            </svg>`;
            return L.divIcon({ html: svg, className: '', iconSize: [36,36], iconAnchor: [18,18], popupAnchor: [0,-22] });
        }

        const markerMap = {};

        vehicles.forEach(v => {
            if (!v.lat || !v.lon) return;
            const cfg      = statusConfig[v.status] || statusConfig.available;
            const pinColor = v.is_stale ? '#F59E0B' : cfg.color;
            const pinBg    = v.is_stale ? '#FFFBEB' : cfg.bg;
            const pinText  = v.is_stale ? '#92400E' : cfg.text;
            const pinLabel = v.is_stale ? 'Lokasi Lama' : cfg.label;

            const staleNote = v.is_stale && v.location_updated_at
                ? `<div class="pin-popup-stale">⚠ Terakhir: ${v.location_updated_at}</div>`
                : '';

            const marker = L.marker([v.lat, v.lon], { icon: makeIcon(v.status, v.is_stale) })
                .bindPopup(`<div class="pin-popup">
                    <div class="pin-popup-plate">${v.plate}</div>
                    <div class="pin-popup-label">${v.label}</div>
                    <div class="pin-popup-driver">Driver: ${v.driver}</div>
                    <span class="pin-popup-status" style="background:${pinBg};color:${pinText}">
                        <span style="width:7px;height:7px;border-radius:50%;background:${pinColor};display:inline-block;margin-right:3px"></span>
                        ${pinLabel}
                    </span>
                    ${staleNote}
                    <a href="/admin/maps/${v.id}" style="display:block;margin-top:8px;font-size:11px;font-weight:600;color:#4F46E5;text-decoration:none">
                        Lihat Detail →
                    </a>
                </div>`, { maxWidth: 240 })
                .addTo(map);

            markerMap[v.id] = marker;
        });

        const legend = L.control({ position: 'bottomleft' });
        legend.onAdd = () => {
            const d = L.DomUtil.create('div', 'map-legend-box');
            d.innerHTML = `<p>LEGENDA</p>
                <span><i class="leg-dot" style="background:#10B981"></i> Berjalan</span>
                <span><i class="leg-dot" style="background:#3B82F6"></i> Tersedia / Disewa</span>
                <span><i class="leg-dot" style="background:#F59E0B"></i> Maintenance / Lokasi Lama</span>`;
            return d;
        };
        legend.addTo(map);
        setTimeout(() => map.invalidateSize(), 300);

        window.focusVehicle = function (id) {
            document.querySelectorAll('.mp-item').forEach(el => el.classList.remove('active'));
            const item = document.querySelector(`.mp-item[data-id="${id}"]`);
            if (item) { item.classList.add('active'); item.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }
            const marker = markerMap[id];
            if (marker) {
                map.flyTo(marker.getLatLng(), 15, { animate: true, duration: 0.8 });
                setTimeout(() => marker.openPopup(), 850);
            }
        };

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