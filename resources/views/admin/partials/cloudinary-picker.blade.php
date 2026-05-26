@once
<style>
    .cld-picker-backdrop {
        position: fixed;
        inset: 0;
        z-index: 1300;
        display: none;
        background: rgba(17, 24, 39, 0.72);
    }
    .cld-picker-backdrop.is-open { display: flex; align-items: center; justify-content: center; padding: 24px; }
    .cld-picker-modal {
        width: min(1120px, 100%);
        max-height: min(760px, calc(100vh - 48px));
        background: #fff;
        border: 1px solid rgba(17, 24, 39, 0.16);
        display: grid;
        grid-template-rows: auto auto 1fr auto;
    }
    .cld-picker-head,
    .cld-picker-tools,
    .cld-picker-foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 16px;
        border-bottom: 1px solid rgba(17, 24, 39, 0.1);
    }
    .cld-picker-title { font-size: 13px; font-weight: 800; color: #111827; text-transform: uppercase; letter-spacing: .08em; }
    .cld-picker-close,
    .cld-picker-btn {
        border: 1px solid rgba(17, 24, 39, 0.16);
        background: #fff;
        color: #111827;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
    }
    .cld-picker-btn.primary { background: #111827; color: #fff; border-color: #111827; }
    .cld-picker-btn.danger { color: #b91c1c; border-color: rgba(185, 28, 28, .28); }
    .cld-picker-tabs { display: flex; gap: 0; }
    .cld-picker-tab {
        border: 1px solid rgba(17, 24, 39, 0.16);
        background: #fff;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
    }
    .cld-picker-tab.is-active { background: #111827; color: #fff; border-color: #111827; }
    .cld-picker-input,
    .cld-picker-select {
        border: 1px solid rgba(17, 24, 39, 0.16);
        padding: 8px 10px;
        font-size: 12px;
        min-height: 36px;
        background: #fff;
    }
    .cld-picker-body { overflow: auto; padding: 16px; background: #f8fbff; }
    .cld-picker-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(148px, 1fr));
        gap: 12px;
    }
    .cld-picker-card {
        position: relative;
        border: 1px solid rgba(17, 24, 39, 0.12);
        background: #fff;
        cursor: pointer;
    }
    .cld-picker-card.is-selected { outline: 3px solid #111827; outline-offset: -3px; }
    .cld-picker-thumb { aspect-ratio: 4 / 3; background: #e5e7eb; overflow: hidden; }
    .cld-picker-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .cld-picker-meta { padding: 8px; }
    .cld-picker-name { font-size: 11px; font-weight: 700; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cld-picker-sub { margin-top: 2px; font-size: 10px; color: rgba(17, 24, 39, .48); }
    .cld-picker-delete {
        position: absolute;
        top: 6px;
        right: 6px;
        border: 0;
        background: rgba(185, 28, 28, .92);
        color: #fff;
        width: 28px;
        height: 28px;
        cursor: pointer;
    }
    .cld-picker-empty { padding: 44px 16px; text-align: center; color: rgba(17, 24, 39, .52); font-size: 13px; }
    .cld-frame-wrap {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 260px;
        gap: 16px;
        align-items: start;
    }
    .cld-frame-stage {
        background: #111827;
        border: 1px solid rgba(17, 24, 39, 0.16);
        min-height: 420px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        overflow: hidden;
    }
    .cld-frame-box {
        position: relative;
        width: min(100%, 720px);
        max-height: 520px;
        background: #0b1220;
        overflow: hidden;
        border: 2px solid #fff;
    }
    .cld-frame-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        user-select: none;
        pointer-events: none;
    }
    .cld-frame-overlay {
        position: absolute;
        inset: 0;
        cursor: crosshair;
        background:
            linear-gradient(to right, rgba(255,255,255,.28) 1px, transparent 1px) 33.33% 0 / 33.33% 100%,
            linear-gradient(to bottom, rgba(255,255,255,.28) 1px, transparent 1px) 0 33.33% / 100% 33.33%;
    }
    .cld-frame-focus {
        position: absolute;
        width: 18px;
        height: 18px;
        border: 2px solid #fff;
        background: rgba(255,255,255,.22);
        transform: translate(-50%, -50%);
        pointer-events: none;
    }
    .cld-frame-focus::before,
    .cld-frame-focus::after {
        content: "";
        position: absolute;
        background: #fff;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
    }
    .cld-frame-focus::before { width: 44px; height: 1px; }
    .cld-frame-focus::after { width: 1px; height: 44px; }
    .cld-frame-side {
        background: #fff;
        border: 1px solid rgba(17, 24, 39, 0.12);
        padding: 14px;
    }
    .cld-frame-label { font-size: 11px; font-weight: 800; color: #111827; text-transform: uppercase; letter-spacing: .06em; }
    .cld-frame-value { margin-top: 4px; font-size: 12px; color: rgba(17, 24, 39, .58); }
    @media (max-width: 820px) {
        .cld-frame-wrap { grid-template-columns: 1fr; }
        .cld-frame-stage { min-height: 300px; padding: 12px; }
    }
    .cld-upload-zone {
        border: 2px dashed rgba(17, 24, 39, 0.18);
        background: #fff;
        padding: 38px 18px;
        text-align: center;
        cursor: pointer;
    }
    .cld-upload-zone.is-over { border-color: #111827; background: #eff6ff; }
    .cld-upload-preview { display: grid; grid-template-columns: repeat(auto-fill, minmax(112px, 1fr)); gap: 10px; margin-top: 12px; }
    .cld-upload-preview img { width: 100%; aspect-ratio: 1; object-fit: cover; border: 1px solid rgba(17, 24, 39, 0.12); }
    .cld-picker-foot { border-top: 1px solid rgba(17, 24, 39, 0.1); border-bottom: 0; }
    .cld-picker-status { font-size: 12px; color: rgba(17, 24, 39, .58); }
</style>

<div class="cld-picker-backdrop" id="cloudinaryPicker" aria-hidden="true">
    <div class="cld-picker-modal" role="dialog" aria-modal="true" aria-labelledby="cldPickerTitle">
        <div class="cld-picker-head">
            <div>
                <div class="cld-picker-title" id="cldPickerTitle">Cloudinary Image Manager</div>
                <div class="cld-picker-status" id="cldPickerSubtitle">Pilih gambar tersimpan atau upload lokal.</div>
            </div>
            <button type="button" class="cld-picker-close" data-cld-close>Tutup</button>
        </div>

        <div class="cld-picker-tools">
            <div class="cld-picker-tabs">
                <button type="button" class="cld-picker-tab is-active" data-cld-tab="library">Galeri</button>
                <button type="button" class="cld-picker-tab" data-cld-tab="upload">Upload Lokal</button>
            </div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <select class="cld-picker-select" id="cldPickerFolder">
                    <option value="">Semua folder</option>
                </select>
                <input class="cld-picker-input" id="cldPickerSearch" type="search" placeholder="Cari nama file">
                <button type="button" class="cld-picker-btn" id="cldPickerRefresh">Refresh</button>
            </div>
        </div>

        <div class="cld-picker-body">
            <div data-cld-panel="library">
                <div class="cld-picker-grid" id="cldPickerGrid"></div>
                <div class="cld-picker-empty" id="cldPickerEmpty" style="display:none">Belum ada gambar.</div>
            </div>
            <div data-cld-panel="upload" style="display:none">
                <form id="cldPickerUploadForm">
                    <select class="cld-picker-select" name="subfolder" id="cldUploadFolder" style="margin-bottom:12px">
                        <option value="admin">admin</option>
                        <option value="vehicles">vehicles</option>
                        <option value="landing">landing</option>
                        <option value="landing/slides">landing/slides</option>
                    </select>
                    <div class="cld-upload-zone" id="cldUploadZone">
                        <strong>Klik atau drag gambar lokal ke sini</strong>
                        <div class="cld-picker-status" style="margin-top:6px">JPG, PNG, WEBP. Maks 5 MB per file.</div>
                    </div>
                    <input type="file" name="files[]" id="cldUploadInput" accept="image/*" multiple hidden>
                    <div class="cld-upload-preview" id="cldUploadPreview"></div>
                </form>
            </div>
            <div data-cld-panel="frame" style="display:none">
                <div class="cld-frame-wrap">
                    <div class="cld-frame-stage">
                        <div class="cld-frame-box" id="cldFrameBox">
                            <img id="cldFrameImage" alt="">
                            <div class="cld-frame-overlay" id="cldFrameOverlay">
                                <div class="cld-frame-focus" id="cldFrameFocus"></div>
                            </div>
                        </div>
                    </div>
                    <div class="cld-frame-side">
                        <div class="cld-frame-label">Frame Target</div>
                        <div class="cld-frame-value" id="cldFrameName">Placeholder</div>
                        <div class="cld-frame-label" style="margin-top:18px">Rasio</div>
                        <div class="cld-frame-value" id="cldFrameRatio">-</div>
                        <div class="cld-frame-label" style="margin-top:18px">Ukuran Tampil</div>
                        <div class="cld-frame-value" id="cldFrameSize">-</div>
                        <div class="cld-frame-label" style="margin-top:18px">Fokus</div>
                        <div class="cld-frame-value" id="cldFramePosition">X: 50% Y: 50%</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="cld-picker-foot">
            <div class="cld-picker-status" id="cldPickerStatus">Memuat galeri...</div>
            <div style="display:flex;gap:8px">
                <button type="button" class="cld-picker-btn" id="cldPickerPrev">Prev</button>
                <button type="button" class="cld-picker-btn" id="cldPickerNext">Next</button>
                <button type="button" class="cld-picker-btn" id="cldPickerBack">Kembali</button>
                <button type="button" class="cld-picker-btn primary" id="cldPickerChoose">Pakai Gambar</button>
                <button type="submit" form="cldPickerUploadForm" class="cld-picker-btn primary" id="cldPickerUpload">Upload</button>
            </div>
        </div>
    </div>
</div>

<script>
window.CloudinaryPickerConfig = {
    pickerUrl: @json(route('admin.assets.picker')),
    uploadUrl: @json(route('admin.assets.store')),
    deleteUrlTemplate: @json(route('admin.assets.destroy', '__ID__')),
    csrf: @json(csrf_token()),
};
</script>
<script src="{{ asset('js/admin/cloudinary-picker.js') }}"></script>
@endonce
