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

    {{-- 
        Inject sidebar state SEBELUM render supaya tidak ada flash/geser.
        Langsung tulis margin ke <style> berdasarkan localStorage.
    --}}
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

</body>
</html>