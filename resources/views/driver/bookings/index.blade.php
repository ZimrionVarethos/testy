{{-- resources/views/driver/bookings/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Pesanan Saya</x-slot>
    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-3">
        @forelse($bookings as $b)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-semibold text-gray-800">{{ $b->booking_code }}</p>
                    <p class="text-sm text-gray-600">🚗 {{ $b->vehicle['name'] ?? '-' }}</p>
                    <p class="text-sm text-gray-500">👤 {{ $b->user['name'] ?? '-' }}</p>
                    <p class="text-sm text-gray-500">📍 {{ $b->pickup['address'] ?? '-' }}</p>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ \Carbon\Carbon::parse($b->start_date)->format('d M Y') }} →
                        {{ \Carbon\Carbon::parse($b->end_date)->format('d M Y') }}
                    </p>
                </div>
                <div class="flex flex-col items-end gap-2">
                    <span @class(['px-2 py-1 text-xs rounded-full font-medium',
                        'bg-blue-100 text-blue-700'     => $b->status === 'accepted',
                        'bg-indigo-100 text-indigo-700' => $b->status === 'confirmed',
                        'bg-green-100 text-green-700'   => $b->status === 'ongoing',
                        'bg-gray-100 text-gray-600'     => $b->status === 'completed',
                        'bg-red-100 text-red-600'       => $b->status === 'cancelled',
                    ])>{{ ucfirst($b->status) }}</span>
                    <a href="{{ route('driver.bookings.show', $b->_id) }}" class="text-xs text-indigo-500 hover:underline">Detail</a>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-12 text-gray-400">Belum ada pesanan.</div>
        @endforelse
        <div>{{ $bookings->links() }}</div>
    </div>
</x-app-layout>