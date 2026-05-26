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
    <link rel="stylesheet" href="{{ asset('css/layouts/app.css') }}">
    @stack('head-scripts')

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
            $__notifPreview = app(\App\Http\Controllers\Api\NotificationController::class)->navPreviewForWeb(request());
            $__notifItems  = $__notifPreview['items'];
            $__unreadCount = $__notifPreview['unread_count'];
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
                                        'booking'  => ['bg'=>'#eff6ff', 'color'=>'#111827',
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
                                <a href="{{ route('notifications.index') }}">Semua notifikasi</a>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endauth

                    {{-- Divider --}}
                    @auth
                    <div style="width:1px;height:20px;background:var(--border);margin:0 6px"></div>
                    @endauth

                    {{-- User chip + dropdown --}}
                    @auth
                    <div class="header-user-chip-wrap" x-data="{ open: false }">

                        <button class="header-user-chip" @click="open = !open" aria-label="Profil">
                            @if(Auth::user()->avatar)
                                <img src="{{ Auth::user()->avatar }}" alt="{{ Auth::user()->name }}"
                                     class="header-avatar" style="object-fit:cover;padding:0;">
                            @else
                                <div class="header-avatar">
                                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
                                </div>
                            @endif
                            <span class="header-user-name">{{ Auth::user()->name }}</span>
                        </button>

                        {{-- Dropdown panel --}}
                        <div class="user-panel"
                             x-show="open"
                             @click.outside="open = false"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             style="display:none">

                            {{-- Info user --}}
                            <div class="user-panel-info">
                                @if(Auth::user()->avatar)
                                    <img src="{{ Auth::user()->avatar }}" alt="{{ Auth::user()->name }}"
                                         class="user-panel-avatar" style="object-fit:cover;">
                                @else
                                    <div class="user-panel-avatar">
                                        {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
                                    </div>
                                @endif
                                <div class="user-panel-name">{{ Auth::user()->name }}</div>
                                <div class="user-panel-email">{{ Auth::user()->email }}</div>
                            </div>

                            {{-- Aksi --}}
                            <div class="user-panel-actions">
                                <a href="{{ route('profile.edit') }}" class="user-panel-btn" @click="open = false">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                    Edit Profil
                                </a>
                                <form method="POST" action="{{ route('logout') }}" style="margin:0">
                                    @csrf
                                    <button type="submit" class="user-panel-btn danger">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/>
                                        </svg>
                                        Keluar
                                    </button>
                                </form>
                            </div>
                        </div>
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
    <script>window.DriverConfig = { locationUrl: '{{ url("/driver/location") }}' };</script>
    <script src="{{ asset('js/layouts/driver-tracking.js') }}"></script>
    @endif
    @endauth

    @stack('scripts')
</body>
</html>
