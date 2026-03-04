{{-- resources/views/admin/dashboard.blade.php --}}

<div class="py-6">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- Greeting --}}
    <div>
        <h3 class="text-lg font-semibold text-gray-800">Selamat datang, {{ Auth::user()->name }} 👋</h3>
        <p class="text-sm text-gray-500">Berikut ringkasan aktivitas hari ini.</p>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Pesanan</p>
            <p class="text-3xl font-bold text-gray-800 mt-1">{{ $stats['total_bookings'] ?? 0 }}</p>
            <p class="text-xs text-indigo-500 mt-1">Semua waktu</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Menunggu Konfirmasi</p>
            <p class="text-3xl font-bold text-yellow-500 mt-1">{{ $stats['pending_bookings'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-1">Perlu tindakan</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Sedang Berjalan</p>
            <p class="text-3xl font-bold text-green-500 mt-1">{{ $stats['ongoing_bookings'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-1">Aktif sekarang</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Pendapatan Bulan Ini</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">Rp {{ number_format($stats['monthly_revenue'] ?? 0, 0, ',', '.') }}</p>
            <p class="text-xs text-green-500 mt-1">Bulan ini</p>
        </div>
    </div>

    {{-- Row 2: Pesanan & Kendaraan --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Pesanan Terbaru --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h4 class="font-semibold text-gray-700">Pesanan Terbaru</h4>
                <a href="{{ route('admin.bookings.index') }}" class="text-xs text-indigo-500 hover:underline">Lihat semua</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($recentBookings ?? [] as $booking)
                <div class="flex items-center justify-between px-5 py-3">
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $booking->booking_code }}</p>
                        <p class="text-xs text-gray-400">{{ $booking->user['name'] ?? '-' }}</p>
                    </div>
                    <span @class([
                        'px-2 py-1 text-xs rounded-full font-medium',
                        'bg-yellow-100 text-yellow-700' => $booking->status === 'pending',
                        'bg-blue-100 text-blue-700'     => $booking->status === 'accepted',
                        'bg-indigo-100 text-indigo-700' => $booking->status === 'confirmed',
                        'bg-green-100 text-green-700'   => $booking->status === 'ongoing',
                        'bg-gray-100 text-gray-600'     => $booking->status === 'completed',
                        'bg-red-100 text-red-600'       => $booking->status === 'cancelled',
                    ])>{{ ucfirst($booking->status) }}</span>
                </div>
                @empty
                <p class="px-5 py-4 text-sm text-gray-400">Belum ada pesanan.</p>
                @endforelse
            </div>
        </div>

        {{-- Status Kendaraan --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h4 class="font-semibold text-gray-700">Status Armada</h4>
                <a href="{{ route('admin.vehicles.index') }}" class="text-xs text-indigo-500 hover:underline">Kelola</a>
            </div>
            <div class="px-5 py-4 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full bg-green-400"></span>
                        <span class="text-sm text-gray-600">Tersedia</span>
                    </div>
                    <span class="font-semibold text-gray-800">{{ $vehicleStats['available'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full bg-blue-400"></span>
                        <span class="text-sm text-gray-600">Disewa</span>
                    </div>
                    <span class="font-semibold text-gray-800">{{ $vehicleStats['rented'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full bg-yellow-400"></span>
                        <span class="text-sm text-gray-600">Maintenance</span>
                    </div>
                    <span class="font-semibold text-gray-800">{{ $vehicleStats['maintenance'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Pesanan yang perlu dikonfirmasi --}}
    @if(isset($acceptedBookings) && $acceptedBookings->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-yellow-200">
        <div class="flex items-center gap-2 px-5 py-4 border-b border-yellow-100 bg-yellow-50 rounded-t-xl">
            <svg class="h-4 w-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 110 18A9 9 0 0112 3z"/>
            </svg>
            <h4 class="font-semibold text-yellow-700">Perlu Konfirmasi Admin ({{ $acceptedBookings->count() }})</h4>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($acceptedBookings as $booking)
            <div class="flex items-center justify-between px-5 py-3">
                <div>
                    <p class="text-sm font-medium text-gray-800">{{ $booking->booking_code }}</p>
                    <p class="text-xs text-gray-400">Driver: {{ $booking->driver['name'] ?? '-' }} · User: {{ $booking->user['name'] ?? '-' }}</p>
                </div>
                <form method="POST" action="{{ route('admin.bookings.confirm', $booking->_id) }}">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 bg-indigo-600 text-white text-xs rounded-lg hover:bg-indigo-700 transition">
                        Konfirmasi
                    </button>
                </form>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
</div>