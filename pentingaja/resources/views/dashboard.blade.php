{{-- resources/views/dashboard.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        @php $role = auth()->check() ? auth()->user()->role : null; @endphp
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            @if($role === 'admin') Dashboard Admin
            @elseif($role === 'pengguna' || $role === 'user') Dashboard
            @elseif($role === 'driver') Dashboard Driver
            @else Dashboard
            @endif
        </h2>
    </x-slot>

    @php $role = auth()->check() ? auth()->user()->role : null; @endphp

    @if($role === 'admin')
        @includeIf('admin.dashboard')
    @elseif($role === 'pengguna' || $role === 'user')
        @includeIf('pengguna.dashboard')
    @elseif($role === 'driver')
        @includeIf('driver.dashboard')
    @else
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-gray-900">
                    <p>{{ __("You're logged in!") }}</p>
                </div>
            </div>
        </div>
    @endif

</x-app-layout>