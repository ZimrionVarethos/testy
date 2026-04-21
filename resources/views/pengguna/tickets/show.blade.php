{{-- resources/views/pengguna/tickets/show.blade.php --}}
<x-app-layout>
    <x-slot name="header">Detail Tiket</x-slot>

    <div class="py-6 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <a href="{{ route('tickets.index') }}" class="text-sm text-indigo-500 hover:underline">← Tiket Saya</a>

        @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-lg px-4 py-3">
            {{ $errors->first() }}
        </div>
        @endif

        {{-- Header tiket --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-3">
            <div class="flex items-start justify-between gap-2">
                <h2 class="font-semibold text-gray-800 text-base flex-1">{{ $ticket->subject }}</h2>
                <div class="flex gap-1.5">
                    @if($ticket->priority === 'urgent')
                    <span class="px-2 py-0.5 text-xs rounded-full font-medium bg-red-100 text-red-600">Urgent</span>
                    @endif
                    <span class="px-2 py-0.5 text-xs rounded-full font-medium {{ $ticket->statusBadgeClass() }}">
                        {{ $ticket->statusLabel() }}
                    </span>
                </div>
            </div>
            <div class="text-xs text-gray-400 flex gap-3">
                <span>Pesanan: <span class="font-medium text-gray-600">{{ $ticket->booking_code }}</span></span>
                <span>Dibuat: {{ $ticket->created_at->format('d M Y, H:i') }}</span>
            </div>
        </div>

        {{-- Thread percakapan --}}
        <div class="space-y-3">
            {{-- Pesan pertama dari user --}}
            <div class="flex flex-col items-end">
                <div class="max-w-[85%] bg-indigo-600 text-white rounded-2xl rounded-tr-sm px-4 py-3">
                    <p class="text-xs font-semibold mb-1 text-indigo-200">{{ $ticket->user_name }} (Anda)</p>
                    <p class="text-sm leading-relaxed whitespace-pre-line">{{ $ticket->message }}</p>
                </div>
                <p class="text-xs text-gray-400 mt-1 mr-1">{{ $ticket->created_at->format('d M Y, H:i') }}</p>
            </div>

            {{-- Balasan --}}
            @foreach($ticket->replies ?? [] as $reply)
            @php $isUser = $reply['sender_role'] === 'pengguna'; @endphp
            <div class="flex flex-col {{ $isUser ? 'items-end' : 'items-start' }}">
                <div class="max-w-[85%] rounded-2xl px-4 py-3
                    {{ $isUser
                        ? 'bg-indigo-600 text-white rounded-tr-sm'
                        : 'bg-white border border-gray-200 text-gray-800 rounded-tl-sm shadow-sm' }}">
                    <p class="text-xs font-semibold mb-1
                        {{ $isUser ? 'text-indigo-200' : 'text-gray-500' }}">
                        {{ $reply['sender_name'] }}
                        {{ !$isUser ? '(Admin)' : '(Anda)' }}
                    </p>
                    <p class="text-sm leading-relaxed whitespace-pre-line">{{ $reply['message'] }}</p>
                </div>
                <p class="text-xs text-gray-400 mt-1 {{ $isUser ? 'mr-1' : 'ml-1' }}">
                    {{ \Carbon\Carbon::parse($reply['created_at'])->format('d M Y, H:i') }}
                </p>
            </div>
            @endforeach
        </div>

        {{-- Admin notes (jika ada) --}}
        @if($ticket->admin_notes)
        <div class="bg-amber-50 border border-amber-100 rounded-xl px-4 py-3">
            <p class="text-xs font-semibold text-amber-700 mb-1">Catatan Admin</p>
            <p class="text-sm text-amber-800 whitespace-pre-line">{{ $ticket->admin_notes }}</p>
        </div>
        @endif

        {{-- Form balas (hanya jika tiket masih open) --}}
        @if($ticket->isOpen())
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Tambah Balasan</h3>
            <form method="POST" action="{{ route('tickets.reply', $ticket->_id) }}" class="space-y-3">
                @csrf
                <textarea name="message" rows="4"
                          class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm
                                 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400
                                 resize-none"
                          placeholder="Tulis balasan Anda..."
                          maxlength="2000" required></textarea>
                <button type="submit"
                        class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg
                               hover:bg-indigo-700 transition">
                    Kirim Balasan
                </button>
            </form>
        </div>
        @else
        <div class="text-center py-4 text-sm text-gray-400">
            Tiket ini sudah {{ $ticket->statusLabel() }} dan tidak bisa dibalas lagi.
        </div>
        @endif
    </div>
</x-app-layout>