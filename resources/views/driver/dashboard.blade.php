{{-- resources/views/driver/dashboard.blade.php --}}

<div class="py-6">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- Greeting + Status Ketersediaan --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">Halo, {{ Auth::user()->name }}! 👋</h3>
            <p class="text-sm text-gray-500 mt-0.5">
                Status:
                @if(Auth::user()->driver_profile['is_available'] ?? false)
                    <span class="text-green-600 font-medium">Tersedia</span>
                @else
                    <span class="text-red-500 font-medium">Tidak Tersedia</span>
                @endif
            </p>
        </div>
        <form method="POST" action="{{ route('driver.toggle-availability') }}">
            @csrf
            <button type="submit" @class([
                'px-4 py-2 text-sm font-medium rounded-lg transition',
                'bg-red-100 text-red-600 hover:bg-red-200'      => Auth::user()->driver_profile['is_available'] ?? false,
                'bg-green-100 text-green-600 hover:bg-green-200' => !(Auth::user()->driver_profile['is_available'] ?? false),
            ])>
                {{ (Auth::user()->driver_profile['is_available'] ?? false) ? 'Set Tidak Tersedia' : 'Set Tersedia' }}
            </button>
        </form>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 text-center">
            <p class="text-2xl font-bold text-gray-800">{{ $stats['total_trips'] ?? 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">Total Trip</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 text-center">
            <p class="text-2xl font-bold text-green-500">{{ $stats['ongoing'] ?? 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">Sedang Berjalan</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 text-center">
            <p class="text-2xl font-bold text-indigo-500">{{ $stats['confirmed'] ?? 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">Akan Datang</p>
        </div>
    </div>

    {{-- Pesanan Aktif Saya --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h4 class="font-semibold text-gray-700">Pesanan Aktif Saya</h4>
            <a href="{{ route('driver.bookings.index') }}" class="text-xs text-indigo-500 hover:underline">Lihat semua</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($myActiveBookings ?? [] as $booking)
            <a href="{{ route('driver.bookings.show', $booking->_id) }}"
               class="block px-5 py-4 hover:bg-gray-50 transition">
                <div class="flex items-start justify-between">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800">{{ $booking->booking_code }}</p>
                        <p class="text-sm text-gray-600 mt-0.5">
                            {{ $booking->vehicle['name'] ?? '-' }} · {{ $booking->vehicle['plate_number'] ?? '-' }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1 truncate">
                            Jemput: {{ $booking->pickup['address'] ?? '-' }}
                        </p>
                        <p class="text-xs text-gray-400">
                            {{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y H:i') }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1.5 flex items-center gap-1">
                            <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            {{ $booking->user['name'] ?? '-' }}
                            @if(!empty($booking->user['phone']))
                            · {{ $booking->user['phone'] }}
                            @endif
                        </p>
                    </div>
                    <span @class([
                        'px-2 py-1 text-xs rounded-full font-medium shrink-0 ml-3',
                        'bg-indigo-100 text-indigo-700' => $booking->status === 'confirmed',
                        'bg-green-100 text-green-700'   => $booking->status === 'ongoing',
                    ])>
                        @switch($booking->status)
                            @case('confirmed') Siap Jemput @break
                            @case('ongoing')   Sedang Berjalan @break
                            @default {{ ucfirst($booking->status) }}
                        @endswitch
                    </span>
                </div>
            </a>
            @empty
            <p class="px-5 py-8 text-sm text-gray-400 text-center">Belum ada pesanan aktif.</p>
            @endforelse
        </div>
    </div>

</div>
</div>