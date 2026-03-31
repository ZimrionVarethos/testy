<x-app-layout>
    <x-slot name="header">Detail Kendaraan</x-slot>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

    <style>
    .sv-root *, .sv-root *::before, .sv-root *::after { box-sizing: border-box; }
    .sv-root { font-family: 'Plus Jakarta Sans', sans-serif; background: #F8FAFC; padding: 24px 0; }
    .sv-wrap { max-width: 1280px; margin: 0 auto; padding: 0 24px; }
    .mono { font-family: 'JetBrains Mono', monospace; }

    .sv-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
    .sv-header-left { display: flex; align-items: center; gap: 14px; }
    .sv-back { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600; color: #4F46E5; text-decoration: none; background: #EEF2FF; padding: 6px 12px; border-radius: 8px; }
    .sv-back:hover { background: #E0E7FF; }
    .sv-title { font-size: 20px; font-weight: 800; color: #0F172A; margin: 0; padding: 0; }
    .sv-subtitle { font-size: 13px; color: #94A3B8; margin: 3px 0 0; padding: 0; }
    .sv-status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px; border-radius: 99px; font-size: 12px; font-weight: 700; }

    .sv-main { display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start; }
    @media (max-width: 1024px) { .sv-main { grid-template-columns: 1fr; } }

    .sv-map-card { background: #fff; border: 1px solid #E2E8F0; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
    .sv-map-topbar { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-bottom: 1px solid #F1F5F9; }
    .sv-map-topbar-left { display: flex; align-items: center; gap: 8px; }
    .sv-live-dot { width: 8px; height: 8px; border-radius: 50%; background: #10B981; animation: pdot 2s infinite; }
    .sv-stale-dot { width: 8px; height: 8px; border-radius: 50%; background: #F59E0B; }
    @keyframes pdot { 0%,100%{opacity:1} 50%{opacity:.3} }
    .sv-map-name  { font-size: 14px; font-weight: 700; color: #1E293B; }
    .sv-last-seen { font-size: 11px; color: #94A3B8; }
    #vehicle-map  { height: 420px; width: 100%; z-index: 0; display: block; }

    /* No location placeholder */
    .sv-no-loc { height: 420px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #94A3B8; gap: 10px; }
    .sv-no-loc svg { opacity: .3; }
    .sv-no-loc p { font-size: 13px; font-weight: 600; margin: 0; }
    .sv-no-loc small { font-size: 11px; }

    .sv-panel { display: flex; flex-direction: column; gap: 16px; }
    .sv-card { background: #fff; border: 1px solid #E2E8F0; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
    .sv-card-head { padding: 14px 18px; border-bottom: 1px solid #F1F5F9; }
    .sv-card-title { font-size: 13px; font-weight: 700; color: #0F172A; }
    .sv-card-body { padding: 16px 18px; }

    .sv-row { display: flex; align-items: flex-start; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid #F8FAFC; gap: 8px; }
    .sv-row:last-child { border-bottom: none; padding-bottom: 0; }
    .sv-row-label { font-size: 11px; font-weight: 600; color: #94A3B8; text-transform: uppercase; letter-spacing: .05em; white-space: nowrap; }
    .sv-row-val   { font-size: 13px; font-weight: 600; color: #0F172A; text-align: right; }

    .sv-loc-card { border-radius: 12px; padding: 14px 16px; }
    .sv-loc-card.known   { background: #F0FDF4; border: 1px solid #BBF7D0; }
    .sv-loc-card.stale   { background: #FFFBEB; border: 1px solid #FDE68A; }
    .sv-loc-card.unknown { background: #FFF7ED; border: 1px solid #FED7AA; }
    .sv-loc-title  { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px; }
    .sv-loc-card.known   .sv-loc-title { color: #15803D; }
    .sv-loc-card.stale   .sv-loc-title { color: #D97706; }
    .sv-loc-card.unknown .sv-loc-title { color: #C2410C; }
    .sv-loc-coords { font-size: 13px; font-weight: 700; color: #0F172A; font-family: 'JetBrains Mono', monospace; margin-bottom: 4px; }
    .sv-loc-time   { font-size: 11px; color: #64748B; }
    .sv-loc-time strong { color: #0F172A; font-weight: 700; }

    .sv-no-booking { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 14px 16px; text-align: center; font-size: 12px; color: #94A3B8; }

    .sv-booking { background: #EEF2FF; border: 1px solid #C7D2FE; border-radius: 12px; padding: 14px 16px; }
    .sv-booking-code { font-size: 13px; font-weight: 800; color: #3730A3; font-family: 'JetBrains Mono', monospace; margin-bottom: 6px; }
    .sv-booking-row  { display: flex; gap: 8px; font-size: 11px; color: #4338CA; margin-bottom: 3px; }
    .sv-booking-row span { font-weight: 600; }
    .sv-booking-link { display: block; margin-top: 10px; font-size: 11px; font-weight: 600; color: #4F46E5; text-decoration: none; text-align: center; padding: 6px; background: white; border-radius: 7px; border: 1px solid #C7D2FE; }
    .sv-booking-link:hover { background: #F5F3FF; }

    .leaflet-container { font-family: 'Plus Jakarta Sans', sans-serif; }
    .leaflet-popup-content-wrapper { border-radius: 12px !important; padding: 0 !important; box-shadow: 0 8px 24px rgba(0,0,0,.15) !important; border: none !important; }
    .leaflet-popup-content { margin: 0 !important; }
    .leaflet-popup-tip-container { display: none; }
    .pin-popup { padding: 14px 16px; min-width: 180px; font-family: 'Plus Jakarta Sans', sans-serif; }
    .pin-popup-plate { font-size: 14px; font-weight: 800; color: #0F172A; font-family: 'JetBrains Mono', monospace; }
    .pin-popup-sub   { font-size: 11px; color: #94A3B8; margin-top: 3px; }
    </style>

    @php
    $statusConfig = [
        'ongoing'     => ['color'=>'#10B981','bg'=>'#ECFDF5','text'=>'#065F46','label'=>'Berjalan'],
        'available'   => ['color'=>'#3B82F6','bg'=>'#EFF6FF','text'=>'#1E40AF','label'=>'Tersedia'],
        'maintenance' => ['color'=>'#F59E0B','bg'=>'#FFFBEB','text'=>'#92400E','label'=>'Maintenance'],
        'rented'      => ['color'=>'#3B82F6','bg'=>'#EFF6FF','text'=>'#1E40AF','label'=>'Disewa'],
    ];

    $status  = $vehicle->status ?? 'available';
    $cfg     = $statusConfig[$status] ?? $statusConfig['available'];
    $hasLoc  = !empty($vehicle->last_lat) && !empty($vehicle->last_lon);
    $lat     = $hasLoc ? (float) $vehicle->last_lat : null;
    $lon     = $hasLoc ? (float) $vehicle->last_lon : null;
    $isStale = $vehicle->is_stale ?? false;

    $updatedAt = null;
    if (!empty($vehicle->last_location_updated_at)) {
        try { $updatedAt = \Carbon\Carbon::parse($vehicle->last_location_updated_at); } catch (\Exception $e) {}
    }

    $booking = $activeBooking ?? null;
    $vBrand  = trim($vehicle->brand ?? '');
    $vModel  = trim($vehicle->model ?? '');
    $vName   = trim($vehicle->name  ?? '');
    $vLabel  = $vName ?: ($vBrand && $vModel ? "$vBrand $vModel" : ($vBrand ?: $vModel));

    // Tentukan kelas card lokasi
    $locClass = 'unknown';
    if ($hasLoc && !$isStale) $locClass = 'known';
    if ($hasLoc && $isStale)  $locClass = 'stale';
    @endphp

    <div class="sv-root">
    <div class="sv-wrap">

        <div class="sv-header">
            <div class="sv-header-left">
                <a href="{{ route('admin.maps.index') }}" class="sv-back">← Peta Armada</a>
                <div>
                    <div class="sv-title mono">{{ $vehicle->plate_number ?? '-' }}</div>
                    <div class="sv-subtitle">{{ $vLabel }}@if($vehicle->year) · {{ $vehicle->year }}@endif</div>
                </div>
            </div>
            <span class="sv-status-badge" style="background:{{ $cfg['bg'] }};color:{{ $cfg['text'] }}">
                <span style="width:8px;height:8px;border-radius:50%;background:{{ $cfg['color'] }};display:inline-block"></span>
                {{ $cfg['label'] }}
            </span>
        </div>

        <div class="sv-main">

            <div class="sv-map-card">
                <div class="sv-map-topbar">
                    <div class="sv-map-topbar-left">
                        @if($hasLoc && !$isStale)
                            <span class="sv-live-dot"></span>
                            <span class="sv-map-name">Lokasi Live</span>
                        @elseif($hasLoc && $isStale)
                            <span class="sv-stale-dot"></span>
                            <span class="sv-map-name">Lokasi Terakhir</span>
                        @else
                            <span class="sv-stale-dot" style="background:#CBD5E1"></span>
                            <span class="sv-map-name">Lokasi Tidak Diketahui</span>
                        @endif
                    </div>
                    @if($updatedAt)
                        <span class="sv-last-seen {{ $isStale ? 'text-amber-500' : '' }}">
                            Terdeteksi {{ $updatedAt->diffForHumans() }}
                        </span>
                    @elseif(!$hasLoc && $booking)
                        <span class="sv-last-seen" style="color:#F59E0B">Driver belum mengirim lokasi</span>
                    @elseif(!$booking)
                        <span class="sv-last-seen">Tidak ada pesanan aktif</span>
                    @endif
                </div>

                @if($hasLoc)
                    <div id="vehicle-map"></div>
                @else
                    <div class="sv-no-loc">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                            <circle cx="12" cy="9" r="2.5"/>
                        </svg>
                        <p>Lokasi belum tersedia</p>
                        <small>
                            @if($booking)
                                Driver perlu mengaktifkan lokasi di perangkatnya
                            @else
                                Kendaraan tidak sedang bertugas
                            @endif
                        </small>
                    </div>
                @endif
            </div>

            <div class="sv-panel">

                {{-- Lokasi --}}
                <div class="sv-card">
                    <div class="sv-card-head"><div class="sv-card-title">📍 Lokasi Driver</div></div>
                    <div class="sv-card-body">
                        <div class="sv-loc-card {{ $locClass }}">
                            @if($hasLoc)
                                <div class="sv-loc-title">
                                    {{ $isStale ? 'Lokasi Terakhir (Tidak Realtime)' : 'Terdeteksi' }}
                                </div>
                                <div class="sv-loc-coords">{{ number_format($lat, 6) }}, {{ number_format($lon, 6) }}</div>
                                @if($updatedAt)
                                <div class="sv-loc-time">
                                    <strong>{{ $updatedAt->diffForHumans() }}</strong>
                                    &nbsp;·&nbsp; {{ $updatedAt->format('d M Y, H:i') }} WIB
                                </div>
                                @endif
                            @else
                                <div class="sv-loc-title">Tidak Diketahui</div>
                                <div class="sv-loc-time">
                                    @if($booking)
                                        Driver belum mengirim lokasi.
                                    @else
                                        Tidak ada pesanan aktif.
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Info Kendaraan --}}
                <div class="sv-card">
                    <div class="sv-card-head"><div class="sv-card-title">🚗 Info Kendaraan</div></div>
                    <div class="sv-card-body">
                        <div class="sv-row">
                            <span class="sv-row-label">Plat</span>
                            <span class="sv-row-val mono">{{ $vehicle->plate_number ?? '-' }}</span>
                        </div>
                        <div class="sv-row">
                            <span class="sv-row-label">Nama</span>
                            <span class="sv-row-val">{{ $vLabel ?: '-' }}</span>
                        </div>
                        @if($vehicle->brand ?? false)
                        <div class="sv-row">
                            <span class="sv-row-label">Merek</span>
                            <span class="sv-row-val">{{ $vehicle->brand }}</span>
                        </div>
                        @endif
                        @if($vehicle->model ?? false)
                        <div class="sv-row">
                            <span class="sv-row-label">Model</span>
                            <span class="sv-row-val">{{ $vehicle->model }}</span>
                        </div>
                        @endif
                        <div class="sv-row">
                            <span class="sv-row-label">Tahun</span>
                            <span class="sv-row-val">{{ $vehicle->year ?? '-' }}</span>
                        </div>
                        <div class="sv-row">
                            <span class="sv-row-label">Status</span>
                            <span class="sv-row-val">
                                <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:3px 9px;border-radius:99px;background:{{ $cfg['bg'] }};color:{{ $cfg['text'] }}">
                                    {{ $cfg['label'] }}
                                </span>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Driver --}}
                @if(!empty($vehicle->driver['name']))
                <div class="sv-card">
                    <div class="sv-card-head"><div class="sv-card-title">👤 Driver Aktif</div></div>
                    <div class="sv-card-body">
                        <div class="sv-row">
                            <span class="sv-row-label">Nama</span>
                            <span class="sv-row-val">{{ $vehicle->driver['name'] ?? '-' }}</span>
                        </div>
                        @if(!empty($vehicle->driver['phone']))
                        <div class="sv-row">
                            <span class="sv-row-label">Telp</span>
                            <span class="sv-row-val mono">{{ $vehicle->driver['phone'] }}</span>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Pesanan Aktif --}}
                <div class="sv-card">
                    <div class="sv-card-head"><div class="sv-card-title">📋 Pesanan Aktif</div></div>
                    <div class="sv-card-body">
                        @if($booking)
                        <div class="sv-booking">
                            <div class="sv-booking-code">{{ $booking->booking_code }}</div>
                            <div class="sv-booking-row"><span>Pengguna:</span> {{ $booking->user['name'] ?? '-' }}</div>
                            <div class="sv-booking-row"><span>Mulai:</span> {{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y') }}</div>
                            <div class="sv-booking-row"><span>Selesai:</span> {{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y') }}</div>
                            <a href="{{ route('admin.bookings.show', $booking->_id) }}" class="sv-booking-link">
                                Lihat Detail Pesanan →
                            </a>
                        </div>
                        @else
                        <div class="sv-no-booking">Tidak ada pesanan aktif saat ini.</div>
                        @endif
                    </div>
                </div>

            </div>
        </div>

    </div>
    </div>

    @if($hasLoc)
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const lat     = {{ $lat }};
        const lon     = {{ $lon }};
        const status  = '{{ $status }}';
        const plate   = '{{ addslashes($vehicle->plate_number ?? '') }}';
        const label   = '{{ addslashes($vLabel) }}';
        const isStale = {{ $isStale ? 'true' : 'false' }};

        const statusConfig = {
            ongoing:     { color: '#10B981', bg: '#ECFDF5', text: '#065F46', label: 'Berjalan' },
            available:   { color: '#3B82F6', bg: '#EFF6FF', text: '#1E40AF', label: 'Tersedia' },
            maintenance: { color: '#F59E0B', bg: '#FFFBEB', text: '#92400E', label: 'Maintenance' },
            rented:      { color: '#3B82F6', bg: '#EFF6FF', text: '#1E40AF', label: 'Disewa' },
        };
        const cfg      = statusConfig[status] || statusConfig.available;
        const pinColor = isStale ? '#F59E0B' : cfg.color;

        const map = L.map('vehicle-map', {
            center: [lat, lon], zoom: 17,
            minZoom: 12, maxZoom: 18,
            zoomControl: false,
            scrollWheelZoom: false,
            dragging: false,
            doubleClickZoom: false,
            touchZoom: false,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(map);

        const pulse = (status === 'ongoing' && !isStale) ? `
            <circle cx="18" cy="18" r="15" fill="none" stroke="${pinColor}" stroke-width="2" opacity="0.5">
                <animate attributeName="r" values="15;26;15" dur="1.8s" repeatCount="indefinite"/>
                <animate attributeName="opacity" values="0.5;0;0.5" dur="1.8s" repeatCount="indefinite"/>
            </circle>` : '';

        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 36 36">
            ${pulse}
            <circle cx="18" cy="18" r="14" fill="${pinColor}" fill-opacity="0.2"/>
            <circle cx="18" cy="18" r="10" fill="${pinColor}"/>
            <circle cx="18" cy="18" r="4" fill="white"/>
        </svg>`;
        const icon = L.divIcon({ html: svg, className: '', iconSize: [40,40], iconAnchor: [20,20], popupAnchor: [0,-24] });

        L.marker([lat, lon], { icon })
            .bindPopup(`<div class="pin-popup">
                <div class="pin-popup-plate">${plate}</div>
                <div class="pin-popup-sub">${label}</div>
                <div class="pin-popup-sub" style="color:${pinColor};font-weight:600">
                    ${isStale ? '⚠ Lokasi Terakhir' : cfg.label}
                </div>
                <div class="pin-popup-sub">${lat.toFixed(6)}, ${lon.toFixed(6)}</div>
            </div>`, { maxWidth: 220 })
            .addTo(map)
            .openPopup();

        L.circle([lat, lon], {
            radius: 60,
            color: pinColor,
            fillColor: pinColor,
            fillOpacity: 0.08,
            weight: 1.5,
        }).addTo(map);

        setTimeout(() => map.invalidateSize(), 300);
    });
    </script>
    @endif

</x-app-layout>