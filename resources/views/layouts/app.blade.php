{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'FleetAdmin') }}</title>

    {{-- Fonts: Epilogue (display) + DM Sans (body) + DM Mono (data) --}}
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="preload" as="style"
          href="https://fonts.bunny.net/css?family=epilogue:400,500,600,700,800|dm-sans:400,500|dm-mono:400,500&display=swap">
    <link rel="stylesheet"
          href="https://fonts.bunny.net/css?family=epilogue:400,500,600,700,800|dm-sans:400,500|dm-mono:400,500&display=swap"
          media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet"
              href="https://fonts.bunny.net/css?family=epilogue:400,500,600,700,800|dm-sans:400,500|dm-mono:400,500&display=swap">
    </noscript>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head-scripts')

    <style>
        /* ── Design tokens ── */
        :root {
            --bg:        #F7F7F5;
            --dark:      rgb(17, 24, 39);
            --white:     #FFFFFF;
            --border:    rgba(17, 24, 39, 0.08);
            --border-md: rgba(17, 24, 39, 0.14);
            --text-1:    rgb(17, 24, 39);
            --text-2:    rgba(17, 24, 39, 0.55);
            --text-3:    rgba(17, 24, 39, 0.32);

            /* status semantics */
            --s-green:   #16a34a; --s-green-bg:  #f0fdf4; --s-green-text: #14532d;
            --s-amber:   #d97706; --s-amber-bg:  #fffbeb; --s-amber-text: #78350f;
            --s-blue:    #2563eb; --s-blue-bg:   #eff6ff; --s-blue-text:  #1e3a8a;
            --s-red:     #dc2626; --s-red-bg:    #fef2f2; --s-red-text:   #7f1d1d;
            --s-gray:    #64748b; --s-gray-bg:   #f8fafc; --s-gray-text:  #1e293b;
            --s-violet:  #7c3aed; --s-violet-bg: #f5f3ff; --s-violet-text:#3b0764;
        }

        /* ── Global ── */
        *, *::before, *::after { box-sizing: border-box; }
        html { font-size: 14px; }
        body {
            font-family: 'DM Sans', sans-serif !important;
            background: var(--bg) !important;
            color: var(--text-1);
        }
        .font-display { font-family: 'Epilogue', sans-serif; }
        .font-mono-data { font-family: 'DM Mono', monospace; }

        /* ── Header ── */
        #app-header {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            height: 56px;
            display: flex;
            align-items: center;
            padding: 0 16px;
            gap: 8px;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .header-icon-btn {
            width: 34px; height: 34px;
            border-radius: 9px;
            border: none;
            background: transparent;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            color: var(--text-2);
            transition: background 0.15s, color 0.15s;
            flex-shrink: 0;
        }
        .header-icon-btn:hover { background: var(--bg); color: var(--text-1); }
        .header-page-title {
            font-family: 'Epilogue', sans-serif;
            font-size: 14px;
            font-weight: 700;
            color: var(--text-1);
        }

        /* ── Notification Bell ── */
        .notif-bell-wrap { position: relative; }
        .notif-unread-dot {
            position: absolute;
            top: 6px; right: 6px;
            width: 7px; height: 7px;
            border-radius: 50%;
            background: var(--s-red);
            border: 1.5px solid var(--white);
        }

        /* ── Notification Dropdown ── */
        .notif-panel {
            position: absolute;
            top: calc(100% + 8px);
            right: -4px;
            width: 340px;
            background: var(--white);
            border: 1px solid var(--border-md);
            border-radius: 14px;
            box-shadow: 0 12px 40px rgba(17,24,39,0.13);
            overflow: hidden;
            z-index: 200;
        }
        .notif-panel-header {
            padding: 13px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .notif-panel-title {
            font-family: 'Epilogue', sans-serif;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-1);
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .notif-count-pill {
            background: var(--s-red);
            color: white;
            font-size: 10px;
            font-weight: 700;
            font-family: 'DM Mono', monospace;
            padding: 1px 6px;
            border-radius: 99px;
            line-height: 1.6;
        }
        .notif-mark-all-btn {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-3);
            border: none;
            background: none;
            cursor: pointer;
            padding: 0;
            font-family: 'DM Sans', sans-serif;
            transition: color 0.15s;
        }
        .notif-mark-all-btn:hover { color: var(--text-1); }

        .notif-list { max-height: 340px; overflow-y: auto; }

        /* notif-item sekarang <a> — reset default anchor styles */
        .notif-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 11px 16px;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            transition: background 0.1s;
            text-decoration: none;
            color: inherit;
        }
        .notif-item:last-child { border-bottom: none; }
        .notif-item:hover { background: var(--bg); }
        .notif-item.is-unread { background: rgba(239,68,68,0.025); }
        .notif-item.no-link { cursor: default; }
        .notif-item-icon {
            width: 30px; height: 30px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .notif-item-icon svg { width: 13px; height: 13px; }
        .notif-item-body { flex: 1; min-width: 0; }
        .notif-item-title {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-1);
            line-height: 1.35;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .notif-item-msg {
            font-size: 11px;
            color: var(--text-2);
            margin-top: 2px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .notif-item-time {
            font-size: 10px;
            color: var(--text-3);
            margin-top: 3px;
        }
        .notif-unread-marker {
            width: 5px; height: 5px;
            border-radius: 50%;
            background: var(--s-red);
            flex-shrink: 0;
            margin-top: 6px;
        }

        .notif-empty {
            padding: 28px 16px;
            text-align: center;
        }
        .notif-empty-icon {
            width: 36px; height: 36px;
            margin: 0 auto 10px;
            background: var(--bg);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        .notif-empty-icon svg { width: 18px; height: 18px; color: var(--text-3); }
        .notif-empty-text { font-size: 12px; color: var(--text-3); }

        .notif-panel-footer {
            padding: 10px 16px;
            border-top: 1px solid var(--border);
            text-align: center;
        }
        .notif-panel-footer a {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-3);
            text-decoration: none;
            transition: color 0.15s;
        }
        .notif-panel-footer a:hover { color: var(--text-1); }

        /* ── User chip (header) ── */
        .header-user-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 10px 4px 4px;
            border-radius: 99px;
            cursor: default;
            transition: background 0.15s;
        }
        .header-user-chip:hover { background: var(--bg); }
        .header-avatar {
            width: 28px; height: 28px;
            border-radius: 50%;
            background: var(--dark);
            color: white;
            font-family: 'Epilogue', sans-serif;
            font-size: 10px;
            font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            letter-spacing: 0.03em;
        }
        .header-user-name {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-1);
            max-width: 100px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(17,24,39,0.10); border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(17,24,39,0.20); }
    </style>

    {{-- Sidebar FOUC prevention (keep as-is) --}}
    <script>
        (function() {
            var isDesktop = window.innerWidth >= 1024;
            var stored    = localStorage.getItem('sidebarOpen');
            var isOpen    = isDesktop
                ? (stored === null ? true : stored === 'true')
                : false;
            var margin = !isDesktop ? '0px' : (isOpen ? '13rem' : '4rem');
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
<body class="antialiased">

    {{-- ── Notification data (global, all authenticated users) ── --}}
    @auth
    @php
        try {
            $__notifItems  = \App\Models\Notification::where('user_id', (string) Auth::id())
                ->orderBy('created_at', 'desc')
                ->limit(12)
                ->get();
            $__unreadCount = $__notifItems->where('is_read', false)->count();
        } catch (\Exception $e) {
            $__notifItems  = collect();
            $__unreadCount = 0;
        }

        // Helper: resolve action_url fallback berdasarkan type & role
        $__role = Auth::user()->role ?? 'pengguna';
    @endphp
    @else
    @php $__notifItems = collect(); $__unreadCount = 0; $__role = null; @endphp
    @endauth

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

            {{-- ── APP HEADER ── --}}
            <header id="app-header">

                {{-- Hamburger --}}
                <button @click="toggle()" class="header-icon-btn" aria-label="Toggle sidebar">
                    <svg width="17" height="17" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                        <line x1="1" y1="4"  x2="16" y2="4"/>
                        <line x1="1" y1="8.5" x2="16" y2="8.5"/>
                        <line x1="1" y1="13" x2="16" y2="13"/>
                    </svg>
                </button>

                {{-- Page title slot --}}
                @isset($header)
                <div class="header-page-title flex-1">{{ $header }}</div>
                @else
                <div class="flex-1"></div>
                @endisset

                {{-- Right actions --}}
                <div style="display:flex;align-items:center;gap:2px">

                    {{-- ── NOTIFICATION BELL ── --}}
                    @auth
                    <div class="notif-bell-wrap" x-data="{ open: false }">

                        <button class="header-icon-btn" @click="open = !open" aria-label="Notifikasi">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                <path d="M13.73 21a2 2 0 01-3.46 0"/>
                            </svg>
                            @if($__unreadCount > 0)
                                <span class="notif-unread-dot"></span>
                            @endif
                        </button>

                        {{-- Dropdown Panel --}}
                        <div class="notif-panel"
                             x-show="open"
                             @click.outside="open = false"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             style="display:none">

                            {{-- Header --}}
                            <div class="notif-panel-header">
                                <span class="notif-panel-title">
                                    Notifikasi
                                    @if($__unreadCount > 0)
                                    <span class="notif-count-pill">{{ $__unreadCount }}</span>
                                    @endif
                                </span>
                                @if($__unreadCount > 0 && Route::has('notifications.read-all'))
                                <form method="POST" action="{{ route('notifications.read-all') }}" style="margin:0">
                                    @csrf
                                    <button type="submit" class="notif-mark-all-btn">Tandai dibaca</button>
                                </form>
                                @endif
                            </div>

                            {{-- List --}}
                            <div class="notif-list">
                                @forelse($__notifItems as $__notif)
                                @php
                                    $__cfg = match($__notif->type ?? 'info') {
                                        'booking'  => ['bg'=>'#eff6ff', 'color'=>'#2563eb',
                                            'path'=>'<path d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/>'],
                                        'payment'  => ['bg'=>'#f0fdf4', 'color'=>'#16a34a',
                                            'path'=>'<line x1="8" y1="1" x2="8" y2="15"/><path d="M11 4H6.5a2.5 2.5 0 000 5h3a2.5 2.5 0 010 5H4"/>'],
                                        'warning'  => ['bg'=>'#fffbeb', 'color'=>'#d97706',
                                            'path'=>'<path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>'],
                                        'success'  => ['bg'=>'#f0fdf4', 'color'=>'#16a34a',
                                            'path'=>'<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                                        default    => ['bg'=>'rgba(17,24,39,0.05)', 'color'=>'rgba(17,24,39,0.45)',
                                            'path'=>'<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'],
                                    };
                                    $__isUnread = !($__notif->is_read ?? true);

                                    // Resolve URL: pakai action_url jika ada, fallback berdasarkan type + role
                                    $__notifUrl = $__notif->action_url ?? null;
                                    if (!$__notifUrl && $__notif->related_id) {
                                        $__notifUrl = match($__notif->type ?? 'system') {
                                            'booking' => ($__role === 'admin'
                                                ? (Route::has('admin.tickets.show') && str_contains($__notif->title ?? '', 'Tiket')
                                                    ? null  // akan di-handle oleh action_url yang sudah diset
                                                    : route('admin.bookings.show', $__notif->related_id))
                                                : route('bookings.show', $__notif->related_id)),
                                            'payment' => ($__role === 'admin'
                                                ? route('admin.payments.show', $__notif->related_id)
                                                : route('payments.show', $__notif->related_id)),
                                            default => null,
                                        };
                                    }
                                @endphp

                                {{-- Item notif: <a> jika punya URL, div jika tidak --}}
                                @if($__notifUrl)
                                <a href="{{ $__notifUrl }}"
                                   class="notif-item {{ $__isUnread ? 'is-unread' : '' }}"
                                   @if($__isUnread)
                                   onclick="fetch('{{ route('notifications.read', $__notif->_id) }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'}})"
                                   @endif
                                >
                                @else
                                <div class="notif-item no-link {{ $__isUnread ? 'is-unread' : '' }}">
                                @endif

                                    <div class="notif-item-icon" style="background:{{ $__cfg['bg'] }};color:{{ $__cfg['color'] }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $__cfg['path'] !!}</svg>
                                    </div>
                                    <div class="notif-item-body">
                                        <div class="notif-item-title">{{ $__notif->title ?? 'Notifikasi' }}</div>
                                        <div class="notif-item-msg">{{ $__notif->message ?? '' }}</div>
                                        <div class="notif-item-time">{{ $__notif->created_at?->diffForHumans() }}</div>
                                    </div>
                                    @if($__isUnread)
                                    <span class="notif-unread-marker"></span>
                                    @endif

                                @if($__notifUrl)
                                </a>
                                @else
                                </div>
                                @endif

                                @empty
                                <div class="notif-empty">
                                    <div class="notif-empty-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                            <path d="M13.73 21a2 2 0 01-3.46 0"/>
                                        </svg>
                                    </div>
                                    <div class="notif-empty-text">Belum ada notifikasi</div>
                                </div>
                                @endforelse
                            </div>

                            {{-- Footer --}}
                            @if(Route::has('notifications.index'))
                            <div class="notif-panel-footer">
                                <a href="{{ route('notifications.index') }}">Semua notifikasi →</a>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endauth

                    {{-- Divider --}}
                    @auth
                    <div style="width:1px;height:20px;background:var(--border);margin:0 6px"></div>
                    @endauth

                    {{-- User chip --}}
                    @auth
                    <div class="header-user-chip">
                        <div class="header-avatar">
                            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
                        </div>
                        <span class="header-user-name">{{ Auth::user()->name }}</span>
                    </div>
                    @endauth

                </div>
            </header>

            {{-- ── SCROLLABLE CONTENT ── --}}
            <main class="flex-1 overflow-y-auto">
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Driver location tracking (unchanged) --}}
    @auth
    @if(Auth::user()->role === 'driver')
    <script>
    (function () {
        if (!navigator.geolocation) return;

        const LOCATION_URL  = '{{ url("/driver/location") }}';
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
    </script>
    @endif
    @endauth

    @stack('scripts')
</body>
</html>