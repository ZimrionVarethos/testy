{{-- resources/views/admin/tickets/show.blade.php --}}
<x-app-layout>
    <x-slot name="header">Detail Tiket</x-slot>

    <div class="py-6 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <a href="{{ route('admin.tickets.index') }}" class="text-sm text-indigo-500 hover:underline">← Semua Tiket</a>

        @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
        @endif

        {{-- Header tiket --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="flex-1">
                    <h2 class="font-semibold text-gray-800 text-base">{{ $ticket->subject }}</h2>
                    <p class="text-xs text-gray-400 mt-1">
                        Dari <span class="font-medium text-gray-600">{{ $ticket->user_name }}</span>
                        · Pesanan <span class="font-medium text-gray-600">{{ $ticket->booking_code }}</span>
                        · {{ $ticket->created_at->format('d M Y, H:i') }}
                    </p>
                </div>
                <div class="flex gap-1.5 flex-shrink-0">
                    @if($ticket->priority === 'urgent')
                    <span class="px-2 py-0.5 text-xs rounded-full font-bold bg-red-100 text-red-600">URGENT</span>
                    @endif
                    <span class="px-2 py-0.5 text-xs rounded-full font-medium {{ $ticket->statusBadgeClass() }}">
                        {{ $ticket->statusLabel() }}
                    </span>
                </div>
            </div>

            {{-- Ubah Status --}}
            <form method="POST" action="{{ route('admin.tickets.status', $ticket->_id) }}"
                  class="flex items-center gap-2 pt-3 border-t border-gray-50">
                @csrf
                <label class="text-xs text-gray-500 font-medium flex-shrink-0">Ubah Status:</label>
                <select name="status"
                        class="text-xs rounded-lg border border-gray-200 px-2 py-1.5
                               focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    <option value="open"        {{ $ticket->status === 'open'        ? 'selected' : '' }}>Terbuka</option>
                    <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>Diproses</option>
                    <option value="resolved"    {{ $ticket->status === 'resolved'    ? 'selected' : '' }}>Diselesaikan</option>
                    <option value="closed"      {{ $ticket->status === 'closed'      ? 'selected' : '' }}>Ditutup</option>
                </select>
                <button type="submit"
                        class="px-3 py-1.5 bg-gray-800 text-white text-xs font-medium rounded-lg hover:bg-gray-700 transition">
                    Simpan
                </button>
                @if($ticket->resolved_at)
                <span class="text-xs text-gray-400 ml-auto">
                    Diselesaikan {{ $ticket->resolved_at->format('d M Y, H:i') }}
                </span>
                @endif
            </form>
        </div>

        {{-- Thread percakapan --}}
        <div class="space-y-3">
            {{-- Pesan pertama --}}
            <div class="flex flex-col items-start">
                <div class="max-w-[85%] bg-white border border-gray-200 text-gray-800 rounded-2xl rounded-tl-sm shadow-sm px-4 py-3">
                    <p class="text-xs font-semibold mb-1 text-gray-500">{{ $ticket->user_name }} (Pengguna)</p>
                    <p class="text-sm leading-relaxed whitespace-pre-line">{{ $ticket->message }}</p>
                </div>
                <p class="text-xs text-gray-400 mt-1 ml-1">{{ $ticket->created_at->format('d M Y, H:i') }}</p>
            </div>

            {{-- Balasan --}}
            @foreach($ticket->replies ?? [] as $reply)
            @php $isAdmin = $reply['sender_role'] === 'admin'; @endphp
            <div class="flex flex-col {{ $isAdmin ? 'items-end' : 'items-start' }}">
                <div class="max-w-[85%] rounded-2xl px-4 py-3
                    {{ $isAdmin
                        ? 'bg-indigo-600 text-white rounded-tr-sm'
                        : 'bg-white border border-gray-200 text-gray-800 rounded-tl-sm shadow-sm' }}">
                    <p class="text-xs font-semibold mb-1 {{ $isAdmin ? 'text-indigo-200' : 'text-gray-500' }}">
                        {{ $reply['sender_name'] }} {{ $isAdmin ? '(Admin)' : '(Pengguna)' }}
                    </p>
                    <p class="text-sm leading-relaxed whitespace-pre-line">{{ $reply['message'] }}</p>
                </div>
                <p class="text-xs text-gray-400 mt-1 {{ $isAdmin ? 'mr-1' : 'ml-1' }}">
                    {{ \Carbon\Carbon::parse($reply['created_at'])->format('d M Y, H:i') }}
                </p>
            </div>
            @endforeach
        </div>

        {{-- Form Balas + Catatan Admin --}}
        @if($ticket->isOpen())
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4">
            <h3 class="text-sm font-semibold text-gray-700">Balas Tiket</h3>
            <form method="POST" action="{{ route('admin.tickets.reply', $ticket->_id) }}" class="space-y-3">
                @csrf
                <textarea name="message" rows="4"
                          class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm
                                 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400
                                 resize-none"
                          placeholder="Tulis balasan untuk pengguna..."
                          maxlength="2000" required></textarea>

                {{-- Catatan internal (tidak terlihat user) --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">
                        Catatan Internal
                        <span class="font-normal text-gray-400">(hanya terlihat oleh admin)</span>
                    </label>
                    <textarea name="admin_notes" rows="2"
                              class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm
                                     focus:outline-none focus:ring-2 focus:ring-amber-200 focus:border-amber-300
                                     resize-none bg-amber-50"
                              placeholder="Catatan internal (opsional)..."
                              maxlength="1000">{{ $ticket->admin_notes }}</textarea>
                </div>

                <button type="submit"
                        class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg
                               hover:bg-indigo-700 transition">
                    Kirim Balasan
                </button>
            </form>
        </div>
        @else
        {{-- Tetap tampilkan catatan admin jika ada meski tiket closed --}}
        @if($ticket->admin_notes)
        <div class="bg-amber-50 border border-amber-100 rounded-xl px-4 py-3">
            <p class="text-xs font-semibold text-amber-700 mb-1">Catatan Internal</p>
            <p class="text-sm text-amber-800 whitespace-pre-line">{{ $ticket->admin_notes }}</p>
        </div>
        @endif
        <div class="text-center py-4 text-sm text-gray-400">
            Tiket ini sudah {{ $ticket->statusLabel() }}.
        </div>
        @endif
    </div>
</x-app-layout>