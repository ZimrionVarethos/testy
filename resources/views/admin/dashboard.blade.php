{{--
    resources/views/admin/dashboard.blade.php
    Data dari DashboardController::adminDashboard()

    PERUBAHAN dari versi lama:
    - Hapus blok "Menunggu Konfirmasi Admin" yang pakai $acceptedBookings (status lama)
    - Ganti dengan $pendingPaidBookings: pesanan pending yang sudah dibayar, siap di-assign driver
    - Badge status "accepted" dihapus — tidak ada lagi di alur baru
--}}
@push('head-scripts')
<link rel="stylesheet" href="{{ asset('css/admin/dashboard.css') }}">
@endpush

<div class="py-6">
<div style="max-width:1200px;margin:0 auto;padding:0 24px 48px;display:flex;flex-direction:column;gap:24px">

    {{-- GREETING --}}
    <div>
        <h2 style="font-family:'Epilogue',sans-serif;font-size:22px;font-weight:800;color:var(--text-1);letter-spacing:-0.5px">
            Selamat datang, {{ Auth::user()->name }}
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
                <div class="db-stat-tag tag-blue">
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
            <div class="db-stat-card" onclick="window.location='{{ route('admin.bookings.index') }}?status=pending'">
                <span class="db-stat-arrow"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 10L10 2M10 2H5M10 2v5"/></svg></span>
                <div class="db-stat-tag tag-amber">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="8" cy="8" r="6"/><polyline points="8 4 8 8 10.5 9.5"/></svg>
                    Perlu Diproses
                </div>
                {{-- Hanya pending yang sudah bayar yang perlu tindakan admin --}}
                <div class="db-stat-value" style="color:var(--s-amber)">{{ $stats['pending_paid'] ?? 0 }}</div>
                <div class="db-stat-label">Sudah bayar, belum di-assign</div>
                <div class="db-stat-divider"></div>
                <div class="db-stat-footer">
                    <span class="db-stat-footer-text">Perlu assign driver</span>
                    @if(($stats['pending_paid'] ?? 0) > 0)
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
                    <span class="db-chip db-chip-green">Detail</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ALERT: Pesanan sudah bayar, siap di-assign driver --}}
    @if(isset($pendingPaidBookings) && $pendingPaidBookings->count() > 0)
    <div>
        <p class="db-section-label">Perlu Tindakan</p>
        <div class="db-alert">
            <div class="db-alert-header">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--s-amber)" stroke-width="2"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                <span class="db-alert-title">Pesanan Sudah Dibayar — Assign Driver</span>
                <span class="db-alert-count">{{ $pendingPaidBookings->count() }}</span>
            </div>
            @foreach($pendingPaidBookings as $booking)
            <div class="db-row">
                <div style="display:flex;align-items:center;gap:10px">
                    <div class="db-row-icon" style="background:var(--s-amber-bg)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="var(--s-amber)" stroke-width="1.8" width="14" height="14"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    </div>
                    <div>
                        <div class="db-row-code">{{ $booking->booking_code }}</div>
                        <div class="db-row-sub">
                            {{ $booking->user['name'] ?? '-' }} ·
                            {{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y') }}
                        </div>
                    </div>
                </div>
                {{-- Link langsung ke halaman assign, bukan form confirm lama --}}
                <a href="{{ route('admin.bookings.show', $booking->_id) }}" class="btn-assign">
                    Assign Driver
                </a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- CHARTS --}}
    <div>
        <p class="db-section-label">Analitik</p>
        <div class="db-charts-grid">
            <div class="db-card">
                <div class="db-card-header">
                    <div><div class="db-card-title">Tren Pesanan</div><div class="db-card-sub">7 hari terakhir</div></div>
                    <span style="font-size:10px;font-weight:600;background:rgba(17,24,39,0.06);color:var(--text-2);padding:3px 9px;border-radius:0">Mingguan</span>
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
                    <a href="{{ route('admin.bookings.index') }}" class="db-card-link">Lihat semua</a>
                </div>
                @forelse($recentBookings ?? [] as $booking)
                <div class="db-row" style="cursor:pointer" onclick="window.location='{{ route('admin.bookings.show', $booking->_id) }}'">
                    <div style="display:flex;align-items:center;gap:10px">
                        <div class="db-row-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        </div>
                        <div>
                            <div class="db-row-code">{{ $booking->booking_code }}</div>
                            <div class="db-row-sub">{{ $booking->user['name'] ?? '-' }}</div>
                        </div>
                    </div>
                    <span class="db-badge badge-{{ $booking->status }}">{{ $booking->statusLabel() }}</span>
                </div>
                @empty
                <div style="padding:32px 18px;text-align:center"><div style="font-size:12px;color:var(--text-3)">Belum ada pesanan</div></div>
                @endforelse
            </div>
            <div class="db-card">
                <div class="db-card-header">
                    <div class="db-card-title">Status Armada</div>
                    <a href="{{ route('admin.vehicles.index') }}" class="db-card-link">Kelola</a>
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
                <a href="{{ route('admin.maps.index') }}" class="db-card-link">Peta detail</a>
            </div>
            <div class="db-map-frame"><div id="db-map"></div></div>
        </div>
    </div>

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
<link  rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
window.DashboardData = {
    bookingTrend: @json($dbBookingTrend),
    revenueData:  @json($dbRevenueData),
    vehicleLocations: @json(isset($vehicleLocations) ? $vehicleLocations : []),
    vehicleStats: {
        available:   {{ $vehicleStats['available'] ?? 0 }},
        rented:      {{ $vehicleStats['rented'] ?? 0 }},
        maintenance: {{ $vehicleStats['maintenance'] ?? 0 }},
    },
};
</script>
<script src="{{ asset('js/admin/dashboard.js') }}"></script>
@endpush
