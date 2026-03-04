{{-- resources/views/pengguna/bookings/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Pesanan Saya</x-slot>
    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-3">
        @if(session('success'))<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>@endif

        @forelse($bookings as $b)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-semibold text-gray-800">{{ $b->booking_code }}</p>
                    <p class="text-sm text-gray-600 mt-0.5">{{ $b->vehicle['name'] ?? '-' }}</p>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ \Carbon\Carbon::parse($b->start_date)->format('d M Y') }} →
                        {{ \Carbon\Carbon::parse($b->end_date)->format('d M Y') }}
                        · {{ $b->duration_days }} hari
                    </p>
                    <p class="text-sm font-bold text-indigo-600 mt-1">Rp {{ number_format($b->total_price, 0, ',', '.') }}</p>
                </div>
                <div class="flex flex-col items-end gap-2">
                    <span @class(['px-2 py-1 text-xs rounded-full font-medium',
                        'bg-yellow-100 text-yellow-700' => $b->status === 'pending',
                        'bg-blue-100 text-blue-700'     => $b->status === 'accepted',
                        'bg-indigo-100 text-indigo-700' => $b->status === 'confirmed',
                        'bg-green-100 text-green-700'   => $b->status === 'ongoing',
                        'bg-gray-100 text-gray-600'     => $b->status === 'completed',
                        'bg-red-100 text-red-600'       => $b->status === 'cancelled',
                    ])>
                        @switch($b->status)
                            @case('pending') Menunggu Driver @break
                            @case('accepted') Driver Ditemukan @break
                            @case('confirmed') Dikonfirmasi @break
                            @case('ongoing') Sedang Berjalan @break
                            @case('completed') Selesai @break
                            @case('cancelled') Dibatalkan @break
                        @endswitch
                    </span>
                    <a href="{{ route('bookings.show', $b->_id) }}" class="text-xs text-indigo-500 hover:underline">Detail</a>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-12">
            <p class="text-gray-400 mb-3">Belum ada pesanan.</p>
            <a href="{{ route('vehicles.index') }}" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition">Sewa Sekarang</a>
        </div>
        @endforelse
        <div>{{ $bookings->links() }}</div>
    </div>
</x-app-layout>