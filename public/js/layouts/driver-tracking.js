(function () {
    if (!navigator.geolocation) return;

    const LOCATION_URL  = window.DriverConfig.locationUrl;
    const CSRF_TOKEN    = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const INTERVAL_MS   = 30000;
    const STORAGE_KEY   = 'driver_loc_granted';

    let isTracking    = false;
    let lastSuccess   = false;
    let userDismissed = false;

    function sendLocation(lat, lon) {
        fetch(LOCATION_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept'      : 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            credentials: 'same-origin',
            body: JSON.stringify({ lat, lon }),
        })
        .then(r => r.json())
        .then(() => { lastSuccess = true; localStorage.setItem(STORAGE_KEY, '1'); hideBanner(); })
        .catch(() => {});
    }

    function tryGetLocation(onSuccess, onFail) {
        navigator.geolocation.getCurrentPosition(
            pos => onSuccess?.(pos.coords.latitude, pos.coords.longitude),
            err => onFail?.(err),
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    }

    function startTracking() {
        if (isTracking) return;
        isTracking = true;
        tryGetLocation(
            (lat, lon) => sendLocation(lat, lon),
            () => { lastSuccess = false; if (!localStorage.getItem(STORAGE_KEY)) showBanner('Lokasi gagal didapat. Pastikan GPS aktif.'); }
        );
        setInterval(() => {
            tryGetLocation((lat, lon) => sendLocation(lat, lon), () => { lastSuccess = false; });
        }, INTERVAL_MS);
    }

    function showBanner(subtitle) {
        const existing = document.getElementById('loc-banner');
        if (existing) { const sub = document.getElementById('loc-banner-sub'); if (sub && subtitle) sub.textContent = subtitle; return; }
        const banner = document.createElement('div');
        banner.id = 'loc-banner';
        banner.innerHTML = `
            <div style="position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:var(--dark);color:white;border-radius:14px;padding:14px 18px;display:flex;align-items:center;gap:12px;font-family:'DM Sans',sans-serif;font-size:13px;box-shadow:0 8px 32px rgba(0,0,0,.35);z-index:9999;max-width:400px;width:calc(100% - 32px);">
                <div style="width:34px;height:34px;border-radius:9px;background:rgba(255,255,255,0.08);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5"/></svg>
                </div>
                <div style="flex:1;min-width:0">
                    <div style="font-weight:700;font-family:'Epilogue',sans-serif;font-size:13px;margin-bottom:2px">Aktifkan Lokasi</div>
                    <div id="loc-banner-sub" style="color:rgba(255,255,255,0.5);font-size:11px;line-height:1.4">${subtitle ?? 'Diperlukan untuk tracking pesanan aktif.'}</div>
                </div>
                <button id="loc-allow-btn" style="background:white;color:var(--dark);border:none;border-radius:8px;padding:7px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;white-space:nowrap;flex-shrink:0">Izinkan</button>
                <button id="loc-dismiss-btn" style="background:none;border:none;color:rgba(255,255,255,0.35);cursor:pointer;font-size:18px;padding:0 2px;line-height:1;flex-shrink:0">×</button>
            </div>`;
        document.body.appendChild(banner);
        document.getElementById('loc-allow-btn').addEventListener('click', () => { hideBanner(); startTracking(); });
        document.getElementById('loc-dismiss-btn').addEventListener('click', () => { userDismissed = true; hideBanner(); });
    }

    function hideBanner() { document.getElementById('loc-banner')?.remove(); }

    const alreadyGranted = localStorage.getItem(STORAGE_KEY);
    if (alreadyGranted) {
        startTracking();
    } else if (navigator.permissions && navigator.permissions.query) {
        navigator.permissions.query({ name: 'geolocation' }).then(result => {
            if (result.state === 'granted') { startTracking(); } else { showBanner(); }
            result.onchange = () => {
                if (result.state === 'granted') { hideBanner(); startTracking(); }
                else if (result.state === 'denied') { localStorage.removeItem(STORAGE_KEY); showBanner('Lokasi diblokir. Aktifkan di pengaturan browser.'); }
            };
        }).catch(() => showBanner());
    } else {
        showBanner();
    }
})();
