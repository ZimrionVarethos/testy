{{-- resources/views/driver/bookings/available.blade.php --}}
<x-app-layout>
    <x-slot name="header">Pesanan Tersedia</x-slot>
    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-3">
        @if($errors->any())<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ $errors->first('error') }}</div>@endif
        @if($hasActiveBooking)
            {{-- Banner warning di atas list --}}
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg text-sm mb-4">
                ⚠️ Kamu masih punya pesanan aktif. Selesaikan dulu sebelum ambil pesanan baru.
            </div>
        @endif

        @forelse($bookings as $b)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <p class="font-semibold text-gray-800">{{ $b->booking_code }}</p>
                        <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 text-xs rounded-full font-medium">Menunggu Driver</span>
                    </div>
                    <p class="text-sm text-gray-600">🚗 {{ $b->vehicle['name'] ?? '-' }} · {{ $b->vehicle['plate_number'] ?? '-' }}</p>
                    <p class="text-sm text-gray-500 mt-1">👤 {{ $b->user['name'] ?? '-' }}</p>
                    <p class="text-sm text-gray-500">📍 {{ $b->pickup['address'] ?? '-' }}</p>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ \Carbon\Carbon::parse($b->start_date)->format('d M Y H:i') }} →
                        {{ \Carbon\Carbon::parse($b->end_date)->format('d M Y H:i') }}
                        · {{ $b->duration_days }} hari
                    </p>
                    <p class="font-semibold text-indigo-600 mt-1">Rp {{ number_format($b->total_price, 0, ',', '.') }}</p>
                </div>
                @if($hasActiveBooking)
                    <button disabled class="px-4 py-2 bg-gray-200 text-gray-400 text-sm rounded-lg font-medium whitespace-nowrap cursor-not-allowed">
                        Tidak Tersedia
                    </button>
                @else
                    <form method="POST" action="{{ route('driver.bookings.accept', $b->_id) }}" class="ml-4">
                        @csrf
                        <button class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition font-medium whitespace-nowrap">
                            Ambil Pesanan
                        </button>
                    </form>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center py-12">
            <p class="text-gray-400">Tidak ada pesanan tersedia saat ini.</p>
            <p class="text-xs text-gray-300 mt-1">Notifikasi akan masuk saat ada pesanan baru.</p>
        </div>
        @endforelse
        <div>{{ $bookings->links() }}</div>
    </div>
</x-app-layout>
