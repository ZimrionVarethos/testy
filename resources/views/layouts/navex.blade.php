
<!-- resources/views/layouts/navigation.blade.php -->

@php $role = auth()->check() ? auth()->user()->role : null; @endphp

{{-- Overlay untuk mobile --}}
<div x-show="sidebarOpen"
     x-transition:enter="transition-opacity ease-linear duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="sidebarOpen = false"
    class="fixed inset-0 z-20 bg-black/50 lg:hidden"
     style="display: none;">
</div>

{{-- Sidebar --}}
<aside
    :style="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    style="position: fixed; top: 0; left: 0; z-index: 30; height: 100%; width: 16rem; background-color: #111827; color: white; display: flex; flex-direction: column; transition: transform 300ms ease-in-out;">

    {{-- Logo / Brand --}}
    <div class="flex items-center justify-between h-16 px-5 border-b border-gray-700 shrink-0">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
            <x-application-logo class="h-8 w-auto fill-current text-white" />
            <span class="font-semibold text-lg tracking-tight">Bening Rental</span>
        </a>
        {{-- Close button mobile --}}
        <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Navigation Menu --}}
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">

        {{-- ===================== ADMIN MENU ===================== --}}
        @if($role === 'admin')

            <p class="px-2 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">Utama</p>

            <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">
                Dashboard
            </x-sidebar-link>
            
            



        {{-- ===================== PENGGUNA / USER MENU ===================== --}}
        @elseif($role === 'pengguna' || $role === 'user')

            <p class="px-2 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">Menu</p>

            <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">
                Dashboard
            </x-sidebar-link>



        {{-- ===================== DRIVER MENU ===================== --}}
        @elseif($role === 'driver')

            <p class="px-2 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">Menu Driver</p>

            <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">
                Dashboard
            </x-sidebar-link>


        {{-- ===================== FALLBACK ===================== --}}
        @else

            <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">
                Dashboard
            </x-sidebar-link>

        @endif

    </nav>

    {{-- User Info + Logout (selalu tampil di bawah) --}}
    <div class="shrink-0 border-t border-gray-700 p-3">
        <div class="flex items-center gap-3 px-2 py-2 mb-1">
            <div class="h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center text-sm font-bold shrink-0">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-white truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-gray-400 truncate">{{ Auth::user()->email }}</p>
            </div>
        </div>

        <x-sidebar-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')" icon="user-circle">
            Profil Saya
        </x-sidebar-link>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-400 hover:text-white hover:bg-gray-700 transition-colors duration-150 text-left">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/>
                </svg>
                Keluar
            </button>
        </form>
    </div>
</aside>