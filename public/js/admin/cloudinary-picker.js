(function () {
    if (window.CloudinaryPicker) return;

    const cfg = window.CloudinaryPickerConfig || {};
    const state = {
        page: 1,
        lastPage: 1,
        selected: null,
        framed: null,
        isFraming: false,
        mode: 'library',
        options: {},
        assets: [],
    };

    const qs = (id) => document.getElementById(id);
    const modal = qs('cloudinaryPicker');
    if (!modal) return;

    const grid = qs('cldPickerGrid');
    const empty = qs('cldPickerEmpty');
    const status = qs('cldPickerStatus');
    const folder = qs('cldPickerFolder');
    const uploadFolder = qs('cldUploadFolder');
    const search = qs('cldPickerSearch');
    const uploadInput = qs('cldUploadInput');
    const uploadPreview = qs('cldUploadPreview');
    const uploadZone = qs('cldUploadZone');
    const btnChoose = qs('cldPickerChoose');
    const btnUpload = qs('cldPickerUpload');
    const btnPrev = qs('cldPickerPrev');
    const btnNext = qs('cldPickerNext');
    const btnBack = qs('cldPickerBack');
    const frameBox = qs('cldFrameBox');
    const frameImage = qs('cldFrameImage');
    const frameOverlay = qs('cldFrameOverlay');
    const frameFocus = qs('cldFrameFocus');
    const frameName = qs('cldFrameName');
    const frameRatio = qs('cldFrameRatio');
    const frameSize = qs('cldFrameSize');
    const framePosition = qs('cldFramePosition');

    function setMode(mode) {
        state.mode = mode;
        document.querySelectorAll('[data-cld-tab]').forEach((btn) => {
            btn.classList.toggle('is-active', btn.dataset.cldTab === mode);
        });
        document.querySelectorAll('[data-cld-panel]').forEach((panel) => {
            panel.style.display = panel.dataset.cldPanel === mode ? '' : 'none';
        });
        btnChoose.style.display = (mode === 'library' || mode === 'frame') ? '' : 'none';
        btnUpload.style.display = mode === 'upload' ? '' : 'none';
        btnPrev.style.display = mode === 'library' ? '' : 'none';
        btnNext.style.display = mode === 'library' ? '' : 'none';
        btnBack.style.display = mode === 'frame' ? '' : 'none';
        btnChoose.textContent = mode === 'frame' ? 'Pakai Frame Ini' : 'Pakai Gambar';
    }

    function open(options) {
        state.options = options || {};
        state.page = 1;
        state.selected = null;
        state.framed = null;
        folder.value = state.options.folder || '';
        uploadFolder.value = state.options.uploadFolder || state.options.folder || uploadFolder.value || 'admin';
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        setMode('library');
        loadAssets();
    }

    function close() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    async function loadAssets() {
        status.textContent = 'Memuat galeri...';
        grid.innerHTML = '';
        empty.style.display = 'none';
        state.selected = null;

        const params = new URLSearchParams({
            page: state.page,
            folder: folder.value || '',
            search: search.value || '',
        });

        try {
            const response = await fetch(`${cfg.pickerUrl}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json();
            state.assets = payload.data || [];
            state.lastPage = payload.last_page || 1;
            renderFolders(payload.folders || []);
            renderAssets();
            status.textContent = `${payload.total || 0} gambar. Halaman ${payload.current_page || 1}/${state.lastPage}.`;
        } catch (error) {
            status.textContent = 'Gagal memuat galeri Cloudinary.';
        }
    }

    function renderFolders(folders) {
        const current = folder.value;
        const uploadCurrent = uploadFolder.value;
        const base = ['admin', 'vehicles', 'landing', 'landing/slides'];
        const merged = Array.from(new Set([...base, ...folders])).filter(Boolean).sort();

        folder.innerHTML = '<option value="">Semua folder</option>' + merged.map((f) => `<option value="${escapeHtml(f)}">${escapeHtml(f)}</option>`).join('');
        uploadFolder.innerHTML = merged.map((f) => `<option value="${escapeHtml(f)}">${escapeHtml(f)}</option>`).join('');

        folder.value = current;
        uploadFolder.value = uploadCurrent || state.options.folder || 'admin';
    }

    function renderAssets() {
        grid.innerHTML = '';
        empty.style.display = state.assets.length ? 'none' : '';
        state.assets.forEach((asset) => {
            const card = document.createElement('button');
            card.type = 'button';
            card.className = 'cld-picker-card';
            card.innerHTML = `
                <div class="cld-picker-thumb"><img src="${escapeAttr(asset.thumb_url || asset.url)}" alt="${escapeAttr(asset.name || '')}" loading="lazy"></div>
                <div class="cld-picker-meta">
                    <div class="cld-picker-name" title="${escapeAttr(asset.name || '')}">${escapeHtml(asset.name || 'Cloudinary asset')}</div>
                    <div class="cld-picker-sub">${escapeHtml(asset.subfolder || '-')} | ${asset.width || '?'}x${asset.height || '?'} | ${escapeHtml(asset.size || '')}</div>
                </div>
                <button type="button" class="cld-picker-delete" title="Hapus gambar">x</button>
            `;
            card.addEventListener('click', () => {
                state.selected = asset;
                document.querySelectorAll('.cld-picker-card.is-selected').forEach((el) => el.classList.remove('is-selected'));
                card.classList.add('is-selected');
                status.textContent = `Dipilih: ${asset.name || asset.public_id || asset.id}`;
            });
            card.querySelector('.cld-picker-delete').addEventListener('click', (event) => {
                event.stopPropagation();
                deleteAsset(asset, card);
            });
            grid.appendChild(card);
        });
    }

    async function deleteAsset(asset, card) {
        if (!confirm('Hapus gambar ini dari Cloudinary?')) return;
        card.style.opacity = '.45';
        try {
            const response = await fetch(cfg.deleteUrlTemplate.replace('__ID__', asset.id), {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': cfg.csrf,
                },
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                alert(payload.message || 'Gagal menghapus gambar.');
                card.style.opacity = '';
                return;
            }
            state.assets = state.assets.filter((item) => item.id !== asset.id);
            renderAssets();
            status.textContent = payload.message || 'Gambar dihapus.';
        } catch (error) {
            card.style.opacity = '';
            alert('Gagal menghapus gambar.');
        }
    }

    function chooseSelected() {
        if (!state.selected) {
            alert('Pilih gambar dulu.');
            return;
        }
        if (state.options.aspect && state.mode !== 'frame') {
            openFrame(state.selected);
            return;
        }

        const selected = state.framed || frameAsset(state.selected);
        if (typeof state.options.onSelect === 'function') {
            state.options.onSelect(selected);
        }
        window.dispatchEvent(new CustomEvent('cloudinary-picker:selected', { detail: selected }));
        close();
    }

    function openFrame(asset) {
        state.selected = asset;
        state.framed = frameAsset(asset);
        setMode('frame');
        renderFrame();
    }

    function frameAsset(asset) {
        const width = Number(state.options.width || asset.targetWidth || 0);
        const height = Number(state.options.height || asset.targetHeight || 0);
        return {
            ...asset,
            frame: {
                name: state.options.label || 'Placeholder',
                aspect: state.options.aspect || (width && height ? `${width}/${height}` : '4/3'),
                width,
                height,
                x: asset.frame?.x || 50,
                y: asset.frame?.y || 50,
            },
        };
    }

    function renderFrame() {
        if (!state.framed) return;
        const frame = state.framed.frame;
        const ratio = parseAspect(frame.aspect);
        frameBox.style.aspectRatio = `${ratio.w} / ${ratio.h}`;
        frameImage.src = state.framed.url;
        frameImage.style.objectPosition = `${frame.x}% ${frame.y}%`;
        frameName.textContent = frame.name;
        frameRatio.textContent = `${ratio.w} : ${ratio.h}`;
        frameSize.textContent = frame.width && frame.height ? `${frame.width} x ${frame.height}px` : 'Mengikuti placeholder';
        updateFrameFocus();
    }

    function parseAspect(value) {
        if (typeof value === 'number') return { w: value, h: 1 };
        const [w, h] = String(value || '4/3').split(/[/:]/).map((part) => Number(part.trim()));
        return { w: w || 4, h: h || 3 };
    }

    function updateFrameFocus() {
        const frame = state.framed.frame;
        frameFocus.style.left = `${frame.x}%`;
        frameFocus.style.top = `${frame.y}%`;
        frameImage.style.objectPosition = `${frame.x}% ${frame.y}%`;
        framePosition.textContent = `X: ${frame.x}% Y: ${frame.y}%`;
    }

    function setFramePoint(event) {
        if (!state.framed) return;
        const rect = frameOverlay.getBoundingClientRect();
        const clientX = event.touches ? event.touches[0].clientX : event.clientX;
        const clientY = event.touches ? event.touches[0].clientY : event.clientY;
        state.framed.frame.x = Math.min(100, Math.max(0, Math.round(((clientX - rect.left) / rect.width) * 100)));
        state.framed.frame.y = Math.min(100, Math.max(0, Math.round(((clientY - rect.top) / rect.height) * 100)));
        updateFrameFocus();
    }

    function previewUploadFiles(files) {
        uploadPreview.innerHTML = '';
        Array.from(files || []).forEach((file) => {
            const reader = new FileReader();
            reader.onload = (event) => {
                const img = document.createElement('img');
                img.src = event.target.result;
                img.alt = file.name;
                uploadPreview.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    }

    async function submitUpload(event) {
        event.preventDefault();
        if (!uploadInput.files.length) {
            alert('Pilih file lokal dulu.');
            return;
        }
        const formData = new FormData();
        Array.from(uploadInput.files).forEach((file) => formData.append('files[]', file));
        formData.append('subfolder', uploadFolder.value || state.options.folder || 'admin');

        btnUpload.disabled = true;
        status.textContent = 'Mengupload ke Cloudinary...';
        try {
            const response = await fetch(cfg.uploadUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': cfg.csrf,
                },
                body: formData,
            });
            const payload = await response.json();
            if (!response.ok) {
                alert(payload.message || (payload.errors || []).join('\n') || 'Upload gagal.');
                return;
            }
            uploadInput.value = '';
            uploadPreview.innerHTML = '';
            status.textContent = payload.message || 'Upload berhasil.';
            setMode('library');
            folder.value = uploadFolder.value;
            state.page = 1;
            await loadAssets();
            if (payload.assets && payload.assets.length === 1) {
                state.selected = payload.assets[0];
                if (state.options.aspect) openFrame(state.selected);
                else chooseSelected();
            }
        } catch (error) {
            alert('Upload gagal.');
        } finally {
            btnUpload.disabled = false;
        }
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, (char) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
        }[char]));
    }

    function escapeAttr(value) {
        return escapeHtml(value);
    }

    document.querySelectorAll('[data-cld-tab]').forEach((btn) => btn.addEventListener('click', () => setMode(btn.dataset.cldTab)));
    document.querySelectorAll('[data-cld-close]').forEach((btn) => btn.addEventListener('click', close));
    modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
    qs('cldPickerRefresh').addEventListener('click', () => { state.page = 1; loadAssets(); });
    folder.addEventListener('change', () => { state.page = 1; uploadFolder.value = folder.value || uploadFolder.value; loadAssets(); });
    search.addEventListener('input', debounce(() => { state.page = 1; loadAssets(); }, 260));
    btnPrev.addEventListener('click', () => { if (state.page > 1) { state.page -= 1; loadAssets(); } });
    btnNext.addEventListener('click', () => { if (state.page < state.lastPage) { state.page += 1; loadAssets(); } });
    btnBack.addEventListener('click', () => setMode('library'));
    btnChoose.addEventListener('click', chooseSelected);
    frameOverlay.addEventListener('mousedown', (event) => { state.isFraming = true; setFramePoint(event); });
    frameOverlay.addEventListener('mousemove', (event) => { if (state.isFraming) setFramePoint(event); });
    frameOverlay.addEventListener('mouseup', () => { state.isFraming = false; });
    frameOverlay.addEventListener('mouseleave', () => { state.isFraming = false; });
    frameOverlay.addEventListener('touchstart', (event) => { event.preventDefault(); state.isFraming = true; setFramePoint(event); });
    frameOverlay.addEventListener('touchmove', (event) => { event.preventDefault(); if (state.isFraming) setFramePoint(event); });
    frameOverlay.addEventListener('touchend', () => { state.isFraming = false; });
    uploadZone.addEventListener('click', () => uploadInput.click());
    uploadZone.addEventListener('dragover', (event) => { event.preventDefault(); uploadZone.classList.add('is-over'); });
    uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('is-over'));
    uploadZone.addEventListener('drop', (event) => {
        event.preventDefault();
        uploadZone.classList.remove('is-over');
        uploadInput.files = event.dataTransfer.files;
        previewUploadFiles(uploadInput.files);
    });
    uploadInput.addEventListener('change', () => previewUploadFiles(uploadInput.files));
    qs('cldPickerUploadForm').addEventListener('submit', submitUpload);

    function debounce(fn, wait) {
        let timer = null;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn(...args), wait);
        };
    }

    window.CloudinaryPicker = { open, close, reload: loadAssets };
})();
