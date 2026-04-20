{{--
    resources/views/admin/dashboard.blade.php
    (atau dashboard.blade.php jika route ke view('dashboard', ...))

    Data yang dibutuhkan dari DashboardController::adminDashboard():
    - $stats             → array: total_bookings, pending_bookings, ongoing_bookings, monthly_revenue
    - $vehicleStats      → array: available, rented, maintenance
    - $recentBookings    → Collection Booking (limit 5)
    - $acceptedBookings  → Collection Booking (status = accepted)
    - $vehicleLocations  → array of {id, plate, driver, status, lat, lon, location_updated_at}
    - $bookingTrend      → array of {date, total}  ← TAMBAHKAN ke controller (lihat komentar bawah)
    - $revenueChart      → array of {month, revenue} ← TAMBAHKAN ke controller (lihat komentar bawah)
--}}

<style>
    /* ── Dashboard Variables (extends app.blade.php tokens) ── */
    .db-section-label {
        font-family: 'Epilogue', sans-serif;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.09em;
        color: var(--text-3);
        margin-bottom: 10px;
    }

    /* ── Stat Cards ── */
    .db-stat-card {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 20px 20px 16px;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        transition: border-color 0.15s, transform 0.15s, box-shadow 0.15s;
    }
    .db-stat-card:hover {
        border-color: var(--border-md);
        transform: translateY(-2px);
        box-shadow: 0 6px 24px rgba(17,24,39,0.07);
    }
    .db-stat-card:active { transform: translateY(0); }

    .db-stat-arrow {
        position: absolute; top: 14px; right: 14px;
        width: 22px; height: 22px;
        border-radius: 7px;
        background: var(--bg);
        display: flex; align-items: center; justify-content: center;
        opacity: 0;
        transition: opacity 0.15s;
    }
    .db-stat-card:hover .db-stat-arrow { opacity: 1; }
    .db-stat-arrow svg { width: 10px; height: 10px; color: var(--text-2); }

    .db-stat-tag {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 10px; font-weight: 600;
        padding: 3px 8px; border-radius: 6px;
        margin-bottom: 14px;
    }
    .db-stat-tag svg { width: 11px; height: 11px; }
    .tag-indigo { background: #eef2ff; color: #4338ca; }
    .tag-amber  { background: var(--s-amber-bg); color: var(--s-amber-text); }
    .tag-green  { background: var(--s-green-bg); color: var(--s-green-text); }
    .tag-neutral{ background: rgba(17,24,39,0.06); color: var(--text-2); }

    .db-stat-value {
        font-family: 'Epilogue', sans-serif;
        font-size: 32px; font-weight: 800;
        color: var(--text-1);
        letter-spacing: -1.2px;
        line-height: 1;
        margin-bottom: 5px;
    }
    .db-stat-value.mono-val {
        font-family: 'DM Mono', monospace;
        font-size: 20px;
        letter-spacing: -0.5px;
    }
    .db-stat-label { font-size: 12px; color: var(--text-2); font-weight: 500; }
    .db-stat-divider { height: 1px; background: var(--border); margin: 14px 0 10px; }
    .db-stat-footer { display: flex; align-items: center; justify-content: space-between; }
    .db-stat-footer-text { font-size: 11px; color: var(--text-3); }
    .db-chip {
        font-size: 10px; font-weight: 700;
        padding: 2px 7px; border-radius: 5px;
        line-height: 1.5;
    }
    .db-chip-green { background: var(--s-green-bg); color: var(--s-green-text); }
    .db-chip-amber { background: var(--s-amber-bg); color: var(--s-amber-text); }

    /* ── Section Card ── */
    .db-card {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 14px;
        overflow: hidden;
    }
    .db-card-header {
        padding: 14px 18px;
        border-bottom: 1px solid var(--border);
        display: flex; align-items: center; justify-content: space-between;
    }
    .db-card-title {
        font-family: 'Epilogue', sans-serif;
        font-size: 13px; font-weight: 700;
        color: var(--text-1);
    }
    .db-card-sub { font-size: 11px; color: var(--text-3); margin-top: 1px; }
    .db-card-link {
        font-size: 11px; font-weight: 600;
        color: var(--text-3); text-decoration: none;
        transition: color 0.15s;
    }
    .db-card-link:hover { color: var(--text-1); }

    /* ── Badge ── */
    .db-badge {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 10px; font-weight: 600;
        padding: 3px 8px; border-radius: 6px;
    }
    .db-badge::before { content:''; width:5px; height:5px; border-radius:50%; }
    .badge-pending   { background:var(--s-amber-bg);  color:var(--s-amber-text);  } .badge-pending::before  { background:var(--s-amber); }
    .badge-accepted  { background:var(--s-blue-bg);   color:var(--s-blue-text);   } .badge-accepted::before { background:var(--s-blue); }
    .badge-confirmed { background:var(--s-violet-bg); color:var(--s-violet-text); } .badge-confirmed::before{ background:var(--s-violet); }
    .badge-ongoing   { background:var(--s-green-bg);  color:var(--s-green-text);  } .badge-ongoing::before  { background:var(--s-green); }
    .badge-completed { background:var(--s-gray-bg);   color:var(--s-gray-text);   } .badge-completed::before{ background:var(--s-gray); }
    .badge-cancelled { background:var(--s-red-bg);    color:var(--s-red-text);    } .badge-cancelled::before{ background:var(--s-red); }

    /* ── Table row ── */
    .db-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 11px 18px;
        border-bottom: 1px solid var(--border);
        transition: background 0.1s;
        cursor: default;
    }
    .db-row:last-child { border-bottom: none; }
    .db-row:hover { background: var(--bg); }
    .db-row-icon {
        width: 32px; height: 32px;
        border-radius: 8px;
        background: var(--bg);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .db-row-icon svg { width: 14px; height: 14px; color: var(--text-2); }
    .db-row-code {
        font-family: 'DM Mono', monospace;
        font-size: 12px; font-weight: 500;
        color: var(--text-1);
    }
    .db-row-sub { font-size: 11px; color: var(--text-3); margin-top: 1px; }

    /* ── Fleet card ── */
    .db-fleet-grid {
        display: grid;
        grid-template-columns: 130px 1fr;
        align-items: center;
    }
    .db-fleet-canvas-wrap {
        display: flex; align-items: center; justify-content: center;
        padding: 20px;
        border-right: 1px solid var(--border);
    }
    .db-fleet-legend { padding: 14px 18px; }
    .db-fleet-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 7px 0;
        border-bottom: 1px solid var(--border);
    }
    .db-fleet-row:last-child { border-bottom: none; }
    .db-fleet-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
    .db-fleet-label { font-size: 12px; color: var(--text-2); font-weight: 500; }
    .db-fleet-val {
        font-family: 'DM Mono', monospace;
        font-size: 15px; font-weight: 500;
        color: var(--text-1);
    }
    .db-util-wrap { margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border); }
    .db-util-label { font-size: 10px; color: var(--text-3); font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; }
    .db-util-val {
        font-family: 'DM Mono', monospace;
        font-size: 24px; font-weight: 500;
        color: var(--text-1);
        margin: 2px 0 6px;
    }
    .db-util-track {
        height: 3px; background: var(--border); border-radius: 99px; overflow: hidden;
    }
    .db-util-fill { height: 100%; background: var(--dark); border-radius: 99px; transition: width 0.6s ease; }

    /* ── Map ── */
    #db-map {
        height: 380px; width: 100%;
        border-radius: 0 0 14px 14px;
    }
    .leaflet-container { font-family: 'DM Sans', sans-serif; }
    .leaflet-popup-content-wrapper {
        border-radius: 10px !important;
        box-shadow: 0 8px 28px rgba(17,24,39,0.13) !important;
        border: 1px solid var(--border) !important;
        padding: 0 !important;
    }
    .leaflet-popup-content { margin: 0 !important; }
    .leaflet-popup-tip-container { display: none; }
    .db-map-popup { padding: 12px 15px; }
    .db-map-popup-plate { font-family: 'DM Mono', monospace; font-size: 13px; font-weight: 500; color: var(--dark); }
    .db-map-popup-driver { font-size: 11px; color: var(--text-3); margin: 2px 0 7px; }
    .db-map-legend {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 9px 12px;
    }
    .db-map-legend-title { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: var(--text-3); margin-bottom: 6px; }
    .db-map-legend-row { display: flex; align-items: center; gap: 6px; font-size: 11px; color: var(--text-2); margin-bottom: 4px; font-weight: 500; }
    .db-map-legend-row:last-child { margin-bottom: 0; }
    .leg-dot { width: 7px; height: 7px; border-radius: 50%; }
    .leaflet-control-zoom { border: none !important; box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important; }
    .leaflet-control-zoom a { border: 1px solid var(--border) !important; color: var(--text-1) !important; width: 28px !important; height: 28px !important; line-height: 27px !important; border-radius: 8px !important; }

    /* ── Alert banner ── */
    .db-alert {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 14px;
        overflow: hidden;
    }
    .db-alert-header {
        padding: 12px 18px;
        background: var(--s-amber-bg);
        border-bottom: 1px solid rgba(217,119,6,0.15);
        display: flex; align-items: center; gap: 7px;
    }
    .db-alert-title {
        font-family: 'Epilogue', sans-serif;
        font-size: 12px; font-weight: 700;
        color: var(--s-amber-text);
        flex: 1;
    }
    .db-alert-count {
        background: var(--s-amber); color: white;
        font-size: 10px; font-weight: 700; font-family: 'DM Mono', monospace;
        padding: 1px 7px; border-radius: 99px;
    }
    .btn-confirm {
        padding: 5px 14px;
        background: var(--dark); color: white;
        border: none; border-radius: 8px;
        font-size: 11px; font-weight: 600;
        cursor: pointer;
        font-family: 'DM Sans', sans-serif;
        transition: opacity 0.15s;
        flex-shrink: 0;
    }
    .btn-confirm:hover { opacity: 0.75; }

    /* ── Modal ── */
    @media (max-width: 768px) {
        #db-map { height: 260px; }
        .db-fleet-grid { grid-template-columns: 1fr; }
        .db-fleet-canvas-wrap { border-right: none; border-bottom: 1px solid var(--border); }
    }
</style>

<div class="py-6">
<div style="max-width:1200px;margin:0 auto;padding:0 24px 48px;display:flex;flex-direction:column;gap:24px">

    {{-- ══ GREETING ══ --}}
    <div>
        <h2 style="font-family:'Epilogue',sans-serif;font-size:22px;font-weight:800;color:var(--text-1);letter-spacing:-0.5px">
            Selamat datang, {{ Auth::user()->name }} 👋
        </h2>
        <p style="font-size:13px;color:var(--text-2);margin-top:3px">
            {{ now()->locale('id')->isoFormat('dddd, D MMMM YYYY') }} · Ringkasan aktivitas hari ini
        </p>
    </div>

    {{-- ══ STAT CARDS ══ --}}
    <div>
        <p class="db-section-label">Ringkasan</p>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px">

            {{-- Total Pesanan --}}
            <div class="db-stat-card" onclick="window.location='{{ route('admin.reports.index') }}'" style="cursor:pointer">
                <span class="db-stat-arrow">
                    <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 10L10 2M10 2H5M10 2v5"/></svg>
                </span>
                <div class="db-stat-tag tag-indigo">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2H4a2 2 0 00-2 2v9a2 2 0 002 2h8a2 2 0 002-2V4a2 2 0 00-2-2h-2M6 2a1 1 0 011-1h2a1 1 0 011 1v1H6V2z"/></svg>
                    Pesanan
                </div>
                <div class="db-stat-value">{{ $stats['total_bookings'] ?? 0 }}</div>
                <div class="db-stat-label">Total semua waktu</div>
                <div class="db-stat-divider"></div>
                <div class="db-stat-footer">
                    <span class="db-stat-footer-text">Klik untuk detail</span>
                    <span class="db-chip db-chip-green">Aktif</span>
                </div>
            </div>

            {{-- Pending / Menunggu --}}
            <div class="db-stat-card" onclick="window.location='{{ route('admin.reports.index') }}'" style="cursor:pointer">
                <span class="db-stat-arrow">
                    <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 10L10 2M10 2H5M10 2v5"/></svg>
                </span>
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

            {{-- Ongoing --}}
            <div class="db-stat-card" onclick="window.location='{{ route('admin.reports.index') }}'" style="cursor:pointer">
                <span class="db-stat-arrow">
                    <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 10L10 2M10 2H5M10 2v5"/></svg>
                </span>
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

            {{-- Revenue --}}
            <div class="db-stat-card" onclick="window.location='{{ route('admin.reports.index') }}'" style="cursor:pointer">
                <span class="db-stat-arrow">
                    <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 10L10 2M10 2H5M10 2v5"/></svg>
                </span>
                <div class="db-stat-tag tag-neutral">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="8" y1="1" x2="8" y2="15"/><path d="M11 4H6.5a2.5 2.5 0 000 5h3a2.5 2.5 0 010 5H4"/></svg>
                    Revenue
                </div>
                <div class="db-stat-value mono-val">
                    Rp {{ number_format(($stats['monthly_revenue'] ?? 0) / 1000000, 1, ',', '.') }}jt
                </div>
                <div class="db-stat-label">Pendapatan bulan ini</div>
                <div class="db-stat-divider"></div>
                <div class="db-stat-footer">
                    <span class="db-stat-footer-text">{{ now()->locale('id')->isoFormat('MMMM YYYY') }}</span>
                    <span class="db-chip db-chip-green">Detail →</span>
                </div>
            </div>

        </div>
    </div>

    {{-- ══ CHARTS ROW ══ --}}
    <div>
        <p class="db-section-label">Analitik</p>
        <div style="display:grid;grid-template-columns:1fr 360px;gap:12px">

            <div class="db-card">
                <div class="db-card-header">
                    <div>
                        <div class="db-card-title">Tren Pesanan</div>
                        <div class="db-card-sub">7 hari terakhir</div>
                    </div>
                    <span style="font-size:10px;font-weight:600;background:rgba(17,24,39,0.06);color:var(--text-2);padding:3px 9px;border-radius:6px">Mingguan</span>
                </div>
                <div style="padding:16px">
                    <canvas id="dbBookingChart" height="100"></canvas>
                </div>
            </div>

            <div class="db-card">
                <div class="db-card-header">
                    <div>
                        <div class="db-card-title">Pendapatan</div>
                        <div class="db-card-sub">6 bulan terakhir</div>
                    </div>
                </div>
                <div style="padding:16px">
                    <canvas id="dbRevenueChart" height="148"></canvas>
                </div>
            </div>

        </div>
    </div>

    {{-- ══ BOOKINGS + FLEET ══ --}}
    <div>
        <p class="db-section-label">Operasional</p>
        <div style="display:grid;grid-template-columns:1fr 360px;gap:12px">

            {{-- Pesanan Terbaru --}}
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
                <div style="padding:32px 18px;text-align:center">
                    <div style="font-size:12px;color:var(--text-3)">Belum ada pesanan</div>
                </div>
                @endforelse
            </div>

            {{-- Status Armada --}}
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
                            <div style="display:flex;align-items:center;gap:7px">
                                <span class="db-fleet-dot" style="background:var(--s-green)"></span>
                                <span class="db-fleet-label">Tersedia</span>
                            </div>
                            <span class="db-fleet-val">{{ $vehicleStats['available'] ?? 0 }}</span>
                        </div>
                        <div class="db-fleet-row">
                            <div style="display:flex;align-items:center;gap:7px">
                                <span class="db-fleet-dot" style="background:var(--s-blue)"></span>
                                <span class="db-fleet-label">Disewa</span>
                            </div>
                            <span class="db-fleet-val">{{ $vehicleStats['rented'] ?? 0 }}</span>
                        </div>
                        <div class="db-fleet-row">
                            <div style="display:flex;align-items:center;gap:7px">
                                <span class="db-fleet-dot" style="background:var(--s-amber)"></span>
                                <span class="db-fleet-label">Maintenance</span>
                            </div>
                            <span class="db-fleet-val">{{ $vehicleStats['maintenance'] ?? 0 }}</span>
                        </div>
                        @php
                            $dbTotal    = ($vehicleStats['available'] ?? 0) + ($vehicleStats['rented'] ?? 0) + ($vehicleStats['maintenance'] ?? 0);
                            $dbUtilPct  = $dbTotal > 0 ? round((($vehicleStats['rented'] ?? 0) / $dbTotal) * 100) : 0;
                        @endphp
                        <div class="db-util-wrap">
                            <div class="db-util-label">Utilisasi</div>
                            <div class="db-util-val">{{ $dbUtilPct }}%</div>
                            <div class="db-util-track">
                                <div class="db-util-fill" style="width:{{ $dbUtilPct }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ══ MAP ══ --}}
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
            <div id="db-map"></div>
        </div>
    </div>

    {{-- ══ ALERT: Perlu Konfirmasi ══ --}}
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

</div>

{{-- ══ SCRIPTS ══ --}}
@php
/* ── Default fallback data (ganti dengan data nyata dari controller) ──
 *
 * Tambahkan ke adminDashboard() di DashboardController:
 *
 * $bookingTrend = Booking::selectRaw("DATE_FORMAT(created_at, '%a') as date, COUNT(*) as total")
 *     ->where('created_at', '>=', now()->subDays(6)->startOfDay())
 *     ->groupBy('date')
 *     ->orderBy('created_at')
 *     ->get()->toArray();
 *
 * $revenueChart = Booking::whereIn('status', ['confirmed','ongoing','completed'])
 *     ->where('confirmed_at', '>=', now()->subMonths(5)->startOfMonth())
 *     ->selectRaw("DATE_FORMAT(confirmed_at, '%b') as month, SUM(total_price) as revenue")
 *     ->groupBy('month')
 *     ->orderBy('confirmed_at')
 *     ->get()->toArray();
 */
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
/* ── Chart defaults ── */
Chart.defaults.font.family = "'DM Sans', sans-serif";
Chart.defaults.color = 'rgba(17,24,39,0.35)';

const dbBookingData  = @json($dbBookingTrend);
const dbRevenueData  = @json($dbRevenueData);

/* ── Booking Trend (line) ── */
(function(){
    const ctx = document.getElementById('dbBookingChart').getContext('2d');
    const grad = ctx.createLinearGradient(0,0,0,180);
    grad.addColorStop(0,'rgba(17,24,39,0.08)');
    grad.addColorStop(1,'rgba(17,24,39,0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dbBookingData.map(d=>d.date),
            datasets: [{
                data: dbBookingData.map(d=>d.total),
                borderColor: 'rgb(17,24,39)',
                backgroundColor: grad,
                borderWidth: 2,
                fill: true, tension: 0.4,
                pointBackgroundColor: 'rgb(17,24,39)',
                pointBorderColor: '#fff', pointBorderWidth: 2,
                pointRadius: 3.5, pointHoverRadius: 5,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgb(17,24,39)',
                    titleFont: { size: 11 }, bodyFont: { size: 11 },
                    padding: 10, cornerRadius: 8,
                    callbacks: { label: c => `  ${c.raw} pesanan` }
                }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(17,24,39,0.05)' }, ticks: { font: { size: 10 } } },
                x: { grid: { display: false }, ticks: { font: { size: 10 } } }
            }
        }
    });
})();

/* ── Revenue Bar ── */
(function(){
    const ctx = document.getElementById('dbRevenueChart').getContext('2d');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dbRevenueData.map(d=>d.month),
            datasets: [{
                data: dbRevenueData.map(d=>d.revenue),
                backgroundColor: 'rgba(17,24,39,0.1)',
                hoverBackgroundColor: 'rgba(17,24,39,0.7)',
                borderRadius: 5, borderSkipped: false,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgb(17,24,39)',
                    bodyFont: { family: "'DM Mono', monospace", size: 11 },
                    padding: 10, cornerRadius: 8,
                    callbacks: { label: c => `  Rp ${c.raw.toLocaleString('id-ID')}` }
                }
            },
            scales: {
                y: {
                    beginAtZero: true, grid: { color: 'rgba(17,24,39,0.05)' },
                    ticks: { font: { size: 10 }, callback: v => 'Rp'+(v/1000000).toFixed(0)+'jt' }
                },
                x: { grid: { display: false }, ticks: { font: { size: 10 } } }
            }
        }
    });
})();

/* ── Fleet Donut ── */
(function(){
    const ctx = document.getElementById('dbFleetDonut').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Tersedia','Disewa','Maintenance'],
            datasets: [{
                data: [
                    {{ $vehicleStats['available'] ?? 0 }},
                    {{ $vehicleStats['rented'] ?? 0 }},
                    {{ $vehicleStats['maintenance'] ?? 0 }}
                ],
                backgroundColor: ['#16a34a','#2563eb','#d97706'],
                borderWidth: 0, hoverOffset: 4,
            }]
        },
        options: {
            responsive: false, cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgb(17,24,39)',
                    bodyFont: { size: 12 }, padding: 10, cornerRadius: 8
                }
            }
        }
    });
})();

/* ── Leaflet Map ── */
document.addEventListener('DOMContentLoaded', function() {
    const vehicles = @json(isset($vehicleLocations) ? $vehicleLocations : []);

    const statusCfg = {
        ongoing:     { color:'#16a34a', bg:'#f0fdf4', text:'#14532d', label:'Berjalan',    badgeClass:'badge-ongoing'   },
        available:   { color:'#2563eb', bg:'#eff6ff', text:'#1e3a8a', label:'Tersedia',    badgeClass:'badge-accepted'  },
        rented:      { color:'#2563eb', bg:'#eff6ff', text:'#1e3a8a', label:'Disewa',      badgeClass:'badge-accepted'  },
        maintenance: { color:'#d97706', bg:'#fffbeb', text:'#78350f', label:'Maintenance', badgeClass:'badge-pending'   },
    };

    const map = L.map('db-map', {
        center: [-2.5, 118], zoom: 5, minZoom: 4, maxZoom: 14,
        zoomControl: true, scrollWheelZoom: false,
        maxBounds: [[-15, 90], [10, 145]], maxBoundsViscosity: 0.9,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);

    function makeIcon(status) {
        const c = statusCfg[status] || statusCfg.available;
        const pulse = status === 'ongoing' ? `
            <circle cx="18" cy="18" r="14" fill="none" stroke="${c.color}" stroke-width="1.5" opacity="0.4">
                <animate attributeName="r" values="14;22;14" dur="2s" repeatCount="indefinite"/>
                <animate attributeName="opacity" values="0.4;0;0.4" dur="2s" repeatCount="indefinite"/>
            </circle>` : '';
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36">
            ${pulse}
            <circle cx="18" cy="18" r="12" fill="${c.color}" fill-opacity="0.15"/>
            <circle cx="18" cy="18" r="8" fill="${c.color}"/>
            <circle cx="18" cy="18" r="3" fill="white"/>
        </svg>`;
        return L.divIcon({ html: svg, className: '', iconSize: [36,36], iconAnchor: [18,18], popupAnchor: [0,-20] });
    }

    vehicles.forEach(v => {
        if (!v.lat || !v.lon) return;
        const c = statusCfg[v.status] || statusCfg.available;
        const updatedNote = v.location_updated_at
            ? `<div style="font-size:10px;color:var(--text-3);margin-top:5px">Update: ${v.location_updated_at}</div>` : '';
        L.marker([v.lat, v.lon], { icon: makeIcon(v.status) })
            .bindPopup(`<div class="db-map-popup">
                <div class="db-map-popup-plate">${v.plate}</div>
                <div class="db-map-popup-driver">Driver: ${v.driver || '-'}</div>
                <span class="db-badge ${c.badgeClass}">${c.label}</span>
                ${updatedNote}
            </div>`, { maxWidth: 200, className: '' })
            .addTo(map);
    });

    const legend = L.control({ position: 'bottomleft' });
    legend.onAdd = () => {
        const d = L.DomUtil.create('div', 'db-map-legend');
        d.innerHTML = `<div class="db-map-legend-title">Legenda</div>
            <div class="db-map-legend-row"><span class="leg-dot" style="background:#16a34a"></span>Berjalan</div>
            <div class="db-map-legend-row"><span class="leg-dot" style="background:#2563eb"></span>Tersedia</div>
            <div class="db-map-legend-row"><span class="leg-dot" style="background:#d97706"></span>Maintenance</div>`;
        return d;
    };
    legend.addTo(map);
    setTimeout(() => map.invalidateSize(), 400);
});

</script>
@endpush