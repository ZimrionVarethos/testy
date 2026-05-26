let selected = new Set();

function toggleSelect(card) {
    const id = card.dataset.id;
    if (selected.has(id)) { selected.delete(id); card.classList.remove('selected'); }
    else                   { selected.add(id);    card.classList.add('selected'); }
    updateBulkPanel();
}

function clearSelection() {
    selected.clear();
    document.querySelectorAll('.asset-card.selected').forEach(c => c.classList.remove('selected'));
    updateBulkPanel();
}

function toggleSelectAll() {
    const cards = document.querySelectorAll('.asset-card');
    if (selected.size === cards.length) {
        clearSelection();
    } else {
        cards.forEach(c => { selected.add(c.dataset.id); c.classList.add('selected'); });
        updateBulkPanel();
    }
}

function updateBulkPanel() {
    const panel = document.getElementById('bulk-panel');
    document.getElementById('selected-count').textContent = selected.size;
    if (selected.size > 0) panel.removeAttribute('style');
    else panel.setAttribute('style','display:none!important');

    const wrap = document.getElementById('bulk-ids-inputs');
    wrap.innerHTML = '';
    selected.forEach(id => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = id;
        wrap.appendChild(inp);
    });
}

const dz = document.getElementById('drop-zone');
if (dz) {
    dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('drag-over'); });
    dz.addEventListener('dragleave', () => dz.classList.remove('drag-over'));
    dz.addEventListener('drop', e => {
        e.preventDefault(); dz.classList.remove('drag-over');
        const fi = document.getElementById('file-input');
        fi.files = e.dataTransfer.files;
        previewFiles(fi);
    });
}

function previewFiles(input) {
    const preview = document.getElementById('file-preview');
    preview.innerHTML = '';
    const btn = document.getElementById('btn-upload-submit');
    if (!input.files.length) { btn.disabled = true; return; }
    btn.disabled = false;

    Array.from(input.files).forEach(f => {
        const reader = new FileReader();
        reader.onload = e => {
            const col = document.createElement('div');
            col.className = 'col';
            col.innerHTML = `<div class="rounded overflow-hidden border" style="aspect-ratio:1">
                <img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover">
            </div>
            <div class="text-muted text-truncate" style="font-size:.68rem">${f.name}</div>`;
            preview.appendChild(col);
        };
        reader.readAsDataURL(f);
    });
}

document.getElementById('btn-refresh-usage').addEventListener('click', async function() {
    this.disabled = true;
    this.querySelector('i').classList.add('spin');
    try {
        const r = await fetch(window.AssetData.usageRefreshUrl);
        const d = await r.json();
        if (d.error) { alert('Gagal: ' + d.error); return; }

        ['storage', 'bandwidth'].forEach(k => {
            const pct   = k === 'storage' ? d.storage_pct   : d.bandwidth_pct;
            const bytes = k === 'storage' ? d.storage_bytes  : d.bandwidth_bytes;
            const limit = k === 'storage' ? d.storage_limit_bytes : d.bandwidth_limit_bytes;
            document.getElementById(`stat-${k}-pct`).textContent = pct + '%';
            document.getElementById(`bar-${k}`).style.width = pct + '%';
            document.getElementById(`stat-${k}-bytes`).textContent =
                (bytes / 1048576).toFixed(1) + ' MB / ' + (limit / 1048576).toFixed(0) + ' MB';
        });
        document.getElementById('stat-objects').textContent = d.objects?.toLocaleString() ?? '–';
    } finally {
        this.disabled = false;
        this.querySelector('i').classList.remove('spin');
    }
});
