{{-- resources/views/layouts/navigation.blade.php --}}

@php $role = auth()->check() ? auth()->user()->role : null; @endphp

{{-- Overlay untuk mobile --}}
<div x-show="!isDesktop && sidebarOpen"
     x-transition:enter="transition-opacity ease-linear duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="sidebarOpen = false; localStorage.setItem('sidebarOpen', false)"
     class="fixed inset-0 z-20 bg-black/50 lg:hidden"
     style="display: none;">
</div>

{{-- Sidebar --}}
<aside
    id="sidebar"
    :class="
        isDesktop
            ? 'transform: translateX(0); width: ' + (sidebarOpen ? '13rem' : '4rem') + ';'
            : 'transform: ' + (sidebarOpen ? 'translateX(0)' : 'translateX(-100%)') + '; width: 13rem;'
    "
    :class="ready ? 'transition-[width,transform] duration-300 ease-in-out' : ''"
    style="position: fixed; top: 0; left: 0; z-index: 30; height: 100%; background-color: #111827; color: white; display: flex; flex-direction: column; overflow: hidden;">

    {{-- Logo / Brand --}}
    <div class="relative flex items-center justify-center h-16 border-b border-gray-700 shrink-0">

        {{-- State TERBUKA: logo + teks + tombol close (mobile) --}}
        <div class="absolute inset-0 flex items-center justify-between px-4 transition-opacity duration-300"
             :class="sidebarOpen ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <x-application-logo class="h-8 w-auto fill-current text-white shrink-0" />
                <span class="font-semibold text-base tracking-tight whitespace-nowrap">Bening Rental</span>
            </a>
            <button @click="sidebarOpen = false; localStorage.setItem('sidebarOpen', false)" class="lg:hidden text-gray-400 hover:text-white">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- State TERTUTUP desktop: hanya logo icon di tengah --}}
        <div class="absolute inset-0 flex items-center justify-center transition-opacity duration-300"
             :class="(!sidebarOpen && isDesktop) ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'">
            <a href="{{ route('dashboard') }}">
                <x-application-logo class="h-8 w-auto fill-current text-white" />
            </a>
        </div>

    </div>

    {{-- Navigation Menu --}}
    <nav class="flex-1 overflow-y-auto overflow-x-hidden py-4 space-y-1"
         :class="sidebarOpen ? 'px-3' : 'px-2'">

        @if($role === 'admin')

            <p class="pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider transition-all duration-300 overflow-hidden whitespace-nowrap"
               :class="sidebarOpen ? 'px-2 opacity-100 max-h-8' : 'px-0 opacity-0 max-h-0'">Utama</p>
            <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">Dashboard</x-sidebar-link>

            <p class="pt-3 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider transition-all duration-300 overflow-hidden whitespace-nowrap"
               :class="sidebarOpen ? 'px-2 opacity-100 max-h-8' : 'px-0 opacity-0 max-h-0'">Kelola</p>
            <x-sidebar-link :href="route('admin.bookings.index')" :active="request()->routeIs('admin.bookings.*')" icon="clipboard-list">Pesanan</x-sidebar-link>
            <x-sidebar-link :href="route('admin.vehicles.index')" :active="request()->routeIs('admin.vehicles.*')" icon="truck">Kendaraan</x-sidebar-link>
            <x-sidebar-link :href="route('admin.drivers.index')" :active="request()->routeIs('admin.drivers.*')" icon="identification">Driver</x-sidebar-link>
            <x-sidebar-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" icon="user-group">Pengguna</x-sidebar-link>

            <p class="pt-3 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider transition-all duration-300 overflow-hidden whitespace-nowrap"
               :class="sidebarOpen ? 'px-2 opacity-100 max-h-8' : 'px-0 opacity-0 max-h-0'">Laporan</p>
            <x-sidebar-link :href="route('admin.payments.index')" :active="request()->routeIs('admin.payments.*')" icon="credit-card">Pembayaran</x-sidebar-link>
            <x-sidebar-link :href="route('admin.reports.index')" :active="request()->routeIs('admin.reports.*')" icon="chart-bar">Laporan & Statistik</x-sidebar-link>

        @elseif($role === 'pengguna' || $role === 'user')

            <p class="pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider transition-all duration-300 overflow-hidden whitespace-nowrap"
               :class="sidebarOpen ? 'px-2 opacity-100 max-h-8' : 'px-0 opacity-0 max-h-0'">Menu</p>
            <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">Dashboard</x-sidebar-link>
            <x-sidebar-link :href="route('bookings.index')" :active="request()->routeIs('bookings.*')" icon="clipboard-list">Pesanan Saya</x-sidebar-link>
            <x-sidebar-link :href="route('vehicles.index')" :active="request()->routeIs('vehicles.*')" icon="truck">Sewa Kendaraan</x-sidebar-link>

            <p class="pt-3 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider transition-all duration-300 overflow-hidden whitespace-nowrap"
               :class="sidebarOpen ? 'px-2 opacity-100 max-h-8' : 'px-0 opacity-0 max-h-0'">Lainnya</p>
            <x-sidebar-link :href="route('payments.index')" :active="request()->routeIs('payments.*')" icon="credit-card">Riwayat Pembayaran</x-sidebar-link>
            <x-sidebar-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')" icon="bell">Notifikasi</x-sidebar-link>

        @elseif($role === 'driver')

            <p class="pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider transition-all duration-300 overflow-hidden whitespace-nowrap"
               :class="sidebarOpen ? 'px-2 opacity-100 max-h-8' : 'px-0 opacity-0 max-h-0'">Menu Driver</p>
            <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">Dashboard</x-sidebar-link>
            <x-sidebar-link :href="route('driver.bookings.available')" :active="request()->routeIs('driver.bookings.available')" icon="clipboard-list">Pesanan Tersedia</x-sidebar-link>
            <x-sidebar-link :href="route('driver.bookings.index')" :active="request()->routeIs('driver.bookings.index')" icon="truck">Pesanan Saya</x-sidebar-link>

            <p class="pt-3 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider transition-all duration-300 overflow-hidden whitespace-nowrap"
               :class="sidebarOpen ? 'px-2 opacity-100 max-h-8' : 'px-0 opacity-0 max-h-0'">Lainnya</p>
            <x-sidebar-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')" icon="bell">Notifikasi</x-sidebar-link>

        @else
            <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">Dashboard</x-sidebar-link>
        @endif

    </nav>

    {{-- User Info + Logout --}}
    <div class="shrink-0 border-t border-gray-700 p-2">

        <div class="flex items-center gap-3 py-2 mb-1 overflow-hidden"
             :class="sidebarOpen ? 'px-2' : 'px-0 justify-center'">
            <div class="h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center text-sm font-bold shrink-0">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0 transition-all duration-300 overflow-hidden whitespace-nowrap"
                 :class="sidebarOpen ? 'opacity-100 max-w-full' : 'opacity-0 max-w-0'">
                <p class="text-sm font-medium text-white truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-gray-400 truncate">{{ Auth::user()->email }}</p>
            </div>
        </div>

        <x-sidebar-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')" icon="user-circle">Profil Saya</x-sidebar-link>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="w-full flex items-center py-2 rounded-lg text-sm text-gray-400 hover:text-white hover:bg-gray-700 transition-colors duration-150"
                :class="sidebarOpen ? 'gap-3 px-3 justify-start' : 'gap-0 px-2 justify-center'">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/>
                </svg>
                <span class="whitespace-nowrap overflow-hidden transition-all duration-300"
                      :class="sidebarOpen ? 'opacity-100 max-w-full' : 'opacity-0 max-w-0'">
                    Keluar
                </span>
            </button>
        </form>
    </div>
</aside>