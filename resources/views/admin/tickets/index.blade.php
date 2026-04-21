{{-- resources/views/admin/tickets/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Tiket Bantuan</x-slot>

    <div class="py-6 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

        @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
        @endif

        {{-- Filter tabs --}}
        <div class="flex gap-1 bg-white border border-gray-100 rounded-xl p-1 shadow-sm overflow-x-auto">
            @foreach([
                'all'         => 'Semua',
                'open'        => 'Terbuka',
                'in_progress' => 'Diproses',
                'resolved'    => 'Selesai',
                'closed'      => 'Ditutup',
            ] as $key => $label)
            <a href="{{ route('admin.tickets.index', ['status' => $key]) }}"
               class="flex-shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-1.5
                      {{ $status === $key
                          ? 'bg-indigo-600 text-white'
                          : 'text-gray-500 hover:bg-gray-50' }}">
                {{ $label }}
                @if($counts[$key] > 0)
                <span class="rounded-full px-1.5 py-0.5 text-[10px] font-bold
                    {{ $status === $key ? 'bg-indigo-500 text-white' : 'bg-gray-100 text-gray-500' }}">
                    {{ $counts[$key] }}
                </span>
                @endif
            </a>
            @endforeach
        </div>

        {{-- Tabel tiket --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            @forelse($tickets as $ticket)
            <a href="{{ route('admin.tickets.show', $ticket->_id) }}"
               class="flex items-start gap-4 px-5 py-4 border-b border-gray-50 hover:bg-gray-50
                      transition last:border-b-0">
                {{-- Priority indicator --}}
                <div class="mt-0.5 flex-shrink-0">
                    @if($ticket->priority === 'urgent')
                    <div class="w-2 h-2 rounded-full bg-red-500"></div>
                    @else
                    <div class="w-2 h-2 rounded-full bg-gray-200"></div>
                    @endif
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-0.5">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $ticket->subject }}</p>
                        @if($ticket->priority === 'urgent')
                        <span class="px-2 py-0.5 text-[10px] rounded-full font-bold bg-red-100 text-red-600 flex-shrink-0">URGENT</span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-400">
                        {{ $ticket->user_name }} · {{ $ticket->booking_code }}
                        · {{ $ticket->created_at->locale('id')->diffForHumans() }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1 line-clamp-1">{{ $ticket->message }}</p>
                </div>

                {{-- Status + reply count --}}
                <div class="flex-shrink-0 flex flex-col items-end gap-1.5">
                    <span class="px-2 py-0.5 text-xs rounded-full font-medium {{ $ticket->statusBadgeClass() }}">
                        {{ $ticket->statusLabel() }}
                    </span>
                    @php $replyCount = count($ticket->replies ?? []); @endphp
                    @if($replyCount > 0)
                    <span class="text-xs text-gray-400">{{ $replyCount }} balasan</span>
                    @endif
                </div>
            </a>
            @empty
            <div class="py-16 text-center">
                <div class="w-12 h-12 rounded-xl bg-gray-50 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                </div>
                <p class="text-sm text-gray-400">Tidak ada tiket untuk filter ini.</p>
            </div>
            @endforelse
        </div>

        {{ $tickets->links() }}
    </div>
</x-app-layout>