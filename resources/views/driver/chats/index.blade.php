{{-- resources/views/driver/chats/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Chat</x-slot>

    <div class="py-6 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

        {{-- Filter tabs --}}
        <div class="flex gap-1 bg-white border border-gray-100 rounded-xl p-1 shadow-sm">
            @foreach(['active' => 'Sedang Berjalan', 'history' => 'Riwayat'] as $key => $label)
            <a href="{{ route('driver.chats.index', ['filter' => $key]) }}"
               class="flex-1 text-center px-3 py-1.5 rounded-lg text-xs font-medium transition
                      {{ $filter === $key
                          ? 'bg-indigo-600 text-white'
                          : 'text-gray-500 hover:bg-gray-50' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>

        {{-- List booking --}}
        @forelse($bookings as $booking)
        @php
            $bid    = (string) $booking->_id;
            $unread = $unreadCounts[$bid] ?? 0;
            $rating = $ratings[$bid] ?? null;
            $isDone = in_array($booking->status, ['completed', 'cancelled']);
        @endphp

        <a href="{{ route('driver.bookings.show', $bid) }}"
           class="block bg-white rounded-xl border transition hover:shadow-md
                  {{ $unread > 0 ? 'border-indigo-200 shadow-sm' : 'border-gray-100' }}">
            <div class="p-4 flex items-start gap-3">

                {{-- Avatar / icon --}}
                <div class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center
                            {{ $isDone ? 'bg-gray-100' : 'bg-indigo-50' }}">
                    <svg class="w-5 h-5 {{ $isDone ? 'text-gray-400' : 'text-indigo-500' }}"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2 mb-0.5">
                        <p class="text-sm font-semibold text-gray-800 truncate">
                            {{ $booking->booking_code }}
                        </p>
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            @if($unread > 0)
                            <span class="px-1.5 py-0.5 bg-indigo-600 text-white text-[10px] font-bold rounded-full leading-none">
                                {{ $unread }}
                            </span>
                            @endif
                            <span @class([
                                'px-2 py-0.5 text-[10px] rounded-full font-medium',
                                'bg-indigo-100 text-indigo-700' => $booking->status === 'confirmed',
                                'bg-green-100 text-green-700'   => $booking->status === 'ongoing',
                                'bg-gray-100 text-gray-500'     => $booking->status === 'completed',
                                'bg-red-100 text-red-500'       => $booking->status === 'cancelled',
                            ])>
                                @switch($booking->status)
                                    @case('confirmed') Dikonfirmasi @break
                                    @case('ongoing')   Berjalan @break
                                    @case('completed') Selesai @break
                                    @case('cancelled') Dibatalkan @break
                                @endswitch
                            </span>
                        </div>
                    </div>

                    <p class="text-xs text-gray-500 truncate">
                        {{ $booking->vehicle['name'] ?? '-' }}
                        · Penumpang: <span class="text-gray-700">{{ $booking->user['name'] ?? '-' }}</span>
                    </p>

                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y') }}
                        –
                        {{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y') }}
                    </p>

                    {{-- Rating yang diterima (untuk completed) --}}
                    @if($booking->status === 'completed')
                        @if($rating)
                        <p class="text-xs text-amber-500 mt-1 font-medium">
                            {{ $rating->starLabel() }} — {{ $rating->comment ? '"'.$rating->comment.'"' : 'Tanpa komentar' }}
                        </p>
                        @else
                        <p class="text-xs text-gray-400 mt-1">Belum ada rating dari penumpang</p>
                        @endif
                    @endif
                </div>

                {{-- Chevron --}}
                <div class="flex-shrink-0 self-center">
                    <svg class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </div>
        </a>

        @empty
        <div class="bg-white rounded-xl border border-gray-100 py-16 text-center">
            <div class="w-12 h-12 rounded-xl bg-gray-50 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>
            <p class="text-sm text-gray-400">
                {{ $filter === 'active' ? 'Tidak ada chat aktif saat ini.' : 'Belum ada riwayat chat.' }}
            </p>
        </div>
        @endforelse
    </div>
</x-app-layout>