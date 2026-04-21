@php
    $chatActive  = in_array($booking->status, ['confirmed', 'ongoing']);
    $chatHistory = in_array($booking->status, ['completed', 'cancelled']);
    $showChat    = $chatActive || $chatHistory;
    $existingRating = null;
    $showRating     = false;
    if ($senderRole === 'pengguna' && $booking->status === 'completed') {
        $existingRating = \App\Models\Rating::forBooking((string) $booking->_id);
        $showRating     = true;
    }
@endphp
@if($showChat)
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="flex items-center gap-2.5 px-4 py-3 border-b border-gray-100 bg-gray-50">
        <div class="w-2 h-2 rounded-full flex-shrink-0 {{ $chatActive ? 'bg-green-400 animate-pulse' : 'bg-gray-300' }}"></div>
        <h4 class="text-sm font-semibold text-gray-700">
            @if($senderRole === 'pengguna')
                {{ $chatHistory ? 'Riwayat Chat' : 'Chat' }} dengan Driver
                @if(!empty($booking->driver['name']))
                <span class="text-indigo-600">{{ $booking->driver['name'] }}</span>
                @endif
            @else
                {{ $chatHistory ? 'Riwayat Chat' : 'Chat' }} dengan Penumpang
                <span class="text-indigo-600">{{ $booking->user['name'] }}</span>
            @endif
        </h4>
        @if($chatHistory)
        <span class="ml-auto text-[10px] px-2 py-0.5 bg-gray-100 text-gray-500 rounded-full font-medium">Read-only</span>
        @endif
    </div>
    @if($chatActive)
    <div x-data="chatRoom({ fetchUrl: '{{ $fetchUrl }}', postUrl: '{{ $postUrl }}', csrfToken: '{{ csrf_token() }}', senderRole: '{{ $senderRole }}' })" x-init="init()">
        <div class="h-72 overflow-y-auto px-4 py-3 space-y-2 flex flex-col" x-ref="messageBox">
            <template x-if="loading && messages.length === 0">
                <div class="flex-1 flex items-center justify-center">
                    <div class="flex gap-1.5">
                        <div class="w-1.5 h-1.5 rounded-full bg-gray-300 animate-bounce" style="animation-delay:0ms"></div>
                        <div class="w-1.5 h-1.5 rounded-full bg-gray-300 animate-bounce" style="animation-delay:150ms"></div>
                        <div class="w-1.5 h-1.5 rounded-full bg-gray-300 animate-bounce" style="animation-delay:300ms"></div>
                    </div>
                </div>
            </template>
            <template x-if="!loading && messages.length === 0">
                <div class="flex-1 flex flex-col items-center justify-center text-center py-6">
                    <p class="text-xs text-gray-400">Belum ada pesan. Mulai percakapan!</p>
                </div>
            </template>
            <template x-for="(msg, idx) in messages" :key="msg.id">
                <div>
                    <template x-if="idx === 0 || messages[idx-1].date !== msg.date">
                        <div class="flex items-center gap-2 my-2">
                            <div class="flex-1 h-px bg-gray-100"></div>
                            <span class="text-[10px] text-gray-400 font-medium" x-text="msg.date"></span>
                            <div class="flex-1 h-px bg-gray-100"></div>
                        </div>
                    </template>
                    <div :class="msg.sender_role === '{{ $senderRole }}' ? 'flex flex-col items-end' : 'flex flex-col items-start'">
                        <template x-if="idx === 0 || messages[idx-1].sender_role !== msg.sender_role">
                            <p class="text-[10px] text-gray-400 mb-0.5 px-1" x-text="msg.sender_name"></p>
                        </template>
                        <div class="max-w-[78%] px-3 py-2 rounded-2xl text-sm leading-relaxed break-words"
                             :class="msg.sender_role === '{{ $senderRole }}' ? 'bg-indigo-600 text-white rounded-tr-sm' : 'bg-gray-100 text-gray-800 rounded-tl-sm'"
                             x-text="msg.message"></div>
                        <div class="flex items-center gap-1 mt-0.5 px-1">
                            <span class="text-[10px] text-gray-400" x-text="msg.time"></span>
                            <template x-if="msg.sender_role === '{{ $senderRole }}'">
                                <svg x-show="msg.is_read" class="w-3 h-3 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <div class="border-t border-gray-100 px-3 py-2.5 flex gap-2 items-end bg-gray-50">
            <textarea x-model="newMessage" @keydown.enter.prevent="if(!$event.shiftKey) sendMessage()" placeholder="Tulis pesan... (Enter kirim)" rows="1" :disabled="sending" class="flex-1 resize-none rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 disabled:opacity-50 max-h-24" style="min-height:38px" @input="autoResize($event.target)"></textarea>
            <button @click="sendMessage()" :disabled="sending || !newMessage.trim()" class="flex-shrink-0 w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center hover:bg-indigo-700 disabled:opacity-40 transition">
                <svg class="w-4 h-4 rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            </button>
        </div>
        <div x-show="errorMsg" x-transition style="display:none" class="px-4 py-2 bg-red-50 border-t border-red-100 text-xs text-red-500" x-text="errorMsg"></div>
    </div>
    @else
    @php
        $historyMessages = \App\Models\ChatMessage::forBooking((string) $booking->_id);
        $prevDate = null;
        $prevRole = null;
    @endphp
    <div class="h-72 overflow-y-auto px-4 py-3 space-y-2 flex flex-col bg-gray-50/40" id="ch-{{ (string) $booking->_id }}">
        @if($historyMessages->isEmpty())
        <div class="flex-1 flex flex-col items-center justify-center text-center py-6">
            <p class="text-xs text-gray-400">Tidak ada pesan dalam perjalanan ini.</p>
        </div>
        @else
            @foreach($historyMessages as $msg)
            @php
                $msgDate  = $msg->created_at->format('d M Y');
                $isSelf   = $msg->sender_role === $senderRole;
                $showDate = $msgDate !== $prevDate;
                $showName = $msg->sender_role !== $prevRole;
                $prevDate = $msgDate;
                $prevRole = $msg->sender_role;
            @endphp
            @if($showDate)
            <div class="flex items-center gap-2 my-2">
                <div class="flex-1 h-px bg-gray-200"></div>
                <span class="text-[10px] text-gray-400 font-medium">{{ $msgDate }}</span>
                <div class="flex-1 h-px bg-gray-200"></div>
            </div>
            @endif
            <div class="flex flex-col {{ $isSelf ? 'items-end' : 'items-start' }}">
                @if($showName)
                <p class="text-[10px] text-gray-400 mb-0.5 px-1">{{ $msg->sender_name }}</p>
                @endif
                <div class="max-w-[78%] px-3 py-2 rounded-2xl text-sm leading-relaxed break-words {{ $isSelf ? 'bg-indigo-500/80 text-white rounded-tr-sm' : 'bg-white border border-gray-200 text-gray-700 rounded-tl-sm' }}">{{ $msg->message }}</div>
                <span class="text-[10px] text-gray-400 mt-0.5 px-1">{{ $msg->created_at->format('H:i') }}</span>
            </div>
            @endforeach
        @endif
    </div>
    <script>document.addEventListener('DOMContentLoaded',function(){var e=document.getElementById('ch-{{ (string) $booking->_id }}');if(e)e.scrollTop=e.scrollHeight;});</script>
    @if($showRating)
    <div class="border-t border-gray-100">
        @if($existingRating)
        <div class="px-5 py-5 text-center space-y-1.5">
            <p class="text-2xl">🎉</p>
            <p class="text-sm font-semibold text-gray-700">Pesanan telah selesai</p>
            <p class="text-xs text-gray-400">Terima kasih telah menggunakan layanan kami.</p>
            <p class="text-base text-amber-400 tracking-widest mt-1">{{ $existingRating->starLabel() }}</p>
            @if($existingRating->comment)
            <p class="text-xs text-gray-500 italic">"{{ $existingRating->comment }}"</p>
            @endif
            <a href="{{ route('vehicles.index') }}" class="inline-block mt-3 px-5 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 transition">Pesan Lagi 🚗</a>
        </div>
        @else
        <div class="px-4 py-4 space-y-3">
            <div class="text-center">
                <p class="text-sm font-semibold text-gray-700">Bagaimana perjalanan Anda?</p>
                <p class="text-xs text-gray-400 mt-0.5">Beri rating untuk driver <span class="font-medium text-gray-600">{{ $booking->driver['name'] ?? '' }}</span></p>
            </div>
            <form method="POST" action="{{ route('bookings.rating.store', (string) $booking->_id) }}" x-data="{ score: 0, hover: 0 }" class="space-y-3">
                @csrf
                <div class="flex justify-center gap-1">
                    @for($i = 1; $i <= 5; $i++)
                    <button type="button" @click="score = {{ $i }}" @mouseenter="hover = {{ $i }}" @mouseleave="hover = 0" class="text-3xl transition-transform hover:scale-110 focus:outline-none leading-none">
                        <span :class="(hover || score) >= {{ $i }} ? 'text-amber-400' : 'text-gray-200'">★</span>
                    </button>
                    @endfor
                </div>
                <input type="hidden" name="score" :value="score">
                <textarea name="comment" rows="2" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 resize-none" placeholder="Tulis komentar (opsional)..." maxlength="500"></textarea>
                <button type="submit" :disabled="score === 0" :class="score > 0 ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'bg-gray-100 text-gray-400 cursor-not-allowed'" class="w-full py-2.5 text-sm font-medium rounded-lg transition">
                    <span x-text="score === 0 ? 'Pilih bintang terlebih dahulu' : 'Kirim Rating'"></span>
                </button>
            </form>
        </div>
        @endif
    </div>
    @endif
    @if($senderRole === 'driver' && $booking->status === 'completed')
    @php $driverRating = \App\Models\Rating::forBooking((string) $booking->_id); @endphp
    <div class="border-t border-gray-100 px-5 py-4 text-center space-y-1">
        <p class="text-sm font-semibold text-gray-700">Perjalanan selesai</p>
        @if($driverRating)
        <p class="text-base text-amber-400 tracking-widest">{{ $driverRating->starLabel() }}</p>
        @if($driverRating->comment)
        <p class="text-xs text-gray-400 italic">"{{ $driverRating->comment }}"</p>
        @endif
        @else
        <p class="text-xs text-gray-400">Menunggu rating dari penumpang.</p>
        @endif
    </div>
    @endif
    @if($booking->status === 'cancelled')
    <div class="border-t border-gray-100 px-5 py-3 text-center">
        <p class="text-sm text-gray-400">Pesanan ini dibatalkan.</p>
    </div>
    @endif
    @endif
</div>
@endif
@once
@push('scripts')
<script>
function chatRoom({ fetchUrl, postUrl, csrfToken, senderRole }) {
    return {
        messages: [], newMessage: '', sending: false, loading: true, errorMsg: '', pollTimer: null,
        init() { this.fetchMessages(); this.pollTimer = setInterval(() => this.fetchMessages(), 5000); },
        async fetchMessages() {
            try {
                const res = await fetch(fetchUrl, { headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, credentials: 'same-origin' });
                if (!res.ok) throw new Error();
                const data = await res.json();
                const atBottom = this.isAtBottom();
                this.messages = data.messages; this.loading = false; this.errorMsg = '';
                if (atBottom) this.$nextTick(() => this.scrollToBottom());
            } catch { this.loading = false; this.errorMsg = 'Gagal memuat pesan.'; }
        },
        async sendMessage() {
            const text = this.newMessage.trim();
            if (!text || this.sending) return;
            this.sending = true; this.errorMsg = '';
            try {
                const res = await fetch(postUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, credentials: 'same-origin', body: JSON.stringify({ message: text }) });
                if (!res.ok) { const e = await res.json().catch(() => ({})); throw new Error(e.message || 'Gagal.'); }
                const data = await res.json();
                this.messages.push(data.message); this.newMessage = '';
                this.$nextTick(() => { this.scrollToBottom(); const ta = this.$el.querySelector('textarea'); if (ta) ta.style.height = '38px'; });
            } catch(e) { this.errorMsg = e.message || 'Gagal mengirim pesan.'; }
            finally { this.sending = false; }
        },
        scrollToBottom() { const b = this.$refs.messageBox; if (b) b.scrollTop = b.scrollHeight; },
        isAtBottom() { const b = this.$refs.messageBox; if (!b) return true; return b.scrollHeight - b.scrollTop - b.clientHeight < 60; },
        autoResize(el) { el.style.height = '38px'; el.style.height = Math.min(el.scrollHeight, 96) + 'px'; },
    };
}
</script>
@endpush
@endonce