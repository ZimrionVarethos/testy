{{--
    resources/views/dashboard.blade.php (admin section)
    Data dari DashboardController::adminDashboard()
--}}
<style>
    .db-section-label { font-family:'Epilogue',sans-serif; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.09em; color:var(--text-3); margin-bottom:10px; }

    .db-stat-card { background:var(--white); border:1px solid var(--border); border-radius:14px; padding:20px 20px 16px; cursor:pointer; position:relative; overflow:hidden; transition:border-color 0.15s, transform 0.15s, box-shadow 0.15s; }
    .db-stat-card:hover { border-color:var(--border-md); transform:translateY(-2px); box-shadow:0 6px 24px rgba(17,24,39,0.07); }
    .db-stat-card:active { transform:translateY(0); }
    .db-stat-arrow { position:absolute; top:14px; right:14px; width:22px; height:22px; border-radius:7px; background:var(--bg); display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity 0.15s; }
    .db-stat-card:hover .db-stat-arrow { opacity:1; }
    .db-stat-arrow svg { width:10px; height:10px; color:var(--text-2); }
    .db-stat-tag { display:inline-flex; align-items:center; gap:5px; font-size:10px; font-weight:600; padding:3px 8px; border-radius:6px; margin-bottom:14px; }
    .db-stat-tag svg { width:11px; height:11px; }
    .tag-indigo { background:#eef2ff; color:#4338ca; }
    .tag-amber  { background:var(--s-amber-bg); color:var(--s-amber-text); }
    .tag-green  { background:var(--s-green-bg); color:var(--s-green-text); }
    .tag-neutral{ background:rgba(17,24,39,0.06); color:var(--text-2); }
    .db-stat-value { font-family:'Epilogue',sans-serif; font-size:32px; font-weight:800; color:var(--text-1); letter-spacing:-1.2px; line-height:1; margin-bottom:5px; }
    .db-stat-value.mono-val { font-family:'DM Mono',monospace; font-size:20px; letter-spacing:-0.5px; }
    .db-stat-label { font-size:12px; color:var(--text-2); font-weight:500; }
    .db-stat-divider { height:1px; background:var(--border); margin:14px 0 10px; }
    .db-stat-footer { display:flex; align-items:center; justify-content:space-between; }
    .db-stat-footer-text { font-size:11px; color:var(--text-3); }
    .db-chip { font-size:10px; font-weight:700; padding:2px 7px; border-radius:5px; line-height:1.5; }
    .db-chip-green { background:var(--s-green-bg); color:var(--s-green-text); }
    .db-chip-amber { background:var(--s-amber-bg); color:var(--s-amber-text); }

    .db-card { background:var(--white); border:1px solid var(--border); border-radius:14px; overflow:hidden; }
    .db-card-header { padding:14px 18px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
    .db-card-title { font-family:'Epilogue',sans-serif; font-size:13px; font-weight:700; color:var(--text-1); }
    .db-card-sub { font-size:11px; color:var(--text-3); margin-top:1px; }
    .db-card-link { font-size:11px; font-weight:600; color:var(--text-3); text-decoration:none; transition:color 0.15s; }
    .db-card-link:hover { color:var(--text-1); }

    .db-badge { display:inline-flex; align-items:center; gap:5px; font-size:10px; font-weight:600; padding:3px 8px; border-radius:6px; }
    .db-badge::before { content:''; width:5px; height:5px; border-radius:50%; }
    .badge-pending   { background:var(--s-amber-bg);  color:var(--s-amber-text);  } .badge-pending::before  { background:var(--s-amber); }
    .badge-accepted  { background:var(--s-blue-bg);   color:var(--s-blue-text);   } .badge-accepted::before { background:var(--s-blue); }
    .badge-confirmed { background:var(--s-violet-bg); color:var(--s-violet-text); } .badge-confirmed::before{ background:var(--s-violet); }
    .badge-ongoing   { background:var(--s-green-bg);  color:var(--s-green-text);  } .badge-ongoing::before  { background:var(--s-green); }
    .badge-completed { background:var(--s-gray-bg);   color:var(--s-gray-text);   } .badge-completed::before{ background:var(--s-gray); }
    .badge-cancelled { background:var(--s-red-bg);    color:var(--s-red-text);    } .badge-cancelled::before{ background:var(--s-red); }

    .db-row { display:flex; align-items:center; justify-content:space-between; padding:11px 18px; border-bottom:1px solid var(--border); transition:background 0.1s; cursor:default; }
    .db-row:last-child { border-bottom:none; }
    .db-row:hover { background:var(--bg); }
    .db-row-icon { width:32px; height:32px; border-radius:8px; background:var(--bg); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .db-row-icon svg { width:14px; height:14px; color:var(--text-2); }
    .db-row-code { font-family:'DM Mono',monospace; font-size:12px; font-weight:500; color:var(--text-1); }
    .db-row-sub { font-size:11px; color:var(--text-3); margin-top:1px; }

    .db-fleet-grid { display:grid; grid-template-columns:130px 1fr; align-items:center; }
    .db-fleet-canvas-wrap { display:flex; align-items:center; justify-content:center; padding:20px; border-right:1px solid var(--border); }
    .db-fleet-legend { padding:14px 18px; }
    .db-fleet-row { display:flex; align-items:center; justify-content:space-between; padding:7px 0; border-bottom:1px solid var(--border); }
    .db-fleet-row:last-child { border-bottom:none; }
    .db-fleet-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
    .db-fleet-label { font-size:12px; color:var(--text-2); font-weight:500; }
    .db-fleet-val { font-family:'DM Mono',monospace; font-size:15px; font-weight:500; color:var(--text-1); }
    .db-util-wrap { margin-top:12px; padding-top:12px; border-top:1px solid var(--border); }
    .db-util-label { font-size:10px; color:var(--text-3); font-weight:600; text-transform:uppercase; letter-spacing:0.06em; }
    .db-util-val { font-family:'DM Mono',monospace; font-size:24px; font-weight:500; color:var(--text-1); margin:2px 0 6px; }
    .db-util-track { height:3px; background:var(--border); border-radius:99px; overflow:hidden; }
    .db-util-fill { height:100%; background:var(--dark); border-radius:99px; transition:width 0.6s ease; }

    .db-map-frame { padding:12px; }
    #db-map { height:360px; width:100%; border-radius:10px; border:1px solid var(--border); display:block; }
    .leaflet-container { font-family:'DM Sans',sans-serif; }
    .leaflet-popup-content-wrapper { border-radius:10px !important; box-shadow:0 8px 28px rgba(17,24,39,0.13) !important; border:1px solid var(--border) !important; padding:0 !important; }
    .leaflet-popup-content { margin:0 !important; }
    .leaflet-popup-tip-container { display:none; }
    .db-map-popup { padding:12px 15px; }
    .db-map-popup-plate { font-family:'DM Mono',monospace; font-size:13px; font-weight:500; color:var(--dark); }
    .db-map-popup-driver { font-size:11px; color:var(--text-3); margin:2px 0 7px; }
    .db-map-popup-time { font-size:10px; color:var(--text-3); margin-top:5px; }
    .db-map-legend { background:var(--white); border:1px solid var(--border); border-radius:10px; padding:9px 12px; }
    .db-map-legend-title { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:var(--text-3); margin-bottom:6px; }
    .db-map-legend-row { display:flex; align-items:center; gap:6px; font-size:11px; color:var(--text-2); margin-bottom:4px; font-weight:500; }
    .db-map-legend-row:last-child { margin-bottom:0; }
    .leg-dot { width:7px; height:7px; border-radius:50%; }
    .leaflet-control-zoom { border:none !important; box-shadow:0 2px 8px rgba(0,0,0,0.08) !important; }
    .leaflet-control-zoom a { border:1px solid var(--border) !important; color:var(--text-1) !important; width:28px !important; height:28px !important; line-height:27px !important; border-radius:8px !important; }

    .db-alert { background:var(--white); border:1px solid var(--border); border-radius:14px; overflow:hidden; }
    .db-alert-header { padding:12px 18px; background:var(--s-amber-bg); border-bottom:1px solid rgba(217,119,6,0.15); display:flex; align-items:center; gap:7px; }
    .db-alert-title { font-family:'Epilogue',sans-serif; font-size:12px; font-weight:700; color:var(--s-amber-text); flex:1; }
    .db-alert-count { background:var(--s-amber); color:white; font-size:10px; font-weight:700; font-family:'DM Mono',monospace; padding:1px 7px; border-radius:99px; }
    .btn-confirm { padding:5px 14px; background:var(--dark); color:white; border:none; border-radius:8px; font-size:11px; font-weight:600; cursor:pointer; font-family:'DM Sans',sans-serif; transition:opacity 0.15s; flex-shrink:0; }
    .btn-confirm:hover { opacity:0.75; }

    /* ── Responsive grids ── */
    .db-stat-grid    { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
    .db-charts-grid  { display:grid; grid-template-columns:1fr 360px; gap:12px; }
    .db-ops-grid     { display:grid; grid-template-columns:1fr 360px; gap:12px; }

    @media(max-width:1024px) {
        .db-charts-grid { grid-template-columns:1fr; }
        .db-ops-grid    { grid-template-columns:1fr; }
    }
    @media(max-width:768px) {
        .db-stat-grid { grid-template-columns:1fr 1fr; gap:8px; }
        .db-stat-value { font-size:26px; }
        .db-stat-value.mono-val { font-size:16px; }
        .db-stat-card { padding:14px 14px 12px; }
        #db-map { height:260px; }
        .db-fleet-grid { grid-template-columns:1fr; }
        .db-fleet-canvas-wrap { border-right:none; border-bottom:1px solid var(--border); }
    }
    @media(max-width:480px) {
        .db-stat-grid { grid-template-columns:1fr 1fr; gap:8px; }
    }
</style>

<div class="py-6">
<div style="max-width:1200px;margin:0 auto;padding:0 24px 48px;display:flex;flex-direction:column;gap:24px">

    {{-- GREETING --}}
    <div>
        <h2 style="font-family:'Epilogue',sans-serif;font-size:22px;font-weight:800;color:var(--text-1);letter-spacing:-0.5px">
            Selamat datang, {{ Auth::user()->name }} 👋
        </h2>
        <p style="font-size:13px;color:var(--text-2);margin-top:3px">
            {{ now()->locale('id')->isoFormat('dddd, D MMMM YYYY') }} · Ringkasan aktivitas hari ini
        </p>
    </div>

    {{-- STAT CARDS --}}
    <div>
        <p class="db-section-label">Ringkasan</p>
        <div class="db-stat-grid">
            <div class="db-stat-card" onclick="window.location='{{ route('admin.reports.index') }}'">
                <span class="db-stat-arrow"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 10L10 2M10 2H5M10 2v5"/></svg></span>
                <div class="db-stat-tag tag-indigo">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2H4a2 2 0 00-2 2v9a2 2 0 002 2h8a2 2 0 002-2V4a2 2 0 00-2-2h-2M6 2a1 1 0 011-1h2a1 1 0 011 1v1H6V2z"/></svg>
                    Pesanan
                </div>
                <div class="db-stat-value">{{ $stats['week_bookings'] ?? 0 }}</div>
                <div class="db-stat-label">Pesanan minggu ini</div>
                <div class="db-stat-divider"></div>
                <div class="db-stat-footer">
                    <span class="db-stat-footer-text">Total: {{ $stats['total_bookings'] ?? 0 }}</span>
                    <span class="db-chip db-chip-green">Aktif</span>
                </div>
            </div>
            <div class="db-stat-card" onclick="window.location='{{ route('admin.reports.index') }}'">
                <span class="db-stat-arrow"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 10L10 2M10 2H5M10 2v5"/></svg></span>
                <div class="db-stat-tag tag-amber">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="8" cy="8" r="6"/><polyline points="8 4 8 8 10.5 9.5"/></svg>
                    Pending
                </div>
                <div class="db-stat-value" style="color:var(--s-amber)">{{ $stats['pending_bookings'] ?? 0 }}</div>
                <div class="db-stat-label">Menunggu konfirmasi</div>
                <div class="db-stat-divider"></div>
                <div class="db-stat-footer">
                    <span class="db-stat-footer-text">Perlu tindakan</span>
                    @if(($stats['pending_bookings'] ?? 0) > 0)
                    <span class="db-chip db-chip-amber">Urgent</span>
                    @else
                    <span class="db-chip db-chip-green">Bersih</span>
                    @endif
                </div>
            </div>
            <div class="db-stat-card" onclick="window.location='{{ route('admin.reports.index') }}'">
                <span class="db-stat-arrow"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 10L10 2M10 2H5M10 2v5"/></svg></span>
                <div class="db-stat-tag tag-green">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 8l4 4 6-7"/></svg>
                    Aktif
                </div>
                <div class="db-stat-value" style="color:var(--s-green)">{{ $stats['ongoing_bookings'] ?? 0 }}</div>
                <div class="db-stat-label">Sedang berjalan</div>
                <div class="db-stat-divider"></div>
                <div class="db-stat-footer">
                    <span class="db-stat-footer-text">Aktif sekarang</span>
                    <span class="db-chip db-chip-green">Live</span>
                </div>
            </div>
            <div class="db-stat-card" onclick="window.location='{{ route('admin.reports.index') }}'">
                <span class="db-stat-arrow"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 10L10 2M10 2H5M10 2v5"/></svg></span>
                <div class="db-stat-tag tag-neutral">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="8" y1="1" x2="8" y2="15"/><path d="M11 4H6.5a2.5 2.5 0 000 5h3a2.5 2.5 0 010 5H4"/></svg>
                    Revenue
                </div>
                <div class="db-stat-value mono-val">Rp {{ number_format(($stats['week_revenue'] ?? 0) / 1000000, 1, ',', '.') }}jt</div>
                <div class="db-stat-label">Revenue minggu ini</div>
                <div class="db-stat-divider"></div>
                <div class="db-stat-footer">
                    <span class="db-stat-footer-text">{{ $stats['week_completed'] ?? 0 }} selesai</span>
                    <span class="db-chip db-chip-green">Detail →</span>
                </div>
            </div>
        </div>
    </div>

    {{-- CHARTS --}}
    <div>
        <p class="db-section-label">Analitik</p>
        <div class="db-charts-grid">
            <div class="db-card">
                <div class="db-card-header">
                    <div><div class="db-card-title">Tren Pesanan</div><div class="db-card-sub">7 hari terakhir</div></div>
                    <span style="font-size:10px;font-weight:600;background:rgba(17,24,39,0.06);color:var(--text-2);padding:3px 9px;border-radius:6px">Mingguan</span>
                </div>
                <div style="padding:16px"><canvas id="dbBookingChart" height="100"></canvas></div>
            </div>
            <div class="db-card">
                <div class="db-card-header">
                    <div><div class="db-card-title">Pendapatan</div><div class="db-card-sub">6 bulan terakhir</div></div>
                </div>
                <div style="padding:16px"><canvas id="dbRevenueChart" height="148"></canvas></div>
            </div>
        </div>
    </div>

    {{-- OPERASIONAL --}}
    <div>
        <p class="db-section-label">Operasional</p>
        <div class="db-ops-grid">
            <div class="db-card">
                <div class="db-card-header">
                    <div class="db-card-title">Pesanan Terbaru</div>
                    <a href="{{ route('admin.bookings.index') }}" class="db-card-link">Lihat semua →</a>
                </div>
                @forelse($recentBookings ?? [] as $booking)
                <div class="db-row">
                    <div style="display:flex;align-items:center;gap:10px">
                        <div class="db-row-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        </div>
                        <div>
                            <div class="db-row-code">{{ $booking->booking_code }}</div>
                            <div class="db-row-sub">{{ $booking->user['name'] ?? '-' }}</div>
                        </div>
                    </div>
                    <span class="db-badge badge-{{ $booking->status }}">{{ ucfirst($booking->status) }}</span>
                </div>
                @empty
                <div style="padding:32px 18px;text-align:center"><div style="font-size:12px;color:var(--text-3)">Belum ada pesanan</div></div>
                @endforelse
            </div>
            <div class="db-card">
                <div class="db-card-header">
                    <div class="db-card-title">Status Armada</div>
                    <a href="{{ route('admin.vehicles.index') }}" class="db-card-link">Kelola →</a>
                </div>
                <div class="db-fleet-grid">
                    <div class="db-fleet-canvas-wrap">
                        <canvas id="dbFleetDonut" width="100" height="100"></canvas>
                    </div>
                    <div class="db-fleet-legend">
                        <div class="db-fleet-row">
                            <div style="display:flex;align-items:center;gap:7px"><span class="db-fleet-dot" style="background:var(--s-green)"></span><span class="db-fleet-label">Tersedia</span></div>
                            <span class="db-fleet-val">{{ $vehicleStats['available'] ?? 0 }}</span>
                        </div>
                        <div class="db-fleet-row">
                            <div style="display:flex;align-items:center;gap:7px"><span class="db-fleet-dot" style="background:var(--s-blue)"></span><span class="db-fleet-label">Disewa</span></div>
                            <span class="db-fleet-val">{{ $vehicleStats['rented'] ?? 0 }}</span>
                        </div>
                        <div class="db-fleet-row">
                            <div style="display:flex;align-items:center;gap:7px"><span class="db-fleet-dot" style="background:var(--s-amber)"></span><span class="db-fleet-label">Maintenance</span></div>
                            <span class="db-fleet-val">{{ $vehicleStats['maintenance'] ?? 0 }}</span>
                        </div>
                        @php
                            $dbTotal   = ($vehicleStats['available'] ?? 0) + ($vehicleStats['rented'] ?? 0) + ($vehicleStats['maintenance'] ?? 0);
                            $dbUtilPct = $dbTotal > 0 ? round((($vehicleStats['rented'] ?? 0) / $dbTotal) * 100) : 0;
                        @endphp
                        <div class="db-util-wrap">
                            <div class="db-util-label">Utilisasi</div>
                            <div class="db-util-val">{{ $dbUtilPct }}%</div>
                            <div class="db-util-track"><div class="db-util-fill" style="width:{{ $dbUtilPct }}%"></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MAP --}}
    <div>
        <p class="db-section-label">Lokasi Real-time</p>
        <div class="db-card">
            <div class="db-card-header">
                <div style="display:flex;align-items:center;gap:7px">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--text-2)" stroke-width="1.8"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5"/></svg>
                    <div class="db-card-title">Lokasi Armada</div>
                </div>
                <a href="{{ route('admin.maps.index') }}" class="db-card-link">Peta detail →</a>
            </div>
            <div class="db-map-frame"><div id="db-map"></div></div>
        </div>
    </div>

    {{-- ALERT --}}
    @if(isset($acceptedBookings) && $acceptedBookings->count() > 0)
    <div>
        <p class="db-section-label">Perlu Tindakan</p>
        <div class="db-alert">
            <div class="db-alert-header">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--s-amber)" stroke-width="2"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                <span class="db-alert-title">Menunggu Konfirmasi Admin</span>
                <span class="db-alert-count">{{ $acceptedBookings->count() }}</span>
            </div>
            @foreach($acceptedBookings as $booking)
            <div class="db-row">
                <div style="display:flex;align-items:center;gap:10px">
                    <div class="db-row-icon" style="background:var(--s-amber-bg)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="var(--s-amber)" stroke-width="1.8" width="14" height="14"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    </div>
                    <div>
                        <div class="db-row-code">{{ $booking->booking_code }}</div>
                        <div class="db-row-sub">
                            Driver: <strong style="color:var(--text-2)">{{ $booking->driver['name'] ?? '-' }}</strong>
                            · User: <strong style="color:var(--text-2)">{{ $booking->user['name'] ?? '-' }}</strong>
                        </div>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.bookings.confirm', $booking->_id) }}" style="margin:0">
                    @csrf
                    <button type="submit" class="btn-confirm">Konfirmasi</button>
                </form>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
</div>

@php
$dbBookingTrend = $bookingTrend ?? [
    ['date'=>'Sen','total'=>8],['date'=>'Sel','total'=>14],['date'=>'Rab','total'=>11],
    ['date'=>'Kam','total'=>19],['date'=>'Jum','total'=>22],['date'=>'Sab','total'=>17],['date'=>'Min','total'=>9],
];
$dbRevenueData = $revenueChart ?? [
    ['month'=>'Okt','revenue'=>12000000],['month'=>'Nov','revenue'=>18500000],
    ['month'=>'Des','revenue'=>22000000],['month'=>'Jan','revenue'=>16000000],
    ['month'=>'Feb','revenue'=>25500000],['month'=>'Mar','revenue'=>28000000],
];
@endphp

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<link  rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>

<script>
Chart.defaults.font.family = "'DM Sans', sans-serif";
Chart.defaults.color = 'rgba(17,24,39,0.35)';

const dbBookingData = @json($dbBookingTrend);
const dbRevenueData = @json($dbRevenueData);

(function(){
    const ctx  = document.getElementById('dbBookingChart').getContext('2d');
    const grad = ctx.createLinearGradient(0,0,0,180);
    grad.addColorStop(0,'rgba(17,24,39,0.08)');
    grad.addColorStop(1,'rgba(17,24,39,0)');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dbBookingData.map(d=>d.date),
            datasets: [{ data: dbBookingData.map(d=>d.total), borderColor:'rgb(17,24,39)', backgroundColor:grad, borderWidth:2, fill:true, tension:0.4, pointBackgroundColor:'rgb(17,24,39)', pointBorderColor:'#fff', pointBorderWidth:2, pointRadius:3.5, pointHoverRadius:5 }]
        },
        options: { responsive:true, maintainAspectRatio:true, plugins:{ legend:{display:false}, tooltip:{ backgroundColor:'rgb(17,24,39)', titleFont:{size:11}, bodyFont:{size:11}, padding:10, cornerRadius:8, callbacks:{label:c=>`  ${c.raw} pesanan`} } }, scales:{ y:{beginAtZero:true,grid:{color:'rgba(17,24,39,0.05)'},ticks:{font:{size:10}}}, x:{grid:{display:false},ticks:{font:{size:10}}} } }
    });
})();

(function(){
    const ctx = document.getElementById('dbRevenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: { labels:dbRevenueData.map(d=>d.month), datasets:[{ data:dbRevenueData.map(d=>d.revenue), backgroundColor:'rgba(17,24,39,0.1)', hoverBackgroundColor:'rgba(17,24,39,0.7)', borderRadius:5, borderSkipped:false }] },
        options: { responsive:true, maintainAspectRatio:true, plugins:{ legend:{display:false}, tooltip:{ backgroundColor:'rgb(17,24,39)', bodyFont:{family:"'DM Mono', monospace",size:11}, padding:10, cornerRadius:8, callbacks:{label:c=>`  Rp ${c.raw.toLocaleString('id-ID')}`} } }, scales:{ y:{beginAtZero:true,grid:{color:'rgba(17,24,39,0.05)'},ticks:{font:{size:10},callback:v=>'Rp'+(v/1000000).toFixed(0)+'jt'}}, x:{grid:{display:false},ticks:{font:{size:10}}} } }
    });
})();

(function(){
    const ctx = document.getElementById('dbFleetDonut').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: { labels:['Tersedia','Disewa','Maintenance'], datasets:[{ data:[{{ $vehicleStats['available'] ?? 0 }},{{ $vehicleStats['rented'] ?? 0 }},{{ $vehicleStats['maintenance'] ?? 0 }}], backgroundColor:['#16a34a','#2563eb','#d97706'], borderWidth:0, hoverOffset:4 }] },
        options: { responsive:false, cutout:'72%', plugins:{ legend:{display:false}, tooltip:{backgroundColor:'rgb(17,24,39)',bodyFont:{size:12},padding:10,cornerRadius:8} } }
    });
})();

document.addEventListener('DOMContentLoaded', function() {
    const vehicles = @json(isset($vehicleLocations) ? $vehicleLocations : []);

    const statusCfg = {
        ongoing:     { color:'#16a34a', bg:'#f0fdf4', text:'#14532d', label:'Berjalan',   badgeClass:'badge-ongoing'  },
        available:   { color:'#2563eb', bg:'#eff6ff', text:'#1e3a8a', label:'Tersedia',   badgeClass:'badge-accepted' },
        rented:      { color:'#2563eb', bg:'#eff6ff', text:'#1e3a8a', label:'Disewa',     badgeClass:'badge-accepted' },
        maintenance: { color:'#d97706', bg:'#fffbeb', text:'#78350f', label:'Maintenance',badgeClass:'badge-pending'  },
    };

    const map = L.map('db-map', {
        center:[-2.5,118], zoom:5, minZoom:4, maxZoom:14,
        zoomControl:true, scrollWheelZoom:false,
        maxBounds:[[-15,90],[10,145]], maxBoundsViscosity:0.9,
    });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'© <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>', maxZoom:19 }).addTo(map);

    // ── Pin teardrop (konsisten dengan maps.index) ──
    function makeIcon(status) {
        const c = statusCfg[status] || statusCfg.available;
        const pulse = status === 'ongoing' ? `
            <circle cx="22" cy="19" r="17" fill="none" stroke="${c.color}" stroke-width="1.5" opacity="0.35">
                <animate attributeName="r" values="17;27;17" dur="2.2s" repeatCount="indefinite"/>
                <animate attributeName="opacity" values="0.35;0;0.35" dur="2.2s" repeatCount="indefinite"/>
            </circle>` : '';
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="44" height="56" viewBox="0 0 44 56">
            ${pulse}
            <path d="M22 5 C13.716 5 7 11.716 7 20 C7 31 22 51 22 51 C22 51 37 31 37 20 C37 11.716 30.284 5 22 5 Z"
                  fill="${c.color}" stroke="white" stroke-width="1.5"/>
            <circle cx="22" cy="19" r="6.5" fill="white" fill-opacity="0.95"/>
        </svg>`;
        return L.divIcon({ html:svg, className:'', iconSize:[44,56], iconAnchor:[22,51], popupAnchor:[0,-54] });
    }

    vehicles.forEach(v => {
        if (!v.lat || !v.lon) return;
        const c = statusCfg[v.status] || statusCfg.available;
        const updatedNote = v.location_updated_at
            ? `<div class="db-map-popup-time">Update: ${v.location_updated_at}</div>` : '';
        L.marker([v.lat, v.lon], { icon: makeIcon(v.status) })
            .bindPopup(`<div class="db-map-popup">
                <div class="db-map-popup-plate">${v.plate}</div>
                <div class="db-map-popup-driver">Driver: ${v.driver || '-'}</div>
                <span class="db-badge ${c.badgeClass}">${c.label}</span>
                ${updatedNote}
            </div>`, { maxWidth:200, className:'' })
            .addTo(map);
    });

    const legend = L.control({ position:'bottomleft' });
    legend.onAdd = () => {
        const d = L.DomUtil.create('div','db-map-legend');
        d.innerHTML = `<div class="db-map-legend-title">Legenda</div>
            <div class="db-map-legend-row"><span class="leg-dot" style="background:#16a34a"></span>Berjalan</div>
            <div class="db-map-legend-row"><span class="leg-dot" style="background:#2563eb"></span>Tersedia / Disewa</div>
            <div class="db-map-legend-row"><span class="leg-dot" style="background:#d97706"></span>Maintenance</div>`;
        return d;
    };
    legend.addTo(map);
    setTimeout(() => map.invalidateSize(), 400);
});
</script>
@endpush