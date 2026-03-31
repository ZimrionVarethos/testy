{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="preload" as="style" href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap">
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap"
          media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap">
    </noscript>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('scripts')


    <script>
        (function() {
            var isDesktop = window.innerWidth >= 1024;
            var stored    = localStorage.getItem('sidebarOpen');
            var isOpen    = isDesktop
                ? (stored === null ? true : stored === 'true')
                : false;

            var margin = !isDesktop ? '0px' : (isOpen ? '13rem' : '4rem');

            // Tulis style awal sebelum Alpine jalan supaya konten tidak geser
            document.write(
                '<style id="sidebar-init-style">' +
                '#main-content { margin-left: ' + margin + ' !important; transition: none !important; }' +
                '#sidebar { ' +
                    (isDesktop
                        ? 'width: ' + (isOpen ? '13rem' : '4rem') + ' !important;'
                        : 'transform: ' + (isOpen ? 'translateX(0)' : 'translateX(-100%)') + ' !important; width: 13rem !important;'
                    ) +
                    'transition: none !important;' +
                '}' +
                '</style>'
            );
        })();
    </script>
</head>
<body class="font-sans antialiased bg-gray-100">

    <div class="flex h-screen overflow-hidden"
         x-data="{
             isDesktop: window.innerWidth >= 1024,
             sidebarOpen: window.innerWidth >= 1024,
             ready: false,
             get marginLeft() {
                 if (!this.isDesktop) return '0px';
                 return this.sidebarOpen ? '13rem' : '4rem';
             },
             toggle() {
                 this.sidebarOpen = !this.sidebarOpen;
                 localStorage.setItem('sidebarOpen', this.sidebarOpen);
             }
         }"
         x-init="
             isDesktop  = window.innerWidth >= 1024;
             var stored = localStorage.getItem('sidebarOpen');
             if (isDesktop) {
                 sidebarOpen = stored === null ? true : stored === 'true';
             } else {
                 sidebarOpen = false;
             }

             window.addEventListener('resize', () => {
                 isDesktop = window.innerWidth >= 1024;
                 if (!isDesktop) sidebarOpen = false;
             });

             {{-- Aktifkan transisi SETELAH Alpine selesai set state awal --}}
             $nextTick(() => {
                 var el = document.getElementById('sidebar-init-style');
                 if (el) el.remove();
                 ready = true;
             });
         ">

        {{-- SIDEBAR --}}
        @include('layouts.navigation')

        {{-- MAIN CONTENT --}}
        <div id="main-content"
             class="flex-1 flex flex-col min-w-0 overflow-hidden"
             :class="ready ? 'transition-all duration-300 ease-in-out' : ''"
             :style="'margin-left: ' + marginLeft">

            {{-- Top Bar --}}
            <header class="flex items-center gap-3 h-16 px-4 bg-white border-b border-gray-200 shrink-0">
                <button @click="toggle()"
                    class="text-gray-500 hover:text-gray-700 p-1.5 rounded-md hover:bg-gray-100 transition-colors focus:outline-none">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                @isset($header)
                    <div class="font-medium text-gray-700">{{ $header }}</div>
                @endisset
            </header>

            {{-- Scrollable Content --}}
            <main class="flex-1 overflow-y-auto">
                {{ $slot }}
            </main>
        </div>
    </div>
    @auth
    @if(Auth::user()->role === 'driver')
    <script>
    (function () {
        if (!navigator.geolocation) return;
    
        const LOCATION_URL = '{{ url("/driver/location") }}';
        const CSRF_TOKEN   = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        const INTERVAL_MS  = 30000;
    
        let isTracking  = false;
        let lastSuccess = false;
    
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
            .then(data => {
                lastSuccess = true;
                hideBanner();
                // SEMENTARA — hapus setelah debug
                alert('OK: ' + JSON.stringify(data));
            })
            .catch(err => {
                // SEMENTARA — hapus setelah debug
                alert('ERROR: ' + err.message);
            });
        }
    
        function tryGetLocation(onSuccess, onFail) {
            alert('Mencoba dapat lokasi...');
            navigator.geolocation.getCurrentPosition(
                pos => {
                    alert('Berhasil: ' + pos.coords.latitude + ', ' + pos.coords.longitude);
                    onSuccess?.(pos.coords.latitude, pos.coords.longitude);
                },
                err => {
                    alert('Gagal kode: ' + err.code + ' | ' + err.message);
                    onFail?.(err);
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }
    
        function startTracking() {
            if (isTracking) return;
            isTracking = true;
    
            tryGetLocation(
                (lat, lon) => sendLocation(lat, lon),
                () => { lastSuccess = false; showBanner('Lokasi gagal didapat. Pastikan GPS aktif.'); }
            );
    
            setInterval(() => {
                tryGetLocation(
                    (lat, lon) => sendLocation(lat, lon),
                    () => { lastSuccess = false; showBanner('Lokasi gagal didapat. Pastikan GPS aktif.'); }
                );
            }, INTERVAL_MS);
        }
    
        function showBanner(subtitle) {
            const existing = document.getElementById('loc-banner');
            if (existing) {
                const sub = document.getElementById('loc-banner-sub');
                if (sub && subtitle) sub.textContent = subtitle;
                return;
            }
    
            const banner = document.createElement('div');
            banner.id = 'loc-banner';
            banner.innerHTML = `
                <div style="
                    position:fixed;bottom:24px;left:50%;transform:translateX(-50%);
                    background:#0F172A;color:white;border-radius:16px;
                    padding:14px 20px;display:flex;align-items:center;gap:12px;
                    font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;
                    box-shadow:0 8px 32px rgba(0,0,0,.4);z-index:9999;
                    max-width:420px;width:calc(100% - 32px);
                ">
                    <span style="font-size:22px;flex-shrink:0">📍</span>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:700;margin-bottom:2px">Aktifkan Lokasi</div>
                        <div id="loc-banner-sub" style="color:#94A3B8;font-size:11px;line-height:1.4">
                            ${subtitle ?? 'Diperlukan untuk tracking pesanan aktif lo.'}
                        </div>
                    </div>
                    <button id="loc-allow-btn" style="
                        background:#4F46E5;color:white;border:none;border-radius:9px;
                        padding:8px 16px;font-size:12px;font-weight:700;cursor:pointer;
                        font-family:'Plus Jakarta Sans',sans-serif;white-space:nowrap;flex-shrink:0;
                    ">Izinkan</button>
                    <button id="loc-dismiss-btn" style="
                        background:none;border:none;color:#64748B;cursor:pointer;
                        font-size:20px;padding:0 2px;line-height:1;flex-shrink:0;
                    ">×</button>
                </div>`;
            document.body.appendChild(banner);
    
            document.getElementById('loc-allow-btn').addEventListener('click', () => {
                hideBanner();
                startTracking();
            });
            document.getElementById('loc-dismiss-btn').addEventListener('click', hideBanner);
        }
    
        function hideBanner() {
            document.getElementById('loc-banner')?.remove();
        }
    
        // ── INIT ──
        if (navigator.permissions && navigator.permissions.query) {
            navigator.permissions.query({ name: 'geolocation' }).then(result => {
                if (result.state === 'granted') {
                    startTracking();
                } else {
                    showBanner();
                }
                result.onchange = () => {
                    if (result.state === 'granted') { hideBanner(); startTracking(); }
                    else if (result.state === 'denied') { showBanner('Lokasi diblokir. Aktifkan di pengaturan browser.'); }
                };
            }).catch(() => showBanner());
        } else {
            // Fallback Safari iOS & browser lama
            showBanner();
        }
    
        // Re-check tiap 60 detik — kalau belum pernah berhasil, tampil banner lagi
        setInterval(() => {
            if (!lastSuccess && !document.getElementById('loc-banner')) {
                showBanner('Belum dapat lokasi. Tap untuk coba lagi.');
            }
        }, 60000);
    
    })();
    </script>
    @endif
    @endauth
</body>
</html>