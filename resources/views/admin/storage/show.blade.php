<x-app-layout>
    <x-slot name="header">Storage · {{ $collection }}</x-slot>

    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
    :root {
        --bg:      #0D1117; --surface: #161B22; --border: #21262D; --border2: #30363D;
        --text:    #E6EDF3; --muted:   #7D8590; --accent: #58A6FF;
        --green:   #3FB950; --yellow:  #D29922; --red:    #F85149;
        --mono:    'IBM Plex Mono', monospace; --sans: 'IBM Plex Sans', sans-serif;
    }
    .st-root *, .st-root *::before, .st-root *::after { box-sizing: border-box; }
    .st-root { font-family: var(--sans); background: var(--bg); min-height: calc(100vh - 64px); padding: 28px 0; color: var(--text); }
    .st-wrap { max-width: 1200px; margin: 0 auto; padding: 0 28px; }

    .st-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 24px; gap: 12px; flex-wrap: wrap; }
    .st-back { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: var(--accent); text-decoration: none; border: 1px solid #1F4E8C; background: #0D2137; padding: 7px 14px; border-radius: 8px; font-family: var(--mono); transition: background .15s; }
    .st-back:hover { background: #112D4E; }
    .st-title { font-size: 20px; font-weight: 700; color: var(--text); font-family: var(--mono); margin: 0; }
    .st-subtitle { font-size: 13px; color: var(--muted); margin: 4px 0 0; font-family: var(--mono); }

    /* Flash */
    .st-flash { padding: 10px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; margin-bottom: 20px; }
    .st-flash.success { background: #0D2E1A; border: 1px solid #1A4731; color: var(--green); }

    /* Info bar */
    .st-infobar { display: flex; align-items: center; gap: 16px; background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: 12px 18px; margin-bottom: 20px; flex-wrap: wrap; }
    .st-infobar-item { font-size: 12px; font-family: var(--mono); color: var(--muted); }
    .st-infobar-item strong { color: var(--text); font-weight: 600; }

    /* Doc cards */
    .st-doc-list { display: flex; flex-direction: column; gap: 10px; }
    .st-doc-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; transition: border-color .15s; }
    .st-doc-card:hover { border-color: var(--border2); }
    .st-doc-header { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; cursor: pointer; gap: 12px; }
    .st-doc-id { font-size: 12px; font-weight: 600; color: var(--accent); font-family: var(--mono); }
    .st-doc-preview { font-size: 11px; color: var(--muted); font-family: var(--mono); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; min-width: 0; }
    .st-doc-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
    .st-toggle { font-size: 11px; color: var(--muted); font-family: var(--mono); cursor: pointer; padding: 3px 8px; border: 1px solid var(--border2); border-radius: 5px; background: none; transition: color .1s; }
    .st-toggle:hover { color: var(--text); }
    .btn-del-doc { font-size: 11px; font-weight: 600; color: var(--red); background: none; border: 1px solid #471A1A; border-radius: 6px; padding: 4px 10px; cursor: pointer; font-family: var(--mono); transition: background .1s; }
    .btn-del-doc:hover { background: #2E0D0D; }

    /* JSON viewer */
    .st-doc-body { display: none; padding: 0 16px 16px; }
    .st-doc-body.open { display: block; }
    .st-json { background: var(--bg); border: 1px solid var(--border); border-radius: 8px; padding: 14px; font-size: 12px; font-family: var(--mono); color: var(--text); overflow-x: auto; white-space: pre; line-height: 1.7; max-height: 400px; overflow-y: auto; }
    /* JSON syntax colors */
    .jk { color: #79C0FF; }  /* key */
    .js { color: #A5D6FF; }  /* string value */
    .jn { color: #F0883E; }  /* number */
    .jb { color: #D2A8FF; }  /* bool/null */

    /* Pagination */
    .st-pagination { display: flex; align-items: center; justify-content: space-between; margin-top: 20px; flex-wrap: wrap; gap: 10px; }
    .st-page-info { font-size: 12px; color: var(--muted); font-family: var(--mono); }
    .st-page-btns { display: flex; gap: 6px; }
    .st-page-btn { font-size: 12px; font-weight: 600; color: var(--accent); text-decoration: none; border: 1px solid #1F4E8C; background: var(--surface); padding: 6px 14px; border-radius: 7px; font-family: var(--mono); transition: background .15s; }
    .st-page-btn:hover { background: #0D2137; }
    .st-page-btn.disabled { color: var(--muted); border-color: var(--border); pointer-events: none; }
    .st-page-current { font-size: 12px; color: var(--text); font-family: var(--mono); padding: 6px 14px; background: var(--border); border-radius: 7px; }

    /* Modal */
    .st-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.7); z-index: 50; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(3px); }
    .st-modal { background: var(--surface); border: 1px solid var(--border2); border-radius: 14px; padding: 28px; max-width: 400px; width: 90%; box-shadow: 0 24px 64px rgba(0,0,0,.5); }
    .st-modal h3 { font-size: 16px; font-weight: 700; color: var(--red); margin: 0 0 8px; font-family: var(--mono); }
    .st-modal p  { font-size: 13px; color: var(--muted); margin: 0 0 20px; line-height: 1.6; }
    .st-modal-code { font-family: var(--mono); font-size: 12px; color: var(--text); background: var(--bg); border: 1px solid var(--border); padding: 8px 12px; border-radius: 7px; display: block; margin-bottom: 20px; word-break: break-all; }
    .st-modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
    .btn-cancel { font-size: 13px; font-weight: 600; color: var(--muted); background: var(--bg); border: 1px solid var(--border2); padding: 8px 16px; border-radius: 8px; cursor: pointer; font-family: var(--mono); }
    .btn-cancel:hover { color: var(--text); }
    .btn-confirm-del { font-size: 13px; font-weight: 600; color: #fff; background: #6E0D0D; border: 1px solid var(--red); padding: 8px 16px; border-radius: 8px; cursor: pointer; font-family: var(--mono); }
    .btn-confirm-del:hover { background: #8B1010; }
    </style>

    @php
    // Helper: buat preview singkat dari doc array
    function docPreview(array $doc): string {
        $skip = ['_id','updated_at','created_at','password'];
        $parts = [];
        foreach ($doc as $k => $v) {
            if (in_array($k, $skip)) continue;
            if (is_array($v)) continue;
            $parts[] = "$k: " . (is_string($v) ? "\"$v\"" : json_encode($v));
            if (count($parts) >= 3) break;
        }
        return implode('  ·  ', $parts) ?: '(kosong)';
    }
    @endphp

    <div class="st-root">
    <div class="st-wrap">

        {{-- Header --}}
        <div class="st-header">
            <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                <a href="{{ route('admin.storage.index') }}" class="st-back">← Storage</a>
                <div>
                    <div class="st-title">{{ $collection }}</div>
                    <div class="st-subtitle">{{ number_format($total) }} dokumen total</div>
                </div>
            </div>
        </div>

        {{-- Flash --}}
        @if(session('success'))
        <div class="st-flash success">✓ {{ session('success') }}</div>
        @endif

        {{-- Info bar --}}
        <div class="st-infobar">
            <div class="st-infobar-item">Halaman <strong>{{ $page }}</strong> dari <strong>{{ $totalPages }}</strong></div>
            <div class="st-infobar-item">Menampilkan <strong>{{ count($docs) }}</strong> dari <strong>{{ number_format($total) }}</strong> dokumen</div>
            <div class="st-infobar-item">Urutan: <strong>_id desc</strong></div>
        </div>

        {{-- Document list --}}
        <div class="st-doc-list">
            @forelse($docs as $doc)
            @php $docId = $doc['_id']['$oid'] ?? ($doc['_id'] ?? '?'); @endphp
            <div class="st-doc-card" id="card-{{ $loop->index }}">
                <div class="st-doc-header" onclick="toggleDoc({{ $loop->index }})">
                    <span class="st-doc-id">{{ $docId }}</span>
                    <span class="st-doc-preview">{{ docPreview($doc) }}</span>
                    <div class="st-doc-actions" onclick="event.stopPropagation()">
                        <button class="st-toggle" onclick="toggleDoc({{ $loop->index }})">JSON</button>
                        <button class="btn-del-doc" onclick="confirmDelDoc('{{ $docId }}')">Hapus</button>
                    </div>
                </div>
                <div class="st-doc-body" id="body-{{ $loop->index }}">
                    <div class="st-json" id="json-{{ $loop->index }}"></div>
                </div>
            </div>
            @php
                // Store docs as JSON for JS rendering
                echo '<script>window.__docs = window.__docs || {}; window.__docs["' . $loop->index . '"] = ' . json_encode($doc) . ';</script>';
            @endphp
            @empty
            <div style="padding:48px;text-align:center;color:var(--muted);font-family:var(--mono);background:var(--surface);border:1px solid var(--border);border-radius:12px;">
                Koleksi ini kosong.
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($totalPages > 1)
        <div class="st-pagination">
            <span class="st-page-info">{{ number_format($total) }} dokumen · halaman {{ $page }}/{{ $totalPages }}</span>
            <div class="st-page-btns">
                @if($page > 1)
                    <a href="{{ route('admin.storage.show', [$collection, 'page' => $page - 1]) }}" class="st-page-btn">← Prev</a>
                @else
                    <span class="st-page-btn disabled">← Prev</span>
                @endif
                <span class="st-page-current">{{ $page }}</span>
                @if($page < $totalPages)
                    <a href="{{ route('admin.storage.show', [$collection, 'page' => $page + 1]) }}" class="st-page-btn">Next →</a>
                @else
                    <span class="st-page-btn disabled">Next →</span>
                @endif
            </div>
        </div>
        @endif

    </div>
    </div>

    {{-- Delete single doc modal --}}
    <div class="st-modal-overlay" id="delDocModal" style="display:none" onclick="closeModal(event)">
        <div class="st-modal">
            <h3>Hapus Dokumen</h3>
            <p>Dokumen ini akan dihapus permanen.</p>
            <code class="st-modal-code" id="delDocCode"></code>
            <div class="st-modal-actions">
                <button class="btn-cancel" onclick="document.getElementById('delDocModal').style.display='none'">Batal</button>
                <form id="delDocForm" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-confirm-del">Hapus</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    // ── Toggle JSON viewer ──
    const openStates = {};
    function toggleDoc(i) {
        const body = document.getElementById('body-' + i);
        const json = document.getElementById('json-' + i);
        if (!openStates[i]) {
            openStates[i] = true;
            body.classList.add('open');
            if (!json.dataset.rendered) {
                json.innerHTML = syntaxHighlight(window.__docs[i]);
                json.dataset.rendered = '1';
            }
        } else {
            openStates[i] = false;
            body.classList.remove('open');
        }
    }

    // ── Syntax highlight JSON ──
    function syntaxHighlight(obj) {
        const json = JSON.stringify(obj, null, 2);
        return json.replace(/("(\\u[\dA-Fa-f]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+\.?\d*(?:[eE][+\-]?\d+)?)/g, match => {
            if (/^"/.test(match)) {
                if (/:$/.test(match)) return '<span class="jk">' + match + '</span>';
                return '<span class="js">' + match + '</span>';
            }
            if (/true|false|null/.test(match)) return '<span class="jb">' + match + '</span>';
            return '<span class="jn">' + match + '</span>';
        });
    }

    // ── Confirm delete single doc ──
    function confirmDelDoc(id) {
        document.getElementById('delDocCode').textContent = 'db.{{ $collection }}.deleteOne({ _id: ObjectId("' + id + '") })';
        document.getElementById('delDocForm').action = '/admin/storage/{{ $collection }}/' + id;
        document.getElementById('delDocModal').style.display = 'flex';
    }
    function closeModal(e) {
        if (e.target.classList.contains('st-modal-overlay'))
            e.target.style.display = 'none';
    }
    </script>
</x-app-layout>