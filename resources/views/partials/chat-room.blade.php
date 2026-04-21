{{--
    resources/views/partials/chat-room.blade.php

    Props (variabel yang harus dikirim dari controller / view yang meng-include):
      $booking      — Booking model
      $senderRole   — 'pengguna' | 'driver'
      $fetchUrl     — URL GET polling (route chat.index / driver.chat.index)
      $postUrl      — URL POST kirim pesan (route chat.store / driver.chat.store)
--}}

@if(in_array($booking->status, ['confirmed', 'ongoing']))
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
     x-data="chatRoom({
         fetchUrl:   '{{ $fetchUrl }}',
         postUrl:    '{{ $postUrl }}',
         csrfToken:  '{{ csrf_token() }}',
         senderId:   '{{ Auth::id() }}',
         senderRole: '{{ $senderRole }}',
     })"
     x-init="init()">

    {{-- Header --}}
    <div class="flex items-center gap-2.5 px-4 py-3 border-b border-gray-100 bg-gray-50">
        <div class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></div>
        <h4 class="text-sm font-semibold text-gray-700">
            Chat
            @if($senderRole === 'pengguna')
                dengan Driver
                @if(!empty($booking->driver['name']))
                    <span class="text-indigo-600">{{ $booking->driver['name'] }}</span>
                @endif
            @else
                dengan Penumpang
                <span class="text-indigo-600">{{ $booking->user['name'] }}</span>
            @endif
        </h4>
        <span class="ml-auto text-xs text-gray-400" x-text="statusText"></span>
    </div>

    {{-- Messages area --}}
    <div class="h-72 overflow-y-auto px-4 py-3 space-y-2 flex flex-col"
         id="chat-messages-{{ (string) $booking->_id }}"
         x-ref="messageBox">

        {{-- Loading state --}}
        <template x-if="loading && messages.length === 0">
            <div class="flex-1 flex items-center justify-center">
                <div class="flex gap-1.5">
                    <div class="w-1.5 h-1.5 rounded-full bg-gray-300 animate-bounce" style="animation-delay:0ms"></div>
                    <div class="w-1.5 h-1.5 rounded-full bg-gray-300 animate-bounce" style="animation-delay:150ms"></div>
                    <div class="w-1.5 h-1.5 rounded-full bg-gray-300 animate-bounce" style="animation-delay:300ms"></div>
                </div>
            </div>
        </template>

        {{-- Empty state --}}
        <template x-if="!loading && messages.length === 0">
            <div class="flex-1 flex flex-col items-center justify-center text-center py-6">
                <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center mb-2">
                    <svg class="w-5 h-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <p class="text-xs text-gray-400">Belum ada pesan. Mulai percakapan!</p>
            </div>
        </template>

        {{-- Messages --}}
        <template x-for="(msg, idx) in messages" :key="msg.id">
            <div>
                {{-- Date separator --}}
                <template x-if="idx === 0 || messages[idx-1].date !== msg.date">
                    <div class="flex items-center gap-2 my-2">
                        <div class="flex-1 h-px bg-gray-100"></div>
                        <span class="text-[10px] text-gray-400 font-medium" x-text="msg.date"></span>
                        <div class="flex-1 h-px bg-gray-100"></div>
                    </div>
                </template>

                {{-- Bubble --}}
                <div :class="msg.sender_role === '{{ $senderRole }}'
                        ? 'flex flex-col items-end'
                        : 'flex flex-col items-start'">
                    {{-- Sender name (hanya tampil jika berbeda dari pengirim sebelumnya) --}}
                    <template x-if="idx === 0 || messages[idx-1].sender_role !== msg.sender_role">
                        <p class="text-[10px] text-gray-400 mb-0.5 px-1" x-text="msg.sender_name"></p>
                    </template>
                    <div class="max-w-[78%] px-3 py-2 rounded-2xl text-sm leading-relaxed break-words"
                         :class="msg.sender_role === '{{ $senderRole }}'
                             ? 'bg-indigo-600 text-white rounded-tr-sm'
                             : 'bg-gray-100 text-gray-800 rounded-tl-sm'"
                         x-text="msg.message">
                    </div>
                    <div class="flex items-center gap-1 mt-0.5 px-1">
                        <span class="text-[10px] text-gray-400" x-text="msg.time"></span>
                        {{-- Read receipt (hanya untuk pesan sendiri) --}}
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

    {{-- Input form --}}
    <div class="border-t border-gray-100 px-3 py-2.5 flex gap-2 items-end bg-gray-50">
        <textarea
            x-model="newMessage"
            @keydown.enter.prevent="if(!$event.shiftKey) sendMessage()"
            placeholder="Tulis pesan... (Enter kirim, Shift+Enter baris baru)"
            rows="1"
            :disabled="sending"
            class="flex-1 resize-none rounded-xl border border-gray-200 px-3 py-2 text-sm
                   focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400
                   disabled:opacity-50 max-h-24 overflow-y-auto"
            style="min-height:38px"
            @input="autoResize($event.target)"
        ></textarea>
        <button @click="sendMessage()"
                :disabled="sending || !newMessage.trim()"
                class="flex-shrink-0 w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center
                       hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed transition">
            <svg class="w-4 h-4 rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
            </svg>
        </button>
    </div>

    {{-- Error toast --}}
    <div x-show="errorMsg"
         x-transition
         class="px-4 py-2 bg-red-50 border-t border-red-100 text-xs text-red-500"
         style="display:none"
         x-text="errorMsg">
    </div>
</div>

@push('scripts')
<script>
function chatRoom({ fetchUrl, postUrl, csrfToken, senderId, senderRole }) {
    return {
        messages:   [],
        newMessage: '',
        sending:    false,
        loading:    true,
        errorMsg:   '',
        pollTimer:  null,
        statusText: 'Menghubungkan...',

        init() {
            this.fetchMessages();
            // Polling setiap 5 detik
            this.pollTimer = setInterval(() => this.fetchMessages(), 5000);
            // Bersihkan timer saat element di-destroy
            this.$el.addEventListener('remove', () => clearInterval(this.pollTimer));
        },

        async fetchMessages() {
            try {
                const res  = await fetch(fetchUrl, {
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('fetch failed');
                const data = await res.json();

                const wasAtBottom = this.isAtBottom();
                this.messages     = data.messages;
                this.loading      = false;
                this.statusText   = 'Aktif';
                this.errorMsg     = '';

                if (wasAtBottom) {
                    this.$nextTick(() => this.scrollToBottom());
                }
            } catch (e) {
                this.loading    = false;
                this.statusText = 'Gagal terhubung';
                this.errorMsg   = 'Gagal memuat pesan. Coba refresh halaman.';
            }
        },

        async sendMessage() {
            const text = this.newMessage.trim();
            if (!text || this.sending) return;

            this.sending    = true;
            this.errorMsg   = '';

            try {
                const res = await fetch(postUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept':       'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ message: text }),
                });

                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    throw new Error(err.message || 'Gagal mengirim pesan.');
                }

                const data = await res.json();
                this.messages.push(data.message);
                this.newMessage = '';
                this.$nextTick(() => {
                    this.scrollToBottom();
                    // Reset textarea height
                    const ta = this.$el.querySelector('textarea');
                    if (ta) { ta.style.height = '38px'; }
                });
            } catch (e) {
                this.errorMsg = e.message || 'Gagal mengirim pesan.';
            } finally {
                this.sending = false;
            }
        },

        scrollToBottom() {
            const box = this.$refs.messageBox;
            if (box) box.scrollTop = box.scrollHeight;
        },

        isAtBottom() {
            const box = this.$refs.messageBox;
            if (!box) return true;
            return box.scrollHeight - box.scrollTop - box.clientHeight < 60;
        },

        autoResize(el) {
            el.style.height = '38px';
            el.style.height = Math.min(el.scrollHeight, 96) + 'px';
        },
    };
}
</script>
@endpush
@endif