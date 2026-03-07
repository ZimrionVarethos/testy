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
    @keyframes pdot { 0%,100%{opacity:1} 50%{opacity:.3} }
    .sv-map-name { font-size: 14px; font-weight: 700; color: #1E293B; }
    .sv-last-seen { font-size: 11px; color: #94A3B8; }

    /* Map tanpa kontrol zoom — tinggi lebih pendek karena fokus detail */
    #vehicle-map { height: 420px; width: 100%; z-index: 0; display: block; }

    .sv-panel { display: flex; flex-direction: column; gap: 16px; }
    .sv-card { background: #fff; border: 1px solid #E2E8F0; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
    .sv-card-head { padding: 14px 18px; border-bottom: 1px solid #F1F5F9; }
    .sv-card-title { font-size: 13px; font-weight: 700; color: #0F172A; }
    .sv-card-body { padding: 16px 18px; }

    .sv-row { display: flex; align-items: flex-start; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid #F8FAFC; gap: 8px; }
    .sv-row:last-child { border-bottom: none; padding-bottom: 0; }
    .sv-row-label { font-size: 11px; font-weight: 600; color: #94A3B8; text-transform: uppercase; letter-spacing: .05em; white-space: nowrap; }
    .sv-row-val   { font-size: 13px; font-weight: 600; color: #0F172A; text-align: right; }
    .sv-row-val.mono { font-size: 12px; }

    .sv-loc-card { background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 12px; padding: 14px 16px; }
    .sv-loc-card.unknown { background: #FFF7ED; border-color: #FED7AA; }
    .sv-loc-title { font-size: 11px; font-weight: 700; color: #15803D; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px; }
    .sv-loc-card.unknown .sv-loc-title { color: #C2410C; }
    .sv-loc-coords { font-size: 13px; font-weight: 700; color: #0F172A; font-family: 'JetBrains Mono', monospace; margin-bottom: 4px; }
    .sv-loc-time   { font-size: 11px; color: #64748B; }
    .sv-loc-time strong { color: #0F172A; font-weight: 700; }

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
    .pin-popup-sub   { font-size: 11px; color: #94A3B8; margin-top: 2px; }
    </style>

    @php
    $statusConfig = [
        'ongoing'     => ['color'=>'#10B981','bg'=>'#ECFDF5','text'=>'#065F46','label'=>'Berjalan'],
        'available'   => ['color'=>'#3B82F6','bg'=>'#EFF6FF','text'=>'#1E40AF','label'=>'Tersedia'],
        'maintenance' => ['color'=>'#F59E0B','bg'=>'#FFFBEB','text'=>'#92400E','label'=>'Maintenance'],
        'rented'      => ['color'=>'#3B82F6','bg'=>'#EFF6FF','text'=>'#1E40AF','label'=>'Disewa'],
    ];

    if (!isset($vehicle)) {
        $vehicle = (object)[
            '_id'          => 'demo1',
            'plate_number' => 'B 1234 XYZ',
            'brand'        => 'Toyota',
            'model'        => 'Avanza',
            'year'         => 2022,
            'color'        => 'Putih',
            'status'       => 'ongoing',
            'last_lat'     => -6.2088,
            'last_lon'     => 106.8456,
            'last_location_updated_at' => now()->subMinutes(8)->toISOString(),
            'driver'       => ['name' => 'Budi Santoso', 'phone' => '0812-3456-7890'],
        ];
    }

    $status    = $vehicle->status ?? 'available';
    $cfg       = $statusConfig[$status] ?? $statusConfig['available'];
    $hasLoc    = !empty($vehicle->last_lat) && !empty($vehicle->last_lon);
    $lat       = $hasLoc ? (float)$vehicle->last_lat : null;
    $lon       = $hasLoc ? (float)$vehicle->last_lon : null;
    $updatedAt = null;
    if (!empty($vehicle->last_location_updated_at)) {
        try { $updatedAt = \Carbon\Carbon::parse($vehicle->last_location_updated_at); } catch (\Exception $e) {}
    }
    $booking = $activeBooking ?? null;

    // Nama kendaraan
    $vBrand = trim($vehicle->brand ?? '');
    $vModel = trim($vehicle->model ?? '');
    $vName  = trim($vehicle->name  ?? '');
    $vLabel = $vName ?: ($vBrand && $vModel ? "$vBrand $vModel" : ($vBrand ?: $vModel));
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
                        <span class="sv-live-dot"></span>
                        <span class="sv-map-name">{{ $vehicle->plate_number ?? 'Kendaraan' }}</span>
                    </div>
                    @if($updatedAt)
                        <span class="sv-last-seen">Terdeteksi {{ $updatedAt->diffForHumans() }}</span>
                    @elseif(!$hasLoc)
                        <span class="sv-last-seen" style="color:#F59E0B">Lokasi tidak diketahui</span>
                    @endif
                </div>
                <div id="vehicle-map"></div>
            </div>

            <div class="sv-panel">

                {{-- Lokasi --}}
                <div class="sv-card">
                    <div class="sv-card-head"><div class="sv-card-title">📍 Lokasi Terakhir</div></div>
                    <div class="sv-card-body">
                        @if($hasLoc)
                        <div class="sv-loc-card">
                            <div class="sv-loc-title">Terdeteksi</div>
                            <div class="sv-loc-coords">{{ number_format($lat, 6) }}, {{ number_format($lon, 6) }}</div>
                            @if($updatedAt)
                            <div class="sv-loc-time">
                                <strong>{{ $updatedAt->diffForHumans() }}</strong>
                                &nbsp;·&nbsp; {{ $updatedAt->format('d M Y, H:i') }} WIB
                            </div>
                            @endif
                        </div>
                        @else
                        <div class="sv-loc-card unknown">
                            <div class="sv-loc-title">Tidak Diketahui</div>
                            <div class="sv-loc-time">Kendaraan belum melaporkan lokasi.</div>
                        </div>
                        @endif
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
                            <span class="sv-row-label">Warna</span>
                            <span class="sv-row-val">{{ $vehicle->color ?? '-' }}</span>
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
                @if(!empty($vehicle->driver['name']) || !empty($vehicle->driver_name))
                <div class="sv-card">
                    <div class="sv-card-head"><div class="sv-card-title">👤 Driver Aktif</div></div>
                    <div class="sv-card-body">
                        <div class="sv-row">
                            <span class="sv-row-label">Nama</span>
                            <span class="sv-row-val">{{ $vehicle->driver['name'] ?? $vehicle->driver_name ?? '-' }}</span>
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

                {{-- Booking Aktif --}}
                @if($booking)
                <div class="sv-card">
                    <div class="sv-card-head"><div class="sv-card-title">📋 Pesanan Aktif</div></div>
                    <div class="sv-card-body">
                        <div class="sv-booking">
                            <div class="sv-booking-code">{{ $booking->booking_code }}</div>
                            <div class="sv-booking-row"><span>Pengguna:</span> {{ $booking->user['name'] ?? '-' }}</div>
                            <div class="sv-booking-row"><span>Mulai:</span> {{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y') }}</div>
                            <div class="sv-booking-row"><span>Selesai:</span> {{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y') }}</div>
                            <a href="{{ route('admin.bookings.show', $booking->_id) }}" class="sv-booking-link">Lihat Detail Pesanan →</a>
                        </div>
                    </div>
                </div>
                @endif

            </div>
        </div>

    </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const hasLoc = {{ $hasLoc ? 'true' : 'false' }};
        const lat    = {{ $lat ?? 'null' }};
        const lon    = {{ $lon ?? 'null' }};
        const status = '{{ $status }}';
        const plate  = '{{ addslashes($vehicle->plate_number ?? '') }}';
        const label  = '{{ addslashes($vLabel) }}';

        const statusConfig = {
            ongoing:     { color: '#10B981', bg: '#ECFDF5', text: '#065F46', label: 'Berjalan' },
            available:   { color: '#3B82F6', bg: '#EFF6FF', text: '#1E40AF', label: 'Tersedia' },
            maintenance: { color: '#F59E0B', bg: '#FFFBEB', text: '#92400E', label: 'Maintenance' },
            rented:      { color: '#3B82F6', bg: '#EFF6FF', text: '#1E40AF', label: 'Disewa' },
        };
        const cfg = statusConfig[status] || statusConfig.available;

        const jawaBounds = L.latLngBounds(L.latLng(-8.8, 105.0), L.latLng(-5.8, 115.0));

        const map = L.map('vehicle-map', {
            center: hasLoc ? [lat, lon] : [-7.2, 110.0],
            zoom:   hasLoc ? 17 : 7,      // zoom 17 = level jalan detail
            minZoom: hasLoc ? 14 : 7,
            maxZoom: 18,
            zoomControl: false,            // ← sembunyikan tombol +/–
            scrollWheelZoom: false,        // ← nonaktifkan scroll zoom
            dragging: false,               // ← peta statis, tidak bisa digeser
            doubleClickZoom: false,
            touchZoom: false,
            maxBounds: jawaBounds,
            maxBoundsViscosity: 1.0,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(map);

        if (hasLoc) {
            const pulse = status === 'ongoing' ? `
                <circle cx="18" cy="18" r="15" fill="none" stroke="${cfg.color}" stroke-width="2" opacity="0.5">
                    <animate attributeName="r" values="15;26;15" dur="1.8s" repeatCount="indefinite"/>
                    <animate attributeName="opacity" values="0.5;0;0.5" dur="1.8s" repeatCount="indefinite"/>
                </circle>` : '';
            const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 36 36">
                ${pulse}
                <circle cx="18" cy="18" r="14" fill="${cfg.color}" fill-opacity="0.2"/>
                <circle cx="18" cy="18" r="10" fill="${cfg.color}"/>
                <circle cx="18" cy="18" r="4" fill="white"/>
            </svg>`;
            const icon = L.divIcon({ html: svg, className: '', iconSize: [40,40], iconAnchor: [20,20], popupAnchor: [0,-24] });

            L.marker([lat, lon], { icon })
                .bindPopup(`<div class="pin-popup">
                    <div class="pin-popup-plate">${plate}</div>
                    <div class="pin-popup-sub">${label}</div>
                    <div class="pin-popup-sub" style="color:${cfg.text};font-weight:600">${cfg.label}</div>
                    <div class="pin-popup-sub">${lat.toFixed(6)}, ${lon.toFixed(6)}</div>
                </div>`, { maxWidth: 220 })
                .addTo(map)
                .openPopup();

            L.circle([lat, lon], {
                radius: 80,
                color: cfg.color,
                fillColor: cfg.color,
                fillOpacity: 0.08,
                weight: 1.5,
            }).addTo(map);
        }

        setTimeout(() => map.invalidateSize(), 300);
    });
    </script>
</x-app-layout>