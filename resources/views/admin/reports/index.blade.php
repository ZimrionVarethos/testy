{{-- resources/views/admin/reports/index.blade.php --}}
<x-app-layout>
<x-slot name="header">Laporan & Statistik</x-slot>

<style>
.rp { max-width:1200px; margin:0 auto; padding:24px 24px 64px; display:flex; flex-direction:column; gap:24px; }
.rp-lbl { font-family:'Epilogue',sans-serif; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.09em; color:var(--text-3); margin-bottom:10px; }
.rp-card { background:var(--white); border:1px solid var(--border); border-radius:14px; overflow:hidden; }
.rp-card-hdr { padding:14px 18px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
.rp-card-title { font-family:'Epilogue',sans-serif; font-size:13px; font-weight:700; color:var(--text-1); }
.rp-card-sub { font-size:11px; color:var(--text-3); margin-top:1px; }
.rp-card-body { padding:16px 18px; }
.rp-stat-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
.rp-stat-card { background:var(--white); border:1px solid var(--border); border-radius:14px; padding:18px 18px 14px; }
.rp-stat-tag { display:inline-flex; align-items:center; gap:5px; font-size:10px; font-weight:600; padding:3px 8px; border-radius:6px; margin-bottom:12px; }
.rp-stat-tag svg { width:11px; height:11px; }
.rp-stat-val { font-family:'Epilogue',sans-serif; font-size:30px; font-weight:800; color:var(--text-1); letter-spacing:-1px; line-height:1; margin-bottom:4px; }
.rp-stat-val.mono { font-family:'DM Mono',monospace; font-size:20px; letter-spacing:-.5px; }
.rp-stat-label { font-size:12px; color:var(--text-2); }
.rp-stat-div { height:1px; background:var(--border); margin:12px 0 10px; }
.rp-stat-foot { display:flex; align-items:center; justify-content:space-between; }
.rp-stat-foot-txt { font-size:11px; color:var(--text-3); }
.rp-chip { font-size:10px; font-weight:700; padding:2px 7px; border-radius:5px; display:inline-block; }
.chip-green  { background:var(--s-green-bg); color:var(--s-green-text); }
.chip-amber  { background:var(--s-amber-bg); color:var(--s-amber-text); }
.chip-red    { background:var(--s-red-bg);   color:var(--s-red-text); }
.chip-gray   { background:var(--s-gray-bg);  color:var(--s-gray-text); }
.tag-neutral { background:rgba(17,24,39,.06); color:var(--text-2); }
.tag-green   { background:var(--s-green-bg); color:var(--s-green-text); }
.tag-amber   { background:var(--s-amber-bg); color:var(--s-amber-text); }

/* Filter */
.rp-filter-select {
    height:34px; padding:0 28px 0 10px;
    background:var(--white); border:1px solid var(--border);
    border-radius:9px; font-size:12px; font-weight:500; color:var(--text-1);
    font-family:'DM Sans',sans-serif; cursor:pointer; transition:border-color .15s;
    appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%2394A3B8' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-repeat:no-repeat; background-position:right 10px center;
}
.rp-filter-select:focus { outline:none; border-color:var(--border-md); }
.rp-btn {
    height:34px; padding:0 14px;
    background:var(--dark); color:white;
    border:none; border-radius:9px;
    font-size:12px; font-weight:600; font-family:'DM Sans',sans-serif;
    cursor:pointer; transition:opacity .15s;
    display:inline-flex; align-items:center; gap:6px; text-decoration:none;
}
.rp-btn:hover { opacity:.8; }
.rp-btn.outline { background:var(--white); color:var(--text-1); border:1px solid var(--border); }
.rp-btn.outline:hover { border-color:var(--border-md); opacity:1; }
.rp-btn svg { width:13px; height:13px; }
.period-active   { background:var(--dark) !important; color:white !important; }
.period-inactive { background:transparent; color:var(--text-2); }
.period-inactive:hover { background:var(--bg); }

/* Grids */
.rp-charts-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.rp-charts-3 { display:grid; grid-template-columns:1fr 1fr 320px; gap:12px; }

/* Table */
.rp-table { width:100%; border-collapse:collapse; }
.rp-table th { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--text-3); padding:9px 18px; border-bottom:1px solid var(--border); text-align:left; background:var(--bg); }
.rp-table td { font-size:12px; color:var(--text-1); padding:10px 18px; border-bottom:1px solid var(--border); }
.rp-table tr:last-child td { border-bottom:none; }
.rp-table tr:hover td { background:var(--bg); }
.mono-cell { font-family:'DM Mono',monospace; }
.rp-rank { width:22px; height:22px; border-radius:7px; background:var(--bg); border:1px solid var(--border); display:inline-flex; align-items:center; justify-content:center; font-family:'DM Mono',monospace; font-size:11px; color:var(--text-2); }
.rp-rank.top { background:var(--dark); color:white; border-color:var(--dark); }
.rp-fleet-row { display:flex; align-items:center; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--border); }
.rp-fleet-row:last-child { border-bottom:none; }
.rp-fleet-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
.rp-fleet-lbl { font-size:12px; color:var(--text-2); font-weight:500; }
.rp-fleet-val { font-family:'DM Mono',monospace; font-size:14px; font-weight:500; color:var(--text-1); }
.rp-util-track { height:3px; background:var(--border); border-radius:99px; overflow:hidden; margin-top:8px; }
.rp-util-fill  { height:100%; background:var(--dark); border-radius:99px; }
.rp-bar-track { flex:1; height:4px; background:var(--border); border-radius:99px; overflow:hidden; }
.rp-bar-fill  { height:100%; background:var(--dark); border-radius:99px; }

/* Cleanup */
.rp-cleanup-hdr { padding:13px 18px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:8px; background:rgba(17,24,39,.02); }
.rp-cleanup-title { font-family:'Epilogue',sans-serif; font-size:13px; font-weight:700; color:var(--text-1); flex:1; }
.rp-cleanup-item { padding:16px 18px; border-bottom:1px solid var(--border); }
.rp-cleanup-item:last-child { border-bottom:none; }
.rp-cleanup-item-top { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:12px; }
.rp-cleanup-item-label { font-size:13px; font-weight:600; color:var(--text-1); margin-bottom:3px; }
.rp-cleanup-item-desc { font-size:11px; color:var(--text-2); line-height:1.5; }
.rp-cleanup-item-meta { display:flex; align-items:center; gap:8px; margin-top:6px; flex-wrap:wrap; }
.rp-cleanup-count { font-family:'DM Mono',monospace; font-size:24px; font-weight:500; color:var(--text-1); flex-shrink:0; }
.rp-cleanup-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.btn-export-old {
    padding:6px 12px; background:var(--white); color:var(--text-1);
    border:1px solid var(--border); border-radius:8px;
    font-size:11px; font-weight:600; cursor:pointer; font-family:'DM Sans',sans-serif;
    display:inline-flex; align-items:center; gap:5px; transition:border-color .15s; text-decoration:none;
}
.btn-export-old:hover { border-color:var(--border-md); }
.btn-delete-old {
    padding:6px 12px; background:var(--s-red-bg); color:var(--s-red-text);
    border:1px solid rgba(220,38,38,.2); border-radius:8px;
    font-size:11px; font-weight:600; cursor:pointer; font-family:'DM Sans',sans-serif;
    display:inline-flex; align-items:center; gap:5px; transition:background .15s;
}
.btn-delete-old:hover { background:rgba(220,38,38,.15); }
.btn-delete-old svg, .btn-export-old svg { width:12px; height:12px; }

/* Confirm modal */
.rp-confirm-bg { display:none; position:fixed; inset:0; background:rgba(17,24,39,.45); z-index:400; align-items:center; justify-content:center; backdrop-filter:blur(3px); }
.rp-confirm-bg.open { display:flex; animation:rp-fade .15s ease; }
@keyframes rp-fade { from{opacity:0}to{opacity:1} }
.rp-confirm-box { background:var(--white); border-radius:16px; width:100%; max-width:420px; margin:16px; padding:24px; animation:rp-pop .18s cubic-bezier(.16,1,.3,1); }
@keyframes rp-pop { from{transform:scale(.95);opacity:0}to{transform:scale(1);opacity:1} }
.rp-confirm-icon { width:42px; height:42px; border-radius:11px; background:var(--s-red-bg); display:flex; align-items:center; justify-content:center; margin-bottom:14px; }
.rp-confirm-icon svg { width:19px; height:19px; color:var(--s-red); }
.rp-confirm-title { font-family:'Epilogue',sans-serif; font-size:16px; font-weight:800; color:var(--text-1); margin-bottom:6px; letter-spacing:-.3px; }
.rp-confirm-msg { font-size:13px; color:var(--text-2); line-height:1.6; }
.rp-confirm-warn { background:var(--s-amber-bg); border:1px solid rgba(217,119,6,.2); border-radius:9px; padding:10px 13px; font-size:12px; color:var(--s-amber-text); font-weight:500; display:flex; align-items:flex-start; gap:8px; margin:14px 0 18px; line-height:1.5; }
.rp-confirm-warn svg { width:14px; height:14px; flex-shrink:0; margin-top:1px; }
.rp-confirm-btns { display:flex; gap:8px; justify-content:flex-end; }
.rp-confirm-cancel { padding:8px 16px; background:var(--white); color:var(--text-1); border:1px solid var(--border); border-radius:9px; font-size:12px; font-weight:600; cursor:pointer; font-family:'DM Sans',sans-serif; transition:border-color .15s; }
.rp-confirm-cancel:hover { border-color:var(--border-md); }
.rp-confirm-del { padding:8px 16px; background:var(--s-red); color:white; border:none; border-radius:9px; font-size:12px; font-weight:600; cursor:pointer; font-family:'DM Sans',sans-serif; transition:opacity .15s; }
.rp-confirm-del:hover { opacity:.85; }

/* Toast */
.rp-toast { position:fixed; bottom:24px; left:50%; transform:translateX(-50%); background:var(--dark); color:white; border-radius:12px; padding:11px 18px; font-size:13px; font-weight:500; font-family:'DM Sans',sans-serif; display:flex; align-items:center; gap:10px; box-shadow:0 8px 24px rgba(17,24,39,.3); z-index:500; white-space:nowrap; animation:rp-toast-in .25s cubic-bezier(.16,1,.3,1); }
@keyframes rp-toast-in { from{transform:translateX(-50%) translateY(16px);opacity:0}to{transform:translateX(-50%) translateY(0);opacity:1} }
.rp-toast svg { width:14px; height:14px; color:#4ade80; flex-shrink:0; }

@media(max-width:900px){
    .rp-stat-grid,.rp-charts-2,.rp-charts-3 { grid-template-columns:1fr 1fr; }
    .rp-charts-3 > :last-child { grid-column:1/-1; }
}
@media(max-width:560px){
    .rp-stat-grid { grid-template-columns:1fr 1fr; }
    .rp-charts-2,.rp-charts-3 { grid-template-columns:1fr; }
}
</style>

<div class="rp">

    {{-- Header --}}
    <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:10px">
        <div>
            <h2 style="font-family:'Epilogue',sans-serif;font-size:22px;font-weight:800;color:var(--text-1);letter-spacing:-.5px">Laporan & Statistik</h2>
            <p style="font-size:13px;color:var(--text-2);margin-top:3px">{{ $rangeLabel }} · Diperbarui {{ now()->locale('id')->isoFormat('D MMM YYYY, HH:mm') }}</p>
        </div>
        <a href="{{ route('dashboard') }}" class="rp-btn outline" style="text-decoration:none">
            <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 6H2M2 6l4-4M2 6l4 4"/></svg>
            Dashboard
        </a>
    </div>

    {{-- ══ FILTER BAR ══ --}}
    <form method="GET" action="{{ route('admin.reports.index') }}" id="filterForm">
        <div style="background:var(--white);border:1px solid var(--border);border-radius:14px;padding:13px 18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">

            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                {{-- Period toggle --}}
                <div style="display:flex;border:1px solid var(--border);border-radius:9px;overflow:hidden">
                    <button type="button" onclick="setPeriod('monthly')" id="btn-monthly"
                        style="padding:6px 14px;font-size:12px;font-weight:600;border:none;cursor:pointer;font-family:'DM Sans',sans-serif;transition:background .15s"
                        class="{{ $period==='monthly'?'period-active':'period-inactive' }}">Bulanan</button>
                    <button type="button" onclick="setPeriod('yearly')" id="btn-yearly"
                        style="padding:6px 14px;font-size:12px;font-weight:600;border:none;cursor:pointer;font-family:'DM Sans',sans-serif;transition:background .15s;border-left:1px solid var(--border)"
                        class="{{ $period==='yearly'?'period-active':'period-inactive' }}">Tahunan</button>
                </div>
                <input type="hidden" name="period" id="periodInput" value="{{ $period }}">

                <select name="year" class="rp-filter-select">
                    @foreach($yearOptions as $y)
                    <option value="{{ $y }}" {{ (int)$year===$y?'selected':'' }}>{{ $y }}</option>
                    @endforeach
                </select>

                <select name="month" class="rp-filter-select" id="monthSelect" style="{{ $period==='yearly'?'display:none':'' }}">
                    @foreach(range(1,12) as $m)
                    <option value="{{ $m }}" {{ (int)$month===$m?'selected':'' }}>
                        {{ \Carbon\Carbon::create(null,$m)->locale('id')->isoFormat('MMMM') }}
                    </option>
                    @endforeach
                </select>

                <button type="submit" class="rp-btn">
                    <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="6" r="4.5"/><line x1="9.5" y1="9.5" x2="13" y2="13"/></svg>
                    Tampilkan
                </button>
            </div>

            <a href="{{ route('admin.reports.export', ['period'=>$period,'year'=>$year,'month'=>$month]) }}" class="rp-btn" style="text-decoration:none">
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M7 1v8M4 6l3 3 3-3M2 10v2a1 1 0 001 1h8a1 1 0 001-1v-2"/>
                </svg>
                Export Excel
            </a>
        </div>
    </form>

    {{-- ══ STAT CARDS ══ --}}
    <div>
        <p class="rp-lbl">Ringkasan — {{ $rangeLabel }}</p>
        <div class="rp-stat-grid">
            <div class="rp-stat-card">
                <div class="rp-stat-tag tag-neutral">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2H4a2 2 0 00-2 2v9a2 2 0 002 2h8a2 2 0 002-2V4a2 2 0 00-2-2h-2M6 2a1 1 0 011-1h2a1 1 0 011 1v1H6V2z"/></svg>Total Pesanan</div>
                <div class="rp-stat-val">{{ $stats['total_bookings'] }}</div>
                <div class="rp-stat-label">Booking dalam periode</div>
                <div class="rp-stat-div"></div>
                <div class="rp-stat-foot">
                    <span class="rp-stat-foot-txt">{{ $stats['completed_bookings'] }} selesai</span>
                    <span class="rp-chip chip-gray">{{ $stats['total_bookings']>0?round($stats['completed_bookings']/$stats['total_bookings']*100):0 }}% rate</span>
                </div>
            </div>
            <div class="rp-stat-card">
                <div class="rp-stat-tag tag-green">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 8l4 4 6-7"/></svg>Selesai</div>
                <div class="rp-stat-val" style="color:var(--s-green)">{{ $stats['completed_bookings'] }}</div>
                <div class="rp-stat-label">Booking completed</div>
                <div class="rp-stat-div"></div>
                <div class="rp-stat-foot">
                    <span class="rp-stat-foot-txt">{{ $stats['cancelled_bookings'] }} dibatalkan</span>
                    <span class="rp-chip chip-green">Sukses</span>
                </div>
            </div>
            <div class="rp-stat-card">
                <div class="rp-stat-tag tag-amber">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="8" cy="8" r="6"/><polyline points="8 4 8 8 10.5 9.5"/></svg>Aktif Sekarang</div>
                <div class="rp-stat-val" style="color:var(--s-amber)">{{ $stats['pending_bookings']+$stats['ongoing_bookings'] }}</div>
                <div class="rp-stat-label">Pending + ongoing saat ini</div>
                <div class="rp-stat-div"></div>
                <div class="rp-stat-foot">
                    <span class="rp-stat-foot-txt">{{ $stats['ongoing_bookings'] }} berjalan</span>
                    @if($stats['pending_bookings']>0)
                        <span class="rp-chip chip-amber">{{ $stats['pending_bookings'] }} pending</span>
                    @else
                        <span class="rp-chip chip-green">Bersih</span>
                    @endif
                </div>
            </div>
            <div class="rp-stat-card">
                <div class="rp-stat-tag tag-neutral">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="8" y1="1" x2="8" y2="15"/><path d="M11 4H6.5a2.5 2.5 0 000 5h3a2.5 2.5 0 010 5H4"/></svg>Pendapatan</div>
                <div class="rp-stat-val mono">Rp {{ number_format(($stats['period_revenue']??0)/1000000,1,',','.') }}jt</div>
                <div class="rp-stat-label">Dalam periode ini</div>
                <div class="rp-stat-div"></div>
                <div class="rp-stat-foot">
                    <span class="rp-stat-foot-txt">Total all-time</span>
                    <span class="rp-chip chip-gray mono-cell" style="font-size:10px">Rp {{ number_format(($stats['total_revenue']??0)/1000000,1,',','.') }}jt</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Entity quick stats --}}
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
        @foreach([['Kendaraan',$stats['total_vehicles'],'vehicle'],['Driver',$stats['total_drivers'],'driver'],['Pengguna',$stats['total_users'],'user']] as [$lbl,$val,$ico])
        <div style="background:var(--white);border:1px solid var(--border);border-radius:14px;padding:14px 18px;display:flex;align-items:center;gap:13px">
            <div style="width:36px;height:36px;border-radius:9px;background:var(--bg);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                @if($ico==='vehicle')<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--text-2)" stroke-width="1.8"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                @elseif($ico==='driver')<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--text-2)" stroke-width="1.8"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                @else<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--text-2)" stroke-width="1.8"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>@endif
            </div>
            <div>
                <div style="font-size:11px;color:var(--text-3);margin-bottom:1px">Total {{ $lbl }}</div>
                <div style="font-family:'DM Mono',monospace;font-size:22px;font-weight:500;color:var(--text-1)">{{ $val }}</div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ══ CHARTS ══ --}}
    <div>
        <p class="rp-lbl">Tren — {{ $rangeLabel }}</p>
        <div class="rp-charts-2">
            <div class="rp-card">
                <div class="rp-card-hdr">
                    <div><div class="rp-card-title">Booking per Periode</div><div class="rp-card-sub">Selesai vs dibatalkan</div></div>
                </div>
                <div class="rp-card-body"><canvas id="rpBookingChart" height="120"></canvas></div>
            </div>
            <div class="rp-card">
                <div class="rp-card-hdr">
                    <div><div class="rp-card-title">Pendapatan per Periode</div><div class="rp-card-sub">Booking terkonfirmasi</div></div>
                </div>
                <div class="rp-card-body"><canvas id="rpRevenueChart" height="120"></canvas></div>
            </div>
        </div>
    </div>

    {{-- ══ STATUS + DRIVER + FLEET ══ --}}
    <div>
        <p class="rp-lbl">Operasional</p>
        <div class="rp-charts-3">

            <div class="rp-card">
                <div class="rp-card-hdr"><div class="rp-card-title">Distribusi Status</div></div>
                <div class="rp-card-body" style="display:grid;grid-template-columns:110px 1fr;align-items:center;gap:14px">
                    <canvas id="rpStatusDonut" width="110" height="110"></canvas>
                    <div>
                        @foreach($statusDistribution as $s)
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border)">
                            <div style="display:flex;align-items:center;gap:7px">
                                <span style="width:7px;height:7px;border-radius:50%;background:{{ $s['color'] }};flex-shrink:0"></span>
                                <span style="font-size:12px;color:var(--text-2)">{{ $s['label'] }}</span>
                            </div>
                            <span style="font-family:'DM Mono',monospace;font-size:13px;font-weight:500;color:var(--text-1)">{{ $s['count'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="rp-card">
                <div class="rp-card-hdr"><div class="rp-card-title">Top Driver</div><div class="rp-card-sub">Trip selesai dalam periode</div></div>
                @php $maxTrips = collect($topDrivers)->max('trips') ?: 1; @endphp
                @forelse($topDrivers as $i => $d)
                <div style="display:flex;align-items:center;gap:11px;padding:11px 18px;border-bottom:1px solid var(--border)">
                    <span class="rp-rank {{ $i===0?'top':'' }}">{{ $i+1 }}</span>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:600;color:var(--text-1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:5px">{{ $d['name'] }}</div>
                        <div style="display:flex;align-items:center;gap:8px">
                            <div class="rp-bar-track"><div class="rp-bar-fill" style="width:{{ round($d['trips']/$maxTrips*100) }}%"></div></div>
                            <span style="font-family:'DM Mono',monospace;font-size:11px;color:var(--text-3);flex-shrink:0">{{ $d['trips'] }}</span>
                        </div>
                    </div>
                    <div style="font-family:'DM Mono',monospace;font-size:11px;color:var(--text-2);flex-shrink:0">Rp {{ number_format($d['revenue']/1000000,1,',','.') }}jt</div>
                </div>
                @empty
                <div style="padding:24px;text-align:center;font-size:12px;color:var(--text-3)">Belum ada data driver</div>
                @endforelse
            </div>

            <div class="rp-card">
                <div class="rp-card-hdr"><div class="rp-card-title">Status Armada</div></div>
                <div class="rp-card-body">
                    <div style="display:flex;justify-content:center;margin-bottom:14px"><canvas id="rpFleetDonut" width="100" height="100"></canvas></div>
                    @php $rpTotal=array_sum($fleetStats); $rpUtil=$rpTotal>0?round($fleetStats['rented']/$rpTotal*100):0; @endphp
                    @foreach([['Tersedia','var(--s-green)','available'],['Disewa','var(--s-blue)','rented'],['Maintenance','var(--s-amber)','maintenance']] as [$l,$c,$k])
                    <div class="rp-fleet-row">
                        <div style="display:flex;align-items:center;gap:7px">
                            <span class="rp-fleet-dot" style="background:{{ $c }}"></span>
                            <span class="rp-fleet-lbl">{{ $l }}</span>
                        </div>
                        <span class="rp-fleet-val">{{ $fleetStats[$k]??0 }}</span>
                    </div>
                    @endforeach
                    <div style="margin-top:12px;padding-top:11px;border-top:1px solid var(--border)">
                        <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:5px">
                            <span style="font-size:10px;color:var(--text-3);font-weight:700;text-transform:uppercase;letter-spacing:.06em">Utilisasi</span>
                            <span style="font-family:'DM Mono',monospace;font-size:18px;font-weight:500;color:var(--text-1)">{{ $rpUtil }}%</span>
                        </div>
                        <div class="rp-util-track"><div class="rp-util-fill" style="width:{{ $rpUtil }}%"></div></div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ══ CLEANUP RECOMMENDATIONS ══ --}}
    @if($cleanupSuggestions->count() > 0)
    <div>
        <p class="rp-lbl">Rekomendasi Pembersihan Data</p>
        <div class="rp-card">
            <div class="rp-cleanup-hdr">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--text-2)" stroke-width="1.8"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                <span class="rp-cleanup-title">Data Lama yang Disarankan untuk Dihapus</span>
                <span style="font-size:11px;color:var(--text-3)">{{ $cleanupSuggestions->count() }} rekomendasi</span>
            </div>
            @foreach($cleanupSuggestions as $s)
            <div class="rp-cleanup-item">
                <div class="rp-cleanup-item-top">
                    <div>
                        <div class="rp-cleanup-item-label">{{ $s['label'] }}</div>
                        <div class="rp-cleanup-item-desc">{{ $s['description'] }}</div>
                        <div class="rp-cleanup-item-meta">
                            @if($s['oldest'])
                            <span style="font-size:11px;color:var(--text-3)">Data tertua: <strong style="color:var(--text-2)">{{ $s['oldest'] }}</strong></span>
                            @endif
                            <span class="rp-chip {{ $s['color']==='red'?'chip-red':'chip-amber' }}">{{ $s['threshold'] }} lalu</span>
                        </div>
                    </div>
                    <div class="rp-cleanup-count">{{ number_format($s['count']) }}</div>
                </div>
                <div class="rp-cleanup-actions">
                    <form method="POST" action="{{ route('admin.reports.export-old') }}" style="margin:0">
                        @csrf
                        <input type="hidden" name="type" value="{{ $s['key'] }}">
                        <button type="submit" class="btn-export-old">
                            <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 1v8M4 6l3 3 3-3M2 10v2a1 1 0 001 1h8a1 1 0 001-1v-2"/></svg>
                            Export Arsip Dulu
                        </button>
                    </form>
                    <button type="button" class="btn-delete-old" onclick="rpConfirmDelete('{{ $s['key'] }}','{{ $s['label'] }}',{{ $s['count'] }})">
                        <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="2 3.5 3 3.5 12 3.5"/><path d="M10 3.5V11a1 1 0 01-1 1H5a1 1 0 01-1-1V3.5"/><path d="M5 3.5V2.5a1 1 0 011-1h2a1 1 0 011 1v1"/></svg>
                        Hapus Data
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>

{{-- Confirm modal --}}
<div class="rp-confirm-bg" id="rpConfirmBg" onclick="rpCloseConfirm(event)">
    <div class="rp-confirm-box">
        <div class="rp-confirm-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
        </div>
        <div class="rp-confirm-title" id="rpConfirmTitle">Hapus Data?</div>
        <div class="rp-confirm-msg" id="rpConfirmMsg"></div>
        <div class="rp-confirm-warn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            <span>Sangat disarankan untuk <strong>export arsip terlebih dahulu</strong> sebelum menghapus. Gunakan tombol <em>"Export Arsip Dulu"</em> untuk menyimpan data ke Excel.</span>
        </div>
        <div class="rp-confirm-btns">
            <button type="button" class="rp-confirm-cancel" onclick="rpForceClose()">Batal</button>
            <form method="POST" action="{{ route('admin.reports.delete-old') }}" style="margin:0" id="rpDeleteForm">
                @csrf @method('DELETE')
                <input type="hidden" name="type" id="rpDeleteType">
                <button type="submit" class="rp-confirm-del">Ya, Hapus Sekarang</button>
            </form>
        </div>
    </div>
</div>

@if(session('cleanup_success'))
<div class="rp-toast" id="rpToast">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
    {{ session('cleanup_success') }}
</div>
<script>setTimeout(()=>{const t=document.getElementById('rpToast');if(t){t.style.transition='opacity .3s';t.style.opacity='0';setTimeout(()=>t.remove(),300);}},3500);</script>
@endif

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'DM Sans', sans-serif";
Chart.defaults.color = 'rgba(17,24,39,0.35)';

const rpLabels    = @json($chartLabels);
const rpCompleted = @json($chartCompleted);
const rpCancelled = @json($chartCancelled);
const rpRevenue   = @json($chartRevenue);
const rpStatus    = @json($statusDistribution);
const rpFleet     = @json($fleetStats);

(function(){
    const ctx = document.getElementById('rpBookingChart').getContext('2d');
    new Chart(ctx,{ type:'bar', data:{ labels:rpLabels, datasets:[
        { label:'Selesai', data:rpCompleted, backgroundColor:'rgba(17,24,39,0.75)', borderRadius:4, borderSkipped:false },
        { label:'Dibatalkan', data:rpCancelled, backgroundColor:'rgba(220,38,38,0.18)', borderRadius:4, borderSkipped:false },
    ]}, options:{ responsive:true, maintainAspectRatio:true,
        plugins:{ legend:{ display:true, position:'top', labels:{font:{size:11},boxWidth:10,boxHeight:10,borderRadius:3,useBorderRadius:true,padding:12} }, tooltip:{backgroundColor:'rgb(17,24,39)',bodyFont:{size:11},padding:10,cornerRadius:8} },
        scales:{ y:{beginAtZero:true,grid:{color:'rgba(17,24,39,0.05)'},ticks:{font:{size:10},precision:0}}, x:{grid:{display:false},ticks:{font:{size:10}}} }
    }});
})();

(function(){
    const ctx=document.getElementById('rpRevenueChart').getContext('2d');
    const g=ctx.createLinearGradient(0,0,0,200);
    g.addColorStop(0,'rgba(17,24,39,0.08)'); g.addColorStop(1,'rgba(17,24,39,0)');
    new Chart(ctx,{ type:'line', data:{ labels:rpLabels, datasets:[{ data:rpRevenue, borderColor:'rgb(17,24,39)', backgroundColor:g, borderWidth:2, fill:true, tension:0.4, pointBackgroundColor:'rgb(17,24,39)', pointBorderColor:'#fff', pointBorderWidth:2, pointRadius:3.5, pointHoverRadius:5 }]},
        options:{ responsive:true, maintainAspectRatio:true,
            plugins:{ legend:{display:false}, tooltip:{backgroundColor:'rgb(17,24,39)',bodyFont:{family:"'DM Mono',monospace",size:11},padding:10,cornerRadius:8, callbacks:{label:c=>`  Rp ${c.raw.toLocaleString('id-ID')}`}} },
            scales:{ y:{beginAtZero:true,grid:{color:'rgba(17,24,39,0.05)'},ticks:{font:{size:10},callback:v=>'Rp'+(v/1000000).toFixed(0)+'jt'}}, x:{grid:{display:false},ticks:{font:{size:10}}} }
        }
    });
})();

(function(){
    new Chart(document.getElementById('rpStatusDonut').getContext('2d'),{
        type:'doughnut', data:{ labels:rpStatus.map(d=>d.label), datasets:[{data:rpStatus.map(d=>d.count),backgroundColor:rpStatus.map(d=>d.color),borderWidth:0,hoverOffset:4}]},
        options:{responsive:false,cutout:'70%',plugins:{legend:{display:false},tooltip:{backgroundColor:'rgb(17,24,39)',bodyFont:{size:11},padding:10,cornerRadius:8}}}
    });
    new Chart(document.getElementById('rpFleetDonut').getContext('2d'),{
        type:'doughnut', data:{ labels:['Tersedia','Disewa','Maintenance'], datasets:[{data:[rpFleet.available||0,rpFleet.rented||0,rpFleet.maintenance||0],backgroundColor:['#16a34a','#2563eb','#d97706'],borderWidth:0,hoverOffset:4}]},
        options:{responsive:false,cutout:'72%',plugins:{legend:{display:false},tooltip:{backgroundColor:'rgb(17,24,39)',bodyFont:{size:11},padding:10,cornerRadius:8}}}
    });
})();

function setPeriod(p){
    document.getElementById('periodInput').value=p;
    document.getElementById('monthSelect').style.display=p==='yearly'?'none':'';
    ['monthly','yearly'].forEach(id=>{
        const btn=document.getElementById('btn-'+id);
        btn.className=btn.className.replace('period-active','').replace('period-inactive','').trim()+' '+(id===p?'period-active':'period-inactive');
    });
}
function rpConfirmDelete(type,label,count){
    document.getElementById('rpDeleteType').value=type;
    document.getElementById('rpConfirmTitle').textContent=`Hapus ${label}?`;
    document.getElementById('rpConfirmMsg').textContent=`Akan menghapus ${count.toLocaleString('id-ID')} data secara permanen. Tindakan ini tidak dapat dibatalkan.`;
    document.getElementById('rpConfirmBg').classList.add('open');
    document.body.style.overflow='hidden';
}
function rpCloseConfirm(e){ if(e.target===document.getElementById('rpConfirmBg')) rpForceClose(); }
function rpForceClose(){ document.getElementById('rpConfirmBg').classList.remove('open'); document.body.style.overflow=''; }
document.addEventListener('keydown',e=>{ if(e.key==='Escape') rpForceClose(); });
</script>
@endpush

</x-app-layout>