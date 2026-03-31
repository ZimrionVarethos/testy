{{-- resources/views/admin/dashboard.blade.php --}}
{{-- 
    STATIC VEHICLE DATA (untuk testing):
    Ganti $vehicleLocations dengan data dari API/database kamu.
    Lihat bagian "CARA KONEKSI API LOKASI" di bawah.
--}}

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<style>
    :root {
        --brand-primary: #4F46E5;
        --brand-secondary: #0EA5E9;
        --brand-accent: #10B981;
        --brand-warn: #F59E0B;
        --brand-danger: #EF4444;
        --surface: #F8FAFC;
        --card: #FFFFFF;
        --border: #E2E8F0;
        --text-primary: #0F172A;
        --text-secondary: #64748B;
        --text-muted: #94A3B8;
    }

    body, .dashboard-root { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--surface); }

    /* ── Stat Cards ── */
    .stat-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 20px 24px;
        position: relative;
        overflow: hidden;
        transition: transform .2s, box-shadow .2s;
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.07); }
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        border-radius: 16px 16px 0 0;
    }
    .stat-card.indigo::before  { background: linear-gradient(90deg, #4F46E5, #818CF8); }
    .stat-card.amber::before   { background: linear-gradient(90deg, #F59E0B, #FCD34D); }
    .stat-card.green::before   { background: linear-gradient(90deg, #10B981, #6EE7B7); }
    .stat-card.sky::before     { background: linear-gradient(90deg, #0EA5E9, #7DD3FC); }

    .stat-icon {
        width: 40px; height: 40px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 14px;
    }
    .stat-icon.indigo { background: #EEF2FF; color: #4F46E5; }
    .stat-icon.amber  { background: #FFFBEB; color: #D97706; }
    .stat-icon.green  { background: #ECFDF5; color: #059669; }
    .stat-icon.sky    { background: #F0F9FF; color: #0284C7; }

    .stat-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); }
    .stat-value { font-size: 28px; font-weight: 800; color: var(--text-primary); line-height: 1.1; margin: 6px 0 4px; }
    .stat-sub   { font-size: 12px; font-weight: 500; }

    /* ── Section Card ── */
    .section-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 16px;
        overflow: hidden;
    }
    .section-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid var(--border);
    }
    .section-title { font-size: 14px; font-weight: 700; color: var(--text-primary); }
    .section-link  { font-size: 12px; font-weight: 600; color: var(--brand-primary); text-decoration: none; }
    .section-link:hover { text-decoration: underline; }

    /* ── Status Badge ── */
    .badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 10px; border-radius: 99px;
        font-size: 11px; font-weight: 600;
    }
    .badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; }
    .badge-pending   { background: #FFFBEB; color: #92400E; } .badge-pending::before   { background: #F59E0B; }
    .badge-accepted  { background: #EFF6FF; color: #1E40AF; } .badge-accepted::before  { background: #3B82F6; }
    .badge-confirmed { background: #EEF2FF; color: #3730A3; } .badge-confirmed::before { background: #6366F1; }
    .badge-ongoing   { background: #ECFDF5; color: #065F46; } .badge-ongoing::before   { background: #10B981; }
    .badge-completed { background: #F8FAFC; color: #475569; } .badge-completed::before { background: #94A3B8; }
    .badge-cancelled { background: #FEF2F2; color: #991B1B; } .badge-cancelled::before { background: #EF4444; }

    /* ── Chart Container ── */
    .chart-wrap { position: relative; }
    
    /* ── Map ── */

    /* ── Confirm button ── */
    .btn-confirm {
        padding: 6px 14px;
        background: var(--brand-primary);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s, transform .1s;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .btn-confirm:hover { background: #4338CA; transform: translateY(-1px); }

    /* ── Revenue Mono ── */
    .mono { font-family: 'JetBrains Mono', monospace; }

    /* ── Alert Banner ── */
    .alert-banner {
        background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
        border: 1px solid #FDE68A;
        border-radius: 16px;
        overflow: hidden;
    }
    .alert-header {
        padding: 14px 20px;
        border-bottom: 1px solid #FDE68A;
        display: flex; align-items: center; gap-x: 8px; gap: 8px;
    }

    /* ── Fleet Donut Label ── */
    .fleet-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid var(--border);
    }
    .fleet-row:last-child { border-bottom: none; }
    .fleet-dot { width: 10px; height: 10px; border-radius: 50%; }
    .fleet-label { font-size: 13px; color: var(--text-secondary); font-weight: 500; }
    .fleet-count { font-size: 15px; font-weight: 700; color: var(--text-primary); }

    /* Responsive */
    @media (max-width: 768px) {
        .stat-value { font-size: 22px; }
        #indonesia-map { height: 260px; }
    }
</style>

<div class="dashboard-root py-6">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- ══ GREETING ══ --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-extrabold text-gray-900">Selamat datang, {{ Auth::user()->name }} 👋</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ now()->locale('id')->isoFormat('dddd, D MMMM YYYY') }} · Ringkasan aktivitas hari ini</p>
        </div>
        <span class="hidden sm:flex items-center gap-1.5 text-xs font-semibold text-emerald-600 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-full">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            Live
        </span>
    </div>

    {{-- ══ STAT CARDS ══ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card indigo">
            <div class="stat-icon indigo">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <p class="stat-label">Total Pesanan</p>
            <p class="stat-value">{{ $stats['total_bookings'] ?? 0 }}</p>
            <p class="stat-sub text-indigo-400">Semua waktu</p>
        </div>
        <div class="stat-card amber">
            <div class="stat-icon amber">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <p class="stat-label">Menunggu Konfirmasi</p>
            <p class="stat-value" style="color:#D97706">{{ $stats['pending_bookings'] ?? 0 }}</p>
            <p class="stat-sub text-amber-400">Perlu tindakan</p>
        </div>
        <div class="stat-card green">
            <div class="stat-icon green">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12l5 5L20 7"/></svg>
            </div>
            <p class="stat-label">Sedang Berjalan</p>
            <p class="stat-value" style="color:#059669">{{ $stats['ongoing_bookings'] ?? 0 }}</p>
            <p class="stat-sub text-emerald-400">Aktif sekarang</p>
        </div>
        <div class="stat-card sky">
            <div class="stat-icon sky">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            </div>
            <p class="stat-label">Pendapatan Bulan Ini</p>
            <p class="stat-value mono text-xl" style="color:#0284C7">Rp {{ number_format($stats['monthly_revenue'] ?? 0, 0, ',', '.') }}</p>
            <p class="stat-sub text-sky-400">Bulan ini</p>
        </div>
    </div>

    {{-- ══ CHARTS ROW ══ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Booking Trend Chart (7 hari) --}}
        <div class="section-card lg:col-span-2">
            <div class="section-header">
                <div>
                    <p class="section-title">Tren Pesanan</p>
                    <p class="text-xs text-gray-400 mt-0.5">7 hari terakhir</p>
                </div>
                <span class="text-xs font-semibold bg-indigo-50 text-indigo-600 px-2.5 py-1 rounded-full">Mingguan</span>
            </div>
            <div class="chart-wrap p-4">
                <canvas id="bookingChart" height="110"></canvas>
            </div>
        </div>

        {{-- Revenue Chart (6 bulan) --}}
        <div class="section-card">
            <div class="section-header">
                <div>
                    <p class="section-title">Pendapatan</p>
                    <p class="text-xs text-gray-400 mt-0.5">6 bulan terakhir</p>
                </div>
            </div>
            <div class="chart-wrap p-4">
                <canvas id="revenueChart" height="178"></canvas>
            </div>
        </div>
    </div>

    {{-- ══ BOOKINGS + FLEET ROW ══ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Pesanan Terbaru --}}
        <div class="section-card">
            <div class="section-header">
                <p class="section-title">Pesanan Terbaru</p>
                <a href="{{ route('admin.bookings.index') }}" class="section-link">Lihat semua →</a>
            </div>
            <div>
                @forelse($recentBookings ?? [] as $booking)
                <div class="flex items-center justify-between px-5 py-3 hover:bg-slate-50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-lg bg-indigo-50 flex items-center justify-center flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800 mono">{{ $booking->booking_code }}</p>
                            <p class="text-xs text-gray-400">{{ $booking->user['name'] ?? '-' }}</p>
                        </div>
                    </div>
                    <span class="badge badge-{{ $booking->status }}">{{ ucfirst($booking->status) }}</span>
                </div>
                @empty
                <div class="flex flex-col items-center justify-center py-10 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mb-2 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                    <p class="text-sm">Belum ada pesanan.</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Status Armada + Donut ── --}}
        <div class="section-card">
            <div class="section-header">
                <p class="section-title">Status Armada</p>
                <a href="{{ route('admin.vehicles.index') }}" class="section-link">Kelola →</a>
            </div>
            <div class="grid grid-cols-2 gap-4 p-5 items-center">
                <div>
                    <canvas id="fleetDonut" width="160" height="160"></canvas>
                </div>
                <div class="space-y-1">
                    <div class="fleet-row">
                        <div class="flex items-center gap-2">
                            <span class="fleet-dot" style="background:#10B981"></span>
                            <span class="fleet-label">Tersedia</span>
                        </div>
                        <span class="fleet-count">{{ $vehicleStats['available'] ?? 0 }}</span>
                    </div>
                    <div class="fleet-row">
                        <div class="flex items-center gap-2">
                            <span class="fleet-dot" style="background:#3B82F6"></span>
                            <span class="fleet-label">Disewa</span>
                        </div>
                        <span class="fleet-count">{{ $vehicleStats['rented'] ?? 0 }}</span>
                    </div>
                    <div class="fleet-row">
                        <div class="flex items-center gap-2">
                            <span class="fleet-dot" style="background:#F59E0B"></span>
                            <span class="fleet-label">Maintenance</span>
                        </div>
                        <span class="fleet-count">{{ $vehicleStats['maintenance'] ?? 0 }}</span>
                    </div>
                    <div class="mt-3 pt-2 border-t border-gray-100">
                        @php
                            $total = ($vehicleStats['available'] ?? 0) + ($vehicleStats['rented'] ?? 0) + ($vehicleStats['maintenance'] ?? 0);
                            $utilization = $total > 0 ? round((($vehicleStats['rented'] ?? 0) / $total) * 100) : 0;
                        @endphp
                        <p class="text-xs text-gray-400 font-medium">Utilisasi Armada</p>
                        <p class="text-xl font-extrabold text-gray-800 mono">{{ $utilization }}%</p>
                        <div class="mt-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500 rounded-full transition-all" style="width:{{ $utilization }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ PETA REAL-TIME ══ --}}
    <div class="section-card">
        
        <div class="section-header">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                <p class="section-title">Lokasi Armada Real-time</p>
            </div>
            <a href="{{ route('admin.maps.index') }}" class="section-link">Peta Detail →</a>
        </div>

        {{-- Leaflet CSS (inline, bukan @push) --}}
        <style>
            #leaflet-map { height: 420px; width: 100%; border-radius: 0 0 16px 16px; z-index: 0; }
            .leaflet-container { font-family: 'Plus Jakarta Sans', sans-serif; background: #DBEAFE; }
            /* Custom pin styles */
            .v-marker-wrap { position: relative; }
            .leaflet-control-zoom { border: none !important; box-shadow: 0 2px 8px rgba(0,0,0,.12) !important; }
            .leaflet-control-zoom a {
                font-size: 16px !important; color: #374151 !important;
                border-radius: 8px !important; border: none !important;
                width: 30px !important; height: 30px !important; line-height: 30px !important;
            }
            .leaflet-control-zoom a:hover { background: #EEF2FF !important; color: #4F46E5 !important; }
            /* Map legend */
            .map-legend-leaflet {
                background: white; border: 1px solid #E2E8F0;
                border-radius: 10px; padding: 10px 14px;
                font-family: 'Plus Jakarta Sans', sans-serif;
                box-shadow: 0 2px 8px rgba(0,0,0,.06);
                line-height: 1.6;
            }
            .map-legend-leaflet p { font-size: 10px; font-weight: 800; color: #374151; margin: 0 0 5px; letter-spacing: .04em; }
            .map-legend-leaflet span { display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; color: #475569; }
            .leg-dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
            /* Popup */
            .leaflet-popup-content-wrapper {
                border-radius: 12px !important; padding: 0 !important;
                box-shadow: 0 8px 24px rgba(0,0,0,.15) !important; border: none !important;
            }
            .leaflet-popup-content { margin: 0 !important; }
            .leaflet-popup-tip-container { display: none; }
            .pin-popup { padding: 12px 16px; min-width: 180px; }
            .pin-popup-plate { font-size: 14px; font-weight: 800; color: #0F172A; font-family: 'JetBrains Mono', monospace; }
            .pin-popup-driver { font-size: 11px; color: #94A3B8; margin: 2px 0 6px; }
            .pin-popup-status { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 99px; }
            .pin-popup-link { display: block; margin-top: 8px; font-size: 11px; font-weight: 600; color: #4F46E5; text-decoration: none; }
            .pin-popup-link:hover { text-decoration: underline; }
        </style>

        <div id="leaflet-map"></div>
    </div>

    {{-- ══ ALERT BANNER: Perlu Konfirmasi ══ --}}
    @if(isset($acceptedBookings) && $acceptedBookings->count() > 0)
    <div class="alert-banner">
        <div class="alert-header">
            <svg class="h-4 w-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 110 18A9 9 0 0112 3z"/>
            </svg>
            <h4 class="font-bold text-amber-800 text-sm">Perlu Konfirmasi Admin ({{ $acceptedBookings->count() }})</h4>
        </div>
        <div>
            @foreach($acceptedBookings as $booking)
            <div class="flex items-center justify-between px-5 py-3 hover:bg-amber-50/50 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="h-7 w-7 rounded-md bg-amber-100 flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-800 mono">{{ $booking->booking_code }}</p>
                        <p class="text-xs text-gray-500">Driver: <span class="font-semibold">{{ $booking->driver['name'] ?? '-' }}</span> · User: <span class="font-semibold">{{ $booking->user['name'] ?? '-' }}</span></p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.bookings.confirm', $booking->_id) }}">
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

{{-- ══ SCRIPTS ══ --}}
@php
$defaultBookingTrend = [
    ['date'=>'Sen','total'=>8],
    ['date'=>'Sel','total'=>14],
    ['date'=>'Rab','total'=>11],
    ['date'=>'Kam','total'=>19],
    ['date'=>'Jum','total'=>22],
    ['date'=>'Sab','total'=>17],
    ['date'=>'Min','total'=>9],
];
$defaultRevenueChart = [
    ['month'=>'Okt','revenue'=>12000000],
    ['month'=>'Nov','revenue'=>18500000],
    ['month'=>'Des','revenue'=>22000000],
    ['month'=>'Jan','revenue'=>16000000],
    ['month'=>'Feb','revenue'=>25500000],
    ['month'=>'Mar','revenue'=>28000000],
];
$chartBookingTrend = $bookingTrend ?? $defaultBookingTrend;
$chartRevenueData  = $revenueChart ?? $defaultRevenueChart;
$mapVehicleLocations = isset($vehicleLocations) ? (array) $vehicleLocations : [];
@endphp

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
// ─────────────────────────────────────────────
//  CHART DATA (ganti dengan data dari controller)
// ─────────────────────────────────────────────

// Booking trend — dari: $bookingTrend = Booking::selectRaw('DATE(created_at) as date, COUNT(*) as total')...
const bookingTrendData = @json($chartBookingTrend);

// Revenue per bulan — dari: $revenueChart = Booking::selectRaw('MONTH(created_at) as month, SUM(total_price) as revenue')...
const revenueData = @json($chartRevenueData);

// ── Booking Trend Line Chart ──
const bookingCtx = document.getElementById('bookingChart').getContext('2d');
const gradientBooking = bookingCtx.createLinearGradient(0,0,0,200);
gradientBooking.addColorStop(0,'rgba(79,70,229,0.18)');
gradientBooking.addColorStop(1,'rgba(79,70,229,0)');

new Chart(bookingCtx, {
    type: 'line',
    data: {
        labels: bookingTrendData.map(d => d.date),
        datasets: [{
            label: 'Pesanan',
            data: bookingTrendData.map(d => d.total),
            borderColor: '#4F46E5',
            backgroundColor: gradientBooking,
            borderWidth: 2.5,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#4F46E5',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: true,
        plugins: { legend: { display: false }, tooltip: {
            backgroundColor: '#0F172A',
            titleFont: { family: 'Plus Jakarta Sans', size: 12 },
            bodyFont:  { family: 'Plus Jakarta Sans', size: 12 },
            padding: 10, cornerRadius: 8,
            callbacks: { label: ctx => `  ${ctx.raw} pesanan` }
        }},
        scales: {
            y: { beginAtZero: true, grid: { color: '#F1F5F9' }, ticks: { font: { family: 'Plus Jakarta Sans', size: 11 }, color: '#94A3B8' } },
            x: { grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans', size: 11 }, color: '#94A3B8' } }
        }
    }
});

// ── Revenue Bar Chart ──
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const gradientRevenue = revenueCtx.createLinearGradient(0,0,0,250);
gradientRevenue.addColorStop(0,'rgba(14,165,233,0.9)');
gradientRevenue.addColorStop(1,'rgba(14,165,233,0.3)');

new Chart(revenueCtx, {
    type: 'bar',
    data: {
        labels: revenueData.map(d => d.month),
        datasets: [{
            label: 'Pendapatan',
            data: revenueData.map(d => d.revenue),
            backgroundColor: gradientRevenue,
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: true,
        plugins: { legend: { display: false }, tooltip: {
            backgroundColor: '#0F172A',
            titleFont: { family: 'Plus Jakarta Sans', size: 12 },
            bodyFont:  { family: 'JetBrains Mono', size: 11 },
            padding: 10, cornerRadius: 8,
            callbacks: { label: ctx => `  Rp ${ctx.raw.toLocaleString('id-ID')}` }
        }},
        scales: {
            y: { beginAtZero: true, grid: { color: '#F1F5F9' },
                 ticks: { font: { family: 'Plus Jakarta Sans', size: 10 }, color: '#94A3B8',
                          callback: v => 'Rp' + (v/1000000).toFixed(0) + 'jt' } },
            x: { grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans', size: 11 }, color: '#94A3B8' } }
        }
    }
});

// ── Fleet Donut Chart ──
const fleetCtx = document.getElementById('fleetDonut').getContext('2d');
new Chart(fleetCtx, {
    type: 'doughnut',
    data: {
        labels: ['Tersedia', 'Disewa', 'Maintenance'],
        datasets: [{
            data: [
                {{ $vehicleStats['available'] ?? 5 }},
                {{ $vehicleStats['rented'] ?? 3 }},
                {{ $vehicleStats['maintenance'] ?? 1 }}
            ],
            backgroundColor: ['#10B981','#3B82F6','#F59E0B'],
            borderWidth: 0,
            hoverOffset: 6,
        }]
    },
    options: {
        responsive: false, cutout: '70%',
        plugins: { legend: { display: false }, tooltip: {
            backgroundColor: '#0F172A',
            bodyFont: { family: 'Plus Jakarta Sans', size: 12 },
            padding: 10, cornerRadius: 8,
        }},
    }
});

// ─────────────────────────────────────────────
//  LEAFLET MAP — Indonesia + Vehicle Pins
// ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {

    const vehicles = @json($mapVehicleLocations);

    const statusConfig = {
        ongoing:     { color: '#10B981', bg: '#ECFDF5', text: '#065F46', label: 'Berjalan' },
        available:   { color: '#3B82F6', bg: '#EFF6FF', text: '#1E40AF', label: 'Tersedia' },
        maintenance: { color: '#F59E0B', bg: '#FFFBEB', text: '#92400E', label: 'Maintenance' },
        rented:      { color: '#3B82F6', bg: '#EFF6FF', text: '#1E40AF', label: 'Disewa' },
    };

    const map = L.map('leaflet-map', {
        center: [-2.5, 118],
        zoom: 5,
        minZoom: 4,
        maxZoom: 14,
        zoomControl: true,
        scrollWheelZoom: false,
        maxBounds: [[-15, 90], [10, 145]],
        maxBoundsViscosity: 0.9,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    function makeIcon(status) {
        const cfg = statusConfig[status] || statusConfig.available;
        const pulse = status === 'ongoing' ? `
            <circle cx="16" cy="16" r="14" fill="none" stroke="${cfg.color}" stroke-width="2" opacity="0.5">
                <animate attributeName="r" values="14;22;14" dur="2s" repeatCount="indefinite"/>
                <animate attributeName="opacity" values="0.5;0;0.5" dur="2s" repeatCount="indefinite"/>
            </circle>` : '';
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36">
            ${pulse}
            <circle cx="18" cy="18" r="14" fill="${cfg.color}" fill-opacity="0.18"/>
            <circle cx="18" cy="18" r="9"  fill="${cfg.color}"/>
            <circle cx="18" cy="18" r="3.5" fill="white"/>
        </svg>`;
        return L.divIcon({ html: svg, className: '', iconSize: [36,36], iconAnchor: [18,18], popupAnchor: [0,-20] });
    }

    vehicles.forEach(v => {
        if (!v.lat || !v.lon) return;
        const cfg = statusConfig[v.status] || statusConfig.available;
        const isDemo = String(v.id).startsWith('demo');
        const detailLink = !isDemo ? `<a href="/admin/maps/${v.id}" class="pin-popup-link">Lihat Detail →</a>` : '';
        L.marker([v.lat, v.lon], { icon: makeIcon(v.status) })
            .bindPopup(`<div class="pin-popup">
                <div class="pin-popup-plate">${v.plate}</div>
                <div class="pin-popup-driver">Driver: ${v.driver || '-'}</div>
                <span class="pin-popup-status" style="background:${cfg.bg};color:${cfg.text}">
                    <span style="width:7px;height:7px;border-radius:50%;background:${cfg.color};display:inline-block;margin-right:4px"></span>
                    ${cfg.label}
                </span>
                ${detailLink}
            </div>`, { maxWidth: 220, className: '' })
            .addTo(map);
    });

    const legend = L.control({ position: 'bottomleft' });
    legend.onAdd = () => {
        const d = L.DomUtil.create('div','map-legend-leaflet');
        d.innerHTML = `<p>LEGENDA</p>
            <span><i class="leg-dot" style="background:#10B981"></i> Berjalan</span>
            <span><i class="leg-dot" style="background:#3B82F6"></i> Tersedia</span>
            <span><i class="leg-dot" style="background:#F59E0B"></i> Maintenance</span>`;
        return d;
    };
    legend.addTo(map);

    setTimeout(() => map.invalidateSize(), 400);
});
</script>

