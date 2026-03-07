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
    .mp-demo-badge { font-size: 10px; font-weight: 700; background: #FFFBEB; border: 1px solid #FDE68A; color: #92400E; padding: 2px 8px; border-radius: 99px; }
    .mp-map-hint { font-size: 11px; color: #94A3B8; }
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
    .mp-item-dot  { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
    .mp-item-body { flex: 1; min-width: 0; }
    .mp-item-plate{ font-size: 12px; font-weight: 700; color: #0F172A; font-family: 'JetBrains Mono', monospace; }
    .mp-item-label{ font-size: 11px; color: #94A3B8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
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
    .pin-popup-dummy  { font-size: 10px; color: #F59E0B; font-weight: 600; margin-top: 6px; }

    .map-legend-box { background: white; border: 1px solid #E2E8F0; border-radius: 10px; padding: 10px 14px; font-family: 'Plus Jakarta Sans', sans-serif; box-shadow: 0 2px 8px rgba(0,0,0,.06); line-height: 2; }
    .map-legend-box p    { font-size: 10px; font-weight: 800; color: #374151; margin: 0 0 4px; padding: 0; letter-spacing: .05em; }
    .map-legend-box span { display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; color: #475569; }
    .leg-dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
    </style>

    @php
    // Controller sudah map semua field — tinggal pakai langsung
    // Format dari controller: ['id','plate','label','driver','status','lat','lon']
    $pinColors    = ['ongoing'=>'#10B981','available'=>'#3B82F6','maintenance'=>'#F59E0B','rented'=>'#3B82F6'];
    $statusLabels = ['ongoing'=>'Berjalan','available'=>'Tersedia','maintenance'=>'Maintenance','rented'=>'Disewa'];

    // Dummy coords untuk kendaraan yang belum punya lokasi GPS
    $dummyCoords = [
        ['lat'=>-6.2088,'lon'=>106.8456],['lat'=>-6.1751,'lon'=>106.8272],
        ['lat'=>-6.2615,'lon'=>106.9816],['lat'=>-6.3741,'lon'=>106.8296],
        ['lat'=>-6.4025,'lon'=>106.7942],['lat'=>-6.9175,'lon'=>107.6191],
        ['lat'=>-6.8694,'lon'=>107.5590],['lat'=>-7.3305,'lon'=>108.2166],
        ['lat'=>-6.9932,'lon'=>110.4229],['lat'=>-7.4239,'lon'=>110.2109],
        ['lat'=>-7.7971,'lon'=>110.3688],['lat'=>-7.5756,'lon'=>110.8243],
        ['lat'=>-7.9666,'lon'=>111.4831],['lat'=>-7.2492,'lon'=>112.7508],
        ['lat'=>-7.1560,'lon'=>112.6441],['lat'=>-7.3619,'lon'=>112.7371],
        ['lat'=>-7.9839,'lon'=>112.6214],['lat'=>-8.1845,'lon'=>114.3680],
        ['lat'=>-7.7221,'lon'=>113.2151],['lat'=>-6.7320,'lon'=>108.5523],
        ['lat'=>-7.0051,'lon'=>107.6816],['lat'=>-7.1500,'lon'=>110.1403],
        ['lat'=>-8.0057,'lon'=>111.8944],['lat'=>-7.5166,'lon'=>111.5667],
    ];

    // $vehicles dari controller sudah berupa Collection of arrays
    $rawList  = isset($vehicles) ? collect($vehicles) : collect([]);
    $coordIdx = 0;

    $listVehicles = $rawList->map(function($v) use ($dummyCoords, &$coordIdx) {
        // v sudah berupa array dengan key: id, plate, label, driver, status, lat, lon
        $arr = is_array($v) ? $v : (array)$v;

        $hasLoc = !empty($arr['lat']) && !empty($arr['lon']);
        $lat    = $hasLoc ? (float)$arr['lat'] : (float)$dummyCoords[$coordIdx % count($dummyCoords)]['lat'];
        $lon    = $hasLoc ? (float)$arr['lon'] : (float)$dummyCoords[$coordIdx % count($dummyCoords)]['lon'];
        if (!$hasLoc) $coordIdx++;

        return [
            'id'       => $arr['id']     ?? '',
            'plate'    => $arr['plate']  ?? '-',
            'label'    => $arr['label']  ?? '-',   // ← langsung pakai dari controller
            'driver'   => $arr['driver'] ?? '-',   // ← langsung pakai dari controller
            'status'   => $arr['status'] ?? 'available',
            'lat'      => $lat,
            'lon'      => $lon,
            'is_dummy' => !$hasLoc,
        ];
    })->values()->all();

    $dummyCount = collect($listVehicles)->where('is_dummy', true)->count();
    @endphp

    <div class="mp-root">
    <div class="mp-wrap">

        <div class="mp-header">
            <div>
                <h2>Peta Armada</h2>
                <p>Lokasi kendaraan real-time · Pulau Jawa</p>
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
                        <span class="mp-map-title">Live Map · Pulau Jawa</span>
                        @if($dummyCount > 0)
                            <span class="mp-demo-badge">{{ $dummyCount }} LOKASI DUMMY</span>
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
                    @php $dotColor = $v['is_dummy'] ? '#94A3B8' : ($pinColors[$v['status']] ?? '#94A3B8'); @endphp
                    <div class="mp-item" data-id="{{ $v['id'] }}" onclick="focusVehicle('{{ $v['id'] }}')">
                        <span class="mp-item-dot" style="background:{{ $dotColor }}"></span>
                        <div class="mp-item-body">
                            <div class="mp-item-plate">{{ $v['plate'] }}</div>
                            <div class="mp-item-label">
                                {{ $v['label'] }}@if($v['is_dummy']) · <span style="color:#F59E0B">dummy loc</span>@endif
                            </div>
                        </div>
                        <span class="mp-badge badge-{{ $v['status'] }}">{{ $statusLabels[$v['status']] ?? $v['status'] }}</span>
                    </div>
                    @empty
                    <div style="padding:32px 16px;text-align:center;color:#94A3B8;font-size:13px;">Belum ada kendaraan.</div>
                    @endforelse
                </div>
                <div class="mp-footer">{{ count($listVehicles) }} kendaraan terdaftar</div>
            </div>

        </div>
    </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const vehicles = @json($listVehicles);
        const statusConfig = {
            ongoing:     { color: '#10B981', bg: '#ECFDF5', text: '#065F46', label: 'Berjalan' },
            available:   { color: '#3B82F6', bg: '#EFF6FF', text: '#1E40AF', label: 'Tersedia' },
            maintenance: { color: '#F59E0B', bg: '#FFFBEB', text: '#92400E', label: 'Maintenance' },
            rented:      { color: '#3B82F6', bg: '#EFF6FF', text: '#1E40AF', label: 'Disewa' },
        };

        const jawaBounds = L.latLngBounds(L.latLng(-8.8, 105.0), L.latLng(-5.8, 115.0));
        const map = L.map('fleet-map', {
            center: [-7.2, 110.0], zoom: 7, minZoom: 7, maxZoom: 18,
            scrollWheelZoom: true, maxBounds: jawaBounds, maxBoundsViscosity: 1.0,
        });
        map.fitBounds(jawaBounds);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>', maxZoom: 19,
        }).addTo(map);

        function makeIcon(status, isDummy) {
            const cfg = statusConfig[status] || statusConfig.available;
            const color = isDummy ? '#94A3B8' : cfg.color;
            const pulse = (status === 'ongoing' && !isDummy) ? `
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
            const cfg       = statusConfig[v.status] || statusConfig.available;
            const pinColor  = v.is_dummy ? '#94A3B8' : cfg.color;
            const pinBg     = v.is_dummy ? '#F8FAFC'  : cfg.bg;
            const pinText   = v.is_dummy ? '#475569'  : cfg.text;
            const pinLabel  = v.is_dummy ? 'Dummy'    : cfg.label;
            const dummyNote = v.is_dummy ? `<div class="pin-popup-dummy">⚠ Lokasi belum tersedia · Koordinat dummy</div>` : '';

            const marker = L.marker([v.lat, v.lon], { icon: makeIcon(v.status, v.is_dummy) })
                .bindPopup(`<div class="pin-popup">
                    <div class="pin-popup-plate">${v.plate}</div>
                    <div class="pin-popup-label">${v.label}</div>
                    <div class="pin-popup-driver">Driver: ${v.driver}</div>
                    <span class="pin-popup-status" style="background:${pinBg};color:${pinText}">
                        <span style="width:7px;height:7px;border-radius:50%;background:${pinColor};display:inline-block;margin-right:3px"></span>
                        ${pinLabel}
                    </span>
                    ${dummyNote}
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
                <span><i class="leg-dot" style="background:#F59E0B"></i> Maintenance</span>
                <span><i class="leg-dot" style="background:#94A3B8"></i> Lokasi Dummy</span>`;
            return d;
        };
        legend.addTo(map);
        setTimeout(() => map.invalidateSize(), 300);

        window.focusVehicle = function(id) {
            document.querySelectorAll('.mp-item').forEach(el => el.classList.remove('active'));
            const item = document.querySelector(`.mp-item[data-id="${id}"]`);
            if (item) { item.classList.add('active'); item.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }
            const marker = markerMap[id];
            if (marker) {
                map.flyTo(marker.getLatLng(), 14, { animate: true, duration: 0.8 });
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