{{-- resources/views/pengguna/dashboard.blade.php --}}

<div class="py-6">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- Greeting --}}
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-500 rounded-xl p-6 text-white">
        <h3 class="text-xl font-bold">Halo, {{ Auth::user()->name }}! 👋</h3>
        <p class="text-indigo-200 text-sm mt-1">Mau pergi ke mana hari ini?</p>
        <a href="{{ route('vehicles.index') }}"
           class="mt-4 inline-block bg-white text-indigo-600 text-sm font-semibold px-4 py-2 rounded-lg hover:bg-indigo-50 transition">
            Sewa Kendaraan Sekarang →
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 text-center">
            <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] ?? 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">Total Pesanan</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 text-center">
            <p class="text-2xl font-bold text-green-500">{{ $stats['ongoing'] ?? 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">Sedang Berjalan</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 text-center">
            <p class="text-2xl font-bold text-gray-400">{{ $stats['completed'] ?? 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">Selesai</p>
        </div>
    </div>

    {{-- Pesanan Aktif --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h4 class="font-semibold text-gray-700">Pesanan Aktif</h4>
            <a href="{{ route('bookings.index') }}" class="text-xs text-indigo-500 hover:underline">Lihat semua</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($activeBookings ?? [] as $booking)
            <div class="px-5 py-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ $booking->booking_code }}</p>
                        <p class="text-sm text-gray-600 mt-0.5">{{ $booking->vehicle['name'] ?? '-' }}</p>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y') }}
                            →
                            {{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y') }}
                        </p>
                    </div>
                    <span @class([
                        'px-2 py-1 text-xs rounded-full font-medium shrink-0',
                        'bg-yellow-100 text-yellow-700' => $booking->status === 'pending',
                        'bg-blue-100 text-blue-700'     => $booking->status === 'accepted',
                        'bg-indigo-100 text-indigo-700' => $booking->status === 'confirmed',
                        'bg-green-100 text-green-700'   => $booking->status === 'ongoing',
                    ])>
                        @switch($booking->status)
                            @case('pending') Menunggu Driver @break
                            @case('accepted') Driver Ditemukan @break
                            @case('confirmed') Dikonfirmasi @break
                            @case('ongoing') Sedang Berjalan @break
                            @default {{ ucfirst($booking->status) }}
                        @endswitch
                    </span>
                </div>
                @if($booking->driver)
                <div class="mt-2 flex items-center gap-2 text-xs text-gray-500">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Driver: {{ $booking->driver['name'] }}
                </div>
                @endif
            </div>
            @empty
            <div class="px-5 py-8 text-center">
                <p class="text-sm text-gray-400">Belum ada pesanan aktif.</p>
                <a href="{{ route('vehicles.index') }}" class="mt-2 inline-block text-indigo-500 text-sm hover:underline">Mulai sewa sekarang</a>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Notifikasi Terbaru --}}
    @if(isset($notifications) && $notifications->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h4 class="font-semibold text-gray-700">Notifikasi Terbaru</h4>
            <a href="{{ route('notifications.index') }}" class="text-xs text-indigo-500 hover:underline">Lihat semua</a>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($notifications as $notif)
            <div class="px-5 py-3 flex items-start gap-3 {{ $notif->is_read ? '' : 'bg-indigo-50/40' }}">
                <div class="h-2 w-2 rounded-full mt-2 shrink-0 {{ $notif->is_read ? 'bg-gray-300' : 'bg-indigo-500' }}"></div>
                <div>
                    <p class="text-sm font-medium text-gray-800">{{ $notif->title }}</p>
                    <p class="text-xs text-gray-500">{{ $notif->message }}</p>
                    <p class="text-xs text-gray-300 mt-0.5">{{ \Carbon\Carbon::parse($notif->created_at)->diffForHumans() }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
</div>