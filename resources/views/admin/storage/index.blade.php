<x-app-layout>
    <x-slot name="header">Storage Monitor</x-slot>

    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
    :root {
        --bg:       #0D1117;
        --surface:  #161B22;
        --border:   #21262D;
        --border2:  #30363D;
        --text:     #E6EDF3;
        --muted:    #7D8590;
        --accent:   #58A6FF;
        --green:    #3FB950;
        --yellow:   #D29922;
        --red:      #F85149;
        --orange:   #E3814B;
        --mono:     'IBM Plex Mono', monospace;
        --sans:     'IBM Plex Sans', sans-serif;
    }

    .st-root *, .st-root *::before, .st-root *::after { box-sizing: border-box; }
    .st-root {
        font-family: var(--sans);
        background: var(--bg);
        min-height: calc(100vh - 64px);
        padding: 28px 0;
        color: var(--text);
    }
    .st-wrap { max-width: 1200px; margin: 0 auto; padding: 0 28px; }

    /* ── Header ── */
    .st-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 28px; gap: 16px; flex-wrap: wrap; }
    .st-header-left h1 { font-size: 22px; font-weight: 700; color: var(--text); margin: 0; font-family: var(--mono); letter-spacing: -.3px; }
    .st-header-left p  { font-size: 13px; color: var(--muted); margin: 4px 0 0; }
    .st-refresh { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: var(--accent); text-decoration: none; border: 1px solid #1F4E8C; background: #0D2137; padding: 7px 14px; border-radius: 8px; font-family: var(--mono); transition: background .15s; }
    .st-refresh:hover { background: #112D4E; }

    /* ── Flash ── */
    .st-flash { padding: 10px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; margin-bottom: 20px; }
    .st-flash.success { background: #0D2E1A; border: 1px solid #1A4731; color: var(--green); }
    .st-flash.error   { background: #2E0D0D; border: 1px solid #471A1A; color: var(--red); }

    /* ── Storage Overview ── */
    .st-overview { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 24px; margin-bottom: 24px; }
    .st-ov-top { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 14px; gap: 12px; flex-wrap: wrap; }
    .st-ov-title { font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .1em; font-family: var(--mono); }
    .st-ov-used  { font-size: 28px; font-weight: 600; color: var(--text); font-family: var(--mono); line-height: 1; }
    .st-ov-limit { font-size: 13px; color: var(--muted); font-family: var(--mono); margin-top: 4px; }
    .st-ov-pct   { font-size: 14px; font-weight: 600; font-family: var(--mono); }

    /* Progress bar */
    .st-bar-track { height: 10px; background: var(--border2); border-radius: 99px; overflow: hidden; margin-bottom: 16px; }
    .st-bar-fill  { height: 100%; border-radius: 99px; transition: width 1s cubic-bezier(.4,0,.2,1); }

    /* Mini stats row */
    .st-ov-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    @media(max-width:640px){ .st-ov-stats { grid-template-columns: repeat(2,1fr); } }
    .st-ov-stat { background: var(--bg); border: 1px solid var(--border); border-radius: 10px; padding: 12px 14px; }
    .st-ov-stat-lbl { font-size: 10px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; font-family: var(--mono); }
    .st-ov-stat-val { font-size: 18px; font-weight: 600; color: var(--text); font-family: var(--mono); margin-top: 4px; }

    /* ── Collections Table ── */
    .st-section-title { font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .1em; font-family: var(--mono); margin-bottom: 12px; }
    .st-table-wrap { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; }
    table.st-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    table.st-table thead tr { background: var(--bg); border-bottom: 1px solid var(--border2); }
    table.st-table thead th { padding: 11px 16px; text-align: left; font-size: 10px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; font-family: var(--mono); white-space: nowrap; }
    table.st-table tbody tr { border-bottom: 1px solid var(--border); transition: background .1s; }
    table.st-table tbody tr:last-child { border-bottom: none; }
    table.st-table tbody tr:hover { background: rgba(88,166,255,.04); }
    table.st-table td { padding: 12px 16px; vertical-align: middle; }
    .td-name { font-family: var(--mono); font-weight: 600; color: var(--accent); font-size: 13px; }
    .td-num  { font-family: var(--mono); color: var(--text); font-size: 13px; }
    .td-muted{ font-family: var(--mono); color: var(--muted); font-size: 12px; }

    /* Inline bar */
    .st-inline-bar { display: flex; align-items: center; gap: 8px; }
    .st-inline-track { flex: 1; height: 5px; background: var(--border2); border-radius: 99px; overflow: hidden; min-width: 60px; }
    .st-inline-fill  { height: 100%; border-radius: 99px; }

    /* Action buttons */
    .btn-view { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; color: var(--accent); text-decoration: none; padding: 4px 10px; border: 1px solid #1F4E8C; border-radius: 6px; font-family: var(--mono); transition: background .1s; }
    .btn-view:hover { background: #0D2137; }
    .btn-del  { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; color: var(--red); background: none; border: 1px solid #471A1A; border-radius: 6px; padding: 4px 10px; cursor: pointer; font-family: var(--mono); transition: background .1s; }
    .btn-del:hover { background: #2E0D0D; }
    .btn-del:disabled { opacity: .3; cursor: not-allowed; }
    .td-actions { display: flex; align-items: center; gap: 6px; }

    /* Protected badge */
    .badge-protected { font-size: 10px; font-weight: 600; color: var(--muted); background: var(--border); padding: 2px 8px; border-radius: 4px; font-family: var(--mono); }

    /* ── Confirm Modal ── */
    .st-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.7); z-index: 50; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(3px); }
    .st-modal { background: var(--surface); border: 1px solid var(--border2); border-radius: 14px; padding: 28px; max-width: 400px; width: 90%; box-shadow: 0 24px 64px rgba(0,0,0,.5); }
    .st-modal h3 { font-size: 16px; font-weight: 700; color: var(--red); margin: 0 0 8px; font-family: var(--mono); }
    .st-modal p  { font-size: 13px; color: var(--muted); margin: 0 0 20px; line-height: 1.6; }
    .st-modal-code { font-family: var(--mono); font-size: 13px; color: var(--text); background: var(--bg); border: 1px solid var(--border); padding: 8px 12px; border-radius: 7px; display: block; margin-bottom: 20px; }
    .st-modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
    .btn-cancel { font-size: 13px; font-weight: 600; color: var(--muted); background: var(--bg); border: 1px solid var(--border2); padding: 8px 16px; border-radius: 8px; cursor: pointer; font-family: var(--mono); transition: color .1s; }
    .btn-cancel:hover { color: var(--text); }
    .btn-confirm-del { font-size: 13px; font-weight: 600; color: #fff; background: #6E0D0D; border: 1px solid var(--red); padding: 8px 16px; border-radius: 8px; cursor: pointer; font-family: var(--mono); transition: background .1s; }
    .btn-confirm-del:hover { background: #8B1010; }

    /* ── Responsive ── */
    @media(max-width:768px){
        table.st-table thead th:nth-child(3),
        table.st-table thead th:nth-child(4),
        table.st-table td:nth-child(3),
        table.st-table td:nth-child(4) { display: none; }
    }
    </style>

    @php
    // Warna bar berdasarkan persentase
    $barColor = $usedPercent < 60 ? '#3FB950' : ($usedPercent < 85 ? '#D29922' : '#F85149');

    // Total storage seluruh koleksi untuk proporsi inline bar
    $totalCollStorage = array_sum(array_column($collections, 'storage'))
                      + array_sum(array_column($collections, 'index_size'));
    $totalCollStorage = max($totalCollStorage, 1);

    $protected = ['users', 'personal_access_tokens'];
    @endphp

    <div class="st-root">
    <div class="st-wrap">

        {{-- Header --}}
        <div class="st-header">
            <div class="st-header-left">
                <h1>storage_monitor</h1>
                <p>MongoDB Atlas Free Tier · 512 MB limit</p>
            </div>
            <a href="{{ route('admin.storage.index') }}" class="st-refresh">↺ Refresh</a>
        </div>

        {{-- Flash --}}
        @if(session('success'))
        <div class="st-flash success">✓ {{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="st-flash error">✗ {{ session('error') }}</div>
        @endif

        {{-- Storage Overview --}}
        <div class="st-overview">
            <div class="st-ov-top">
                <div>
                    <div class="st-ov-title">Total Pemakaian</div>
                    <div class="st-ov-used">{{ $fmt($totalUsed) }}</div>
                    <div class="st-ov-limit">dari 512.00 MB</div>
                </div>
                <div style="text-align:right">
                    <div class="st-ov-pct" style="color:{{ $barColor }}">{{ $usedPercent }}% terpakai</div>
                    <div style="font-size:12px;color:var(--muted);font-family:var(--mono);margin-top:4px">
                        {{ $fmt(512 * 1024 * 1024 - $totalUsed) }} tersisa
                    </div>
                </div>
            </div>

            <div class="st-bar-track">
                <div class="st-bar-fill" style="width:{{ min($usedPercent, 100) }}%;background:{{ $barColor }}"></div>
            </div>

            <div class="st-ov-stats">
                <div class="st-ov-stat">
                    <div class="st-ov-stat-lbl">Data Size</div>
                    <div class="st-ov-stat-val" style="color:#58A6FF">{{ $fmt($dataSize) }}</div>
                </div>
                <div class="st-ov-stat">
                    <div class="st-ov-stat-lbl">Storage Size</div>
                    <div class="st-ov-stat-val" style="color:#3FB950">{{ $fmt($storageSize) }}</div>
                </div>
                <div class="st-ov-stat">
                    <div class="st-ov-stat-lbl">Index Size</div>
                    <div class="st-ov-stat-val" style="color:#D29922">{{ $fmt($indexSize) }}</div>
                </div>
                <div class="st-ov-stat">
                    <div class="st-ov-stat-lbl">Koleksi</div>
                    <div class="st-ov-stat-val">{{ count($collections) }}</div>
                </div>
            </div>
        </div>

        {{-- Collections --}}
        <div class="st-section-title">koleksi · {{ count($collections) }} total</div>
        <div class="st-table-wrap">
            <table class="st-table">
                <thead>
                    <tr>
                        <th>Nama Koleksi</th>
                        <th>Dokumen</th>
                        <th>Ukuran</th>
                        <th>Avg Doc</th>
                        <th>Proporsi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($collections as $c)
                    @php
                        $collTotal  = $c['storage'] + $c['index_size'];
                        $proportion = round(($collTotal / $totalCollStorage) * 100, 1);
                        $isProtected = in_array($c['name'], $protected);
                        $fillColor  = $proportion > 40 ? '#F85149' : ($proportion > 20 ? '#D29922' : '#58A6FF');
                    @endphp
                    <tr>
                        <td class="td-name">{{ $c['name'] }}</td>
                        <td class="td-num">{{ number_format($c['count']) }}</td>
                        <td class="td-num">{{ $c['size_fmt'] }}</td>
                        <td class="td-muted">{{ $fmt($c['avg_obj']) }}</td>
                        <td>
                            <div class="st-inline-bar">
                                <div class="st-inline-track">
                                    <div class="st-inline-fill" style="width:{{ $proportion }}%;background:{{ $fillColor }}"></div>
                                </div>
                                <span class="td-muted">{{ $proportion }}%</span>
                            </div>
                        </td>
                        <td>
                            <div class="td-actions">
                                <a href="{{ route('admin.storage.show', $c['name']) }}" class="btn-view">Lihat</a>
                                @if(!$isProtected)
                                <button class="btn-del"
                                        onclick="confirmDrop('{{ $c['name'] }}', {{ $c['count'] }})">
                                    Hapus
                                </button>
                                @else
                                <span class="badge-protected">protected</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="padding:32px;text-align:center;color:var(--muted);font-family:var(--mono);">Tidak ada koleksi ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
    </div>

    {{-- Confirm Modal --}}
    <div class="st-modal-overlay" id="dropModal" style="display:none" onclick="closeModal(event)">
        <div class="st-modal">
            <h3>⚠ Hapus Koleksi</h3>
            <p>Tindakan ini akan menghapus <strong id="modalCount" style="color:var(--text)"></strong> dokumen secara permanen dan tidak bisa dibatalkan.</p>
            <code class="st-modal-code" id="modalCode"></code>
            <div class="st-modal-actions">
                <button class="btn-cancel" onclick="document.getElementById('dropModal').style.display='none'">Batal</button>
                <form id="dropForm" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-confirm-del">Ya, Hapus Semua</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    function confirmDrop(name, count) {
        document.getElementById('modalCode').textContent = 'db.' + name + '.drop()';
        document.getElementById('modalCount').textContent = count.toLocaleString();
        document.getElementById('dropForm').action = '/admin/storage/' + name;
        document.getElementById('dropModal').style.display = 'flex';
    }
    function closeModal(e) {
        if (e.target.id === 'dropModal') document.getElementById('dropModal').style.display = 'none';
    }

    // Animate progress bar on load
    document.addEventListener('DOMContentLoaded', () => {
        const fill = document.querySelector('.st-bar-fill');
        if (fill) {
            const w = fill.style.width;
            fill.style.width = '0';
            setTimeout(() => fill.style.width = w, 100);
        }
    });
    </script>
</x-app-layout>