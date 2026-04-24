{{-- resources/views/driver/bookings/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Pesanan Saya</x-slot>
    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-3">

        @forelse($bookings as $b)
        <a href="{{ route('driver.bookings.show', $b->_id) }}"
           class="block bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:border-indigo-200 hover:shadow-md transition">
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-800">{{ $b->booking_code }}</p>
                    <p class="text-sm text-gray-600 mt-0.5">🚗 {{ $b->vehicle['name'] ?? '-' }}
                        <span class="text-gray-400">· {{ $b->vehicle['plate_number'] ?? '-' }}</span>
                    </p>
                    <p class="text-sm text-gray-500">👤 {{ $b->user['name'] ?? '-' }}</p>
                    <p class="text-sm text-gray-500 truncate">📍 {{ $b->pickup['address'] ?? '-' }}</p>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ \Carbon\Carbon::parse($b->start_date)->format('d M Y H:i') }} →
                        {{ \Carbon\Carbon::parse($b->end_date)->format('d M Y H:i') }}
                    </p>

                    {{-- Countdown: kalau confirmed dan belum waktunya jemput --}}
                    @if($b->status === 'confirmed')
                        @php $diffHours = now()->diffInHours(\Carbon\Carbon::parse($b->start_date), false); @endphp
                        @if($diffHours > 0)
                        <p class="text-xs text-indigo-500 mt-1">
                            ⏱ Jemput dalam {{ $diffHours }} jam
                        </p>
                        @elseif($diffHours >= -1)
                        <p class="text-xs text-green-600 font-medium mt-1">
                            ✓ Waktunya menjemput sekarang!
                        </p>
                        @endif
                    @endif
                </div>

                <div class="shrink-0">
                    <span @class(['px-2 py-1 text-xs rounded-full font-medium',
                        'bg-indigo-100 text-indigo-700' => $b->status === 'confirmed',
                        'bg-green-100 text-green-700'   => $b->status === 'ongoing',
                        'bg-gray-100 text-gray-600'     => $b->status === 'completed',
                        'bg-red-100 text-red-600'       => $b->status === 'cancelled',
                    ])>{{ $b->statusLabel() }}</span>
                </div>
            </div>
        </a>
        @empty
        <div class="text-center py-12 text-gray-400">
            <p>Belum ada pesanan yang ditugaskan.</p>
        </div>
        @endforelse

        <div>{{ $bookings->links() }}</div>
    </div>
</x-app-layout>