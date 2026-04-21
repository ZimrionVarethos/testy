{{-- resources/views/pengguna/tickets/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Tiket Bantuan Saya</x-slot>

    <div class="py-6 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

        @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
        @endif

        @forelse($tickets as $ticket)
        <a href="{{ route('tickets.show', $ticket->_id) }}"
           class="block bg-white rounded-xl border border-gray-100 shadow-sm p-4 hover:border-indigo-200
                  hover:shadow-md transition space-y-2">
            <div class="flex items-start justify-between gap-2">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $ticket->subject }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $ticket->booking_code }}</p>
                </div>
                <div class="flex gap-1.5 flex-shrink-0">
                    @if($ticket->priority === 'urgent')
                    <span class="px-2 py-0.5 text-xs rounded-full font-medium bg-red-100 text-red-600">Urgent</span>
                    @endif
                    <span class="px-2 py-0.5 text-xs rounded-full font-medium {{ $ticket->statusBadgeClass() }}">
                        {{ $ticket->statusLabel() }}
                    </span>
                </div>
            </div>
            <p class="text-xs text-gray-500 line-clamp-2">{{ $ticket->message }}</p>
            <p class="text-xs text-gray-400">
                {{ $ticket->created_at->locale('id')->diffForHumans() }}
                @if(count($ticket->replies ?? []) > 0)
                · {{ count($ticket->replies) }} balasan
                @endif
            </p>
        </a>
        @empty
        <div class="bg-white rounded-xl border border-gray-100 p-10 text-center">
            <div class="w-12 h-12 rounded-xl bg-gray-50 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                </svg>
            </div>
            <p class="text-sm text-gray-400">Belum ada tiket bantuan.</p>
        </div>
        @endforelse

        {{ $tickets->links() }}
    </div>
</x-app-layout>