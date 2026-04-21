{{-- resources/views/layouts/navigation.blade.php --}}

@php $role = auth()->check() ? auth()->user()->role : null; @endphp

<style>
#sidebar-nav::-webkit-scrollbar { display: none; }
#sidebar-nav { -ms-overflow-style: none; scrollbar-width: none; }
</style>

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
    :style="
        isDesktop
            ? 'width: ' + (sidebarOpen ? '13rem' : '4rem')
            : 'transform: ' + (sidebarOpen ? 'translateX(0)' : 'translateX(-100%)') + '; width: 13rem'
    "
    :class="ready ? 'transition-[width,transform] duration-300 ease-in-out' : ''"
    class="fixed top-0 left-0 z-30 h-full bg-gray-900 text-white flex flex-col overflow-hidden">

    {{-- Logo / Brand --}}
    <div class="flex items-center h-16 border-b border-gray-700 shrink-0 overflow-hidden">
        <a href="{{ route('dashboard') }}" class="flex items-center shrink-0 pl-4">
            <x-application-logo class="h-8 w-8 fill-current text-white shrink-0" />
        </a>
        <div class="overflow-hidden transition-all duration-300 whitespace-nowrap"
             :class="sidebarOpen ? 'opacity-100 max-w-xs' : 'opacity-0 max-w-0 display-none'">
            <span class="font-semibold text-base tracking-tight">Bening Rental</span>
        </div>
        <button @click="sidebarOpen = false; localStorage.setItem('sidebarOpen', false)"
                class="ml-auto mr-3 lg:hidden text-gray-400 hover:text-white shrink-0 transition-all duration-300"
                :class="sidebarOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Navigation Menu --}}
    <nav id="sidebar-nav" class="flex-1 overflow-y-auto overflow-x-hidden py-4 space-y-1 px-2">

        @if($role === 'admin')
            <x-sidebar-link :href="route('dashboard')"              :active="request()->routeIs('dashboard')"             icon="home">Dashboard</x-sidebar-link>
            <x-sidebar-link :href="route('admin.bookings.index')"   :active="request()->routeIs('admin.bookings.*')"      icon="clipboard-list">Pesanan</x-sidebar-link>
            <x-sidebar-link :href="route('admin.vehicles.index')"   :active="request()->routeIs('admin.vehicles.*')"     icon="truck">Kendaraan</x-sidebar-link>
            <x-sidebar-link :href="route('admin.drivers.index')"    :active="request()->routeIs('admin.drivers.*')"      icon="identification">Driver</x-sidebar-link>
            <x-sidebar-link :href="route('admin.users.index')"      :active="request()->routeIs('admin.users.*')"        icon="user-group">Pengguna</x-sidebar-link>
            <x-sidebar-link :href="route('admin.payments.index')"   :active="request()->routeIs('admin.payments.*')"    icon="credit-card">Pembayaran</x-sidebar-link>
            <x-sidebar-link :href="route('admin.tickets.index')"    :active="request()->routeIs('admin.tickets.*')"     icon="clipboard-list">Tiket Bantuan</x-sidebar-link>
            <x-sidebar-link :href="route('admin.reports.index')"    :active="request()->routeIs('admin.reports.*')"     icon="chart-bar">Laporan & Statistik</x-sidebar-link>
            <x-sidebar-link :href="route('admin.maps.index')"       :active="request()->routeIs('admin.maps.*')"        icon="maps">Lokasi</x-sidebar-link>
            <x-sidebar-link :href="route('admin.landing.index')"    :active="request()->routeIs('admin.landing.*')"     icon="website">Landing Page</x-sidebar-link>
            @if(Auth::user()->email === 'mochfarelaz@gmail.com')
            <x-sidebar-link :href="route('admin.storage.index')"    :active="request()->routeIs('admin.storage.*')"     icon="storage">Storage</x-sidebar-link>
            @endif

        @elseif($role === 'pengguna' || $role === 'user')

            @php
                // Hitung unread chat untuk badge
                $__chatUnread = 0;
                try {
                    $__activeChatBookings = \App\Models\Booking::where('user.user_id', (string) Auth::id())
                        ->whereIn('status', ['confirmed', 'ongoing'])
                        ->whereNotNull('driver.driver_id')
                        ->get();
                    foreach ($__activeChatBookings as $__cb) {
                        $__chatUnread += \App\Models\ChatMessage::unreadCount((string) $__cb->_id, 'pengguna');
                    }
                } catch (\Exception $e) { $__chatUnread = 0; }
            @endphp

            <x-sidebar-link :href="route('dashboard')"            :active="request()->routeIs('dashboard')"          icon="home">Dashboard</x-sidebar-link>
            <x-sidebar-link :href="route('bookings.index')"       :active="request()->routeIs('bookings.*')"         icon="clipboard-list">Pesanan Saya</x-sidebar-link>
            <x-sidebar-link :href="route('vehicles.index')"       :active="request()->routeIs('vehicles.*')"        icon="truck">Sewa Kendaraan</x-sidebar-link>
            <x-sidebar-link :href="route('payments.index')"       :active="request()->routeIs('payments.*')"        icon="credit-card">Riwayat Pembayaran</x-sidebar-link>
            <x-sidebar-link :href="route('tickets.index')"        :active="request()->routeIs('tickets.*')"         icon="clipboard-list">Tiket Bantuan</x-sidebar-link>

            {{-- Chat dengan badge unread --}}
            <a href="{{ route('pengguna.chats.index') }}"
               class="flex items-center py-2 px-2 rounded-lg text-sm transition-colors duration-150 relative
                      {{ request()->routeIs('pengguna.chats.*')
                          ? 'bg-indigo-600 text-white'
                          : 'text-gray-400 hover:text-white hover:bg-gray-700' }}">
                <svg class="h-5 w-5 shrink-0 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span class="whitespace-nowrap overflow-hidden transition-all duration-300 ml-3 flex-1"
                      :class="sidebarOpen ? 'opacity-100 max-w-xs' : 'opacity-0 max-w-0'">
                    Chat
                </span>
                @if($__chatUnread > 0)
                <span class="ml-auto mr-1 px-1.5 py-0.5 bg-red-500 text-white text-[9px] font-bold rounded-full leading-none
                             transition-all duration-300"
                      :class="sidebarOpen ? 'opacity-100' : 'opacity-0'">
                    {{ $__chatUnread > 9 ? '9+' : $__chatUnread }}
                </span>
                @endif
            </a>

            <x-sidebar-link :href="route('notifications.index')"  :active="request()->routeIs('notifications.*')"  icon="bell">Notifikasi</x-sidebar-link>

        @elseif($role === 'driver')

            @php
                $__driverChatUnread = 0;
                try {
                    $__driverActiveBookings = \App\Models\Booking::where('driver.driver_id', (string) Auth::id())
                        ->whereIn('status', ['confirmed', 'ongoing'])
                        ->get();
                    foreach ($__driverActiveBookings as $__db) {
                        $__driverChatUnread += \App\Models\ChatMessage::unreadCount((string) $__db->_id, 'driver');
                    }
                } catch (\Exception $e) { $__driverChatUnread = 0; }
            @endphp

            <x-sidebar-link :href="route('dashboard')"             :active="request()->routeIs('dashboard')"              icon="home">Dashboard</x-sidebar-link>
            <x-sidebar-link :href="route('driver.bookings.index')" :active="request()->routeIs('driver.bookings.index')"  icon="truck">Pesanan Saya</x-sidebar-link>

            {{-- Chat dengan badge unread --}}
            <a href="{{ route('driver.chats.index') }}"
               class="flex items-center py-2 px-2 rounded-lg text-sm transition-colors duration-150 relative
                      {{ request()->routeIs('driver.chats.*')
                          ? 'bg-indigo-600 text-white'
                          : 'text-gray-400 hover:text-white hover:bg-gray-700' }}">
                <svg class="h-5 w-5 shrink-0 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span class="whitespace-nowrap overflow-hidden transition-all duration-300 ml-3 flex-1"
                      :class="sidebarOpen ? 'opacity-100 max-w-xs' : 'opacity-0 max-w-0'">
                    Chat
                </span>
                @if($__driverChatUnread > 0)
                <span class="ml-auto mr-1 px-1.5 py-0.5 bg-red-500 text-white text-[9px] font-bold rounded-full leading-none
                             transition-all duration-300"
                      :class="sidebarOpen ? 'opacity-100' : 'opacity-0'">
                    {{ $__driverChatUnread > 9 ? '9+' : $__driverChatUnread }}
                </span>
                @endif
            </a>

        @else
            <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">Dashboard</x-sidebar-link>
        @endif

    </nav>

    {{-- User Info + Logout --}}
    <div class="shrink-0 border-t border-gray-700 p-2">
        <div class="flex items-center py-2 mb-1 overflow-hidden">
            <div class="h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center text-sm font-bold shrink-0 ml-1">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>
            <div class="overflow-hidden transition-all duration-300 whitespace-nowrap ml-3"
                 :class="sidebarOpen ? 'opacity-100 max-w-xs' : 'opacity-0 max-w-0'">
                <p class="text-sm font-medium text-white truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-gray-400 truncate">{{ Auth::user()->email }}</p>
            </div>
        </div>
        <x-sidebar-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')" icon="user-circle">Profil Saya</x-sidebar-link>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="w-full flex items-center py-2 px-2 rounded-lg text-sm text-gray-400 hover:text-white hover:bg-gray-700 transition-colors duration-150">
                <svg class="h-5 w-5 shrink-0 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/>
                </svg>
                <span class="whitespace-nowrap overflow-hidden transition-all duration-300 ml-3"
                      :class="sidebarOpen ? 'opacity-100 max-w-xs' : 'opacity-0 max-w-0'">
                    Keluar
                </span>
            </button>
        </form>
    </div>
</aside>