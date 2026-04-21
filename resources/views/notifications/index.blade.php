{{-- resources/views/notifications/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Notifikasi</x-slot>

    <div class="py-6 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4"
         x-data="{
             selected: [],
             selectMode: false,
             toggleSelect(id) {
                 if (this.selected.includes(id)) {
                     this.selected = this.selected.filter(i => i !== id);
                 } else {
                     this.selected.push(id);
                 }
             },
             selectAll() {
                 const ids = [...document.querySelectorAll('[data-notif-id]')].map(el => el.dataset.notifId);
                 this.selected = ids;
             },
             deselectAll() { this.selected = []; },
             isSelected(id) { return this.selected.includes(id); },
             get hasSelected() { return this.selected.length > 0; },
         }">

        @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
        @endif

        {{-- ── Top bar: filter tabs + actions ── --}}
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">

            {{-- Filter tabs --}}
            <div class="flex gap-1 bg-white border border-gray-100 rounded-xl p-1 shadow-sm flex-1">
                @foreach([
                    'all'    => ['label' => 'Semua',    'count' => $counts['all']],
                    'unread' => ['label' => 'Belum Baca', 'count' => $counts['unread']],
                    'read'   => ['label' => 'Sudah Baca', 'count' => $counts['read']],
                ] as $key => $tab)
                <a href="{{ route('notifications.index', ['filter' => $key]) }}"
                   class="flex-1 text-center px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center justify-center gap-1.5
                          {{ $filter === $key
                              ? 'bg-indigo-600 text-white'
                              : 'text-gray-500 hover:bg-gray-50' }}">
                    {{ $tab['label'] }}
                    @if($tab['count'] > 0)
                    <span class="rounded-full px-1.5 py-0.5 text-[10px] font-bold leading-none
                        {{ $filter === $key ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500' }}">
                        {{ $tab['count'] }}
                    </span>
                    @endif
                </a>
                @endforeach
            </div>

            {{-- Action buttons --}}
            <div class="flex gap-2 flex-shrink-0">
                {{-- Toggle select mode --}}
                <button @click="selectMode = !selectMode; if(!selectMode) selected = []"
                        :class="selectMode ? 'bg-indigo-50 text-indigo-600 border-indigo-200' : 'bg-white text-gray-500 border-gray-200'"
                        class="px-3 py-1.5 rounded-lg border text-xs font-medium hover:border-indigo-300 transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span x-text="selectMode ? 'Batal' : 'Pilih'"></span>
                </button>

                {{-- Mark all read --}}
                @if($counts['unread'] > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="px-3 py-1.5 rounded-lg border border-gray-200 bg-white text-xs font-medium
                                   text-gray-500 hover:border-indigo-300 hover:text-indigo-600 transition flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Baca Semua
                    </button>
                </form>
                @endif

                {{-- Delete all --}}
                @if($counts['all'] > 0)
                <form method="POST" action="{{ route('notifications.destroy-all') }}"
                      onsubmit="return confirm('Hapus semua notifikasi? Tindakan ini tidak bisa dibatalkan.')">
                    @csrf @method('DELETE')
                    <button class="px-3 py-1.5 rounded-lg border border-gray-200 bg-white text-xs font-medium
                                   text-gray-500 hover:border-red-300 hover:text-red-500 transition flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Hapus Semua
                    </button>
                </form>
                @endif
            </div>
        </div>

        {{-- ── Bulk action bar (muncul saat select mode aktif) ── --}}
        <div x-show="selectMode"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-3 flex items-center gap-3"
             style="display:none">
            <button @click="selectAll()"
                    class="text-xs text-indigo-600 font-medium hover:underline">Pilih Semua</button>
            <span class="text-gray-300">|</span>
            <button @click="deselectAll()"
                    class="text-xs text-gray-500 hover:underline">Batal Pilih</button>
            <span class="text-xs text-gray-500 ml-auto" x-text="selected.length + ' dipilih'"></span>

            <form method="POST" action="{{ route('notifications.destroy-selected') }}"
                  id="bulk-delete-form"
                  onsubmit="return confirm('Hapus notifikasi yang dipilih?')">
                @csrf @method('DELETE')
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
                <button type="submit"
                        :disabled="!hasSelected"
                        :class="hasSelected ? 'bg-red-500 text-white hover:bg-red-600' : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6"/>
                    </svg>
                    Hapus Dipilih
                </button>
            </form>
        </div>

        {{-- ── List notifikasi ── --}}
        @forelse($notifications as $n)
        @php
            // Resolve URL — pakai action_url jika ada, fallback by type
            $notifUrl = $n->action_url ?? null;
            if (!$notifUrl && $n->related_id) {
                $role = Auth::user()->role ?? 'pengguna';
                $notifUrl = match($n->type ?? 'system') {
                    'booking' => ($role === 'admin'
                        ? route('admin.bookings.show', $n->related_id)
                        : route('bookings.show', $n->related_id)),
                    'payment' => ($role === 'admin'
                        ? route('admin.payments.show', $n->related_id)
                        : route('payments.show', $n->related_id)),
                    default => null,
                };
            }

            $iconCfg = match($n->type ?? 'system') {
                'booking' => ['bg' => '#eff6ff', 'color' => '#2563eb',
                    'path' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/>'],
                'payment' => ['bg' => '#f0fdf4', 'color' => '#16a34a',
                    'path' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                'warning' => ['bg' => '#fffbeb', 'color' => '#d97706',
                    'path' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>'],
                'success' => ['bg' => '#f0fdf4', 'color' => '#16a34a',
                    'path' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                default   => ['bg' => 'rgba(17,24,39,0.05)', 'color' => 'rgba(17,24,39,0.45)',
                    'path' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
            };
        @endphp

        <div class="group relative bg-white rounded-xl border transition
                    {{ $n->is_read ? 'border-gray-100' : 'border-indigo-200 shadow-sm' }}"
             :class="isSelected('{{ $n->_id }}') ? 'ring-2 ring-indigo-400 border-indigo-300' : ''"
             data-notif-id="{{ $n->_id }}">

            {{-- Checkbox overlay (select mode) --}}
            <div x-show="selectMode"
                 @click="toggleSelect('{{ $n->_id }}')"
                 class="absolute inset-0 z-10 rounded-xl cursor-pointer flex items-start justify-end p-3"
                 style="display:none">
                <div :class="isSelected('{{ $n->_id }}')
                        ? 'bg-indigo-600 border-indigo-600'
                        : 'bg-white border-gray-300 group-hover:border-indigo-400'"
                     class="w-5 h-5 rounded-md border-2 flex items-center justify-center transition flex-shrink-0">
                    <svg x-show="isSelected('{{ $n->_id }}')" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>

            <div class="flex items-start gap-3 p-4">

                {{-- Unread dot --}}
                <div class="flex-shrink-0 mt-0.5">
                    <div class="w-2 h-2 rounded-full mt-1
                                {{ $n->is_read ? 'bg-gray-200' : 'bg-indigo-500' }}"></div>
                </div>

                {{-- Icon --}}
                <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center"
                     style="background:{{ $iconCfg['bg'] }};color:{{ $iconCfg['color'] }}">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        {!! $iconCfg['path'] !!}
                    </svg>
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    @if($notifUrl)
                    <a href="{{ $notifUrl }}"
                       class="block group/link"
                       @if(!$n->is_read)
                       onclick="fetch('{{ route('notifications.read', $n->_id) }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'}})"
                       @endif>
                        <p class="text-sm font-semibold text-gray-800 group-hover/link:text-indigo-600 transition">
                            {{ $n->title }}
                        </p>
                        <p class="text-sm text-gray-500 mt-0.5 leading-relaxed">{{ $n->message }}</p>
                    </a>
                    @else
                    <p class="text-sm font-semibold text-gray-800">{{ $n->title }}</p>
                    <p class="text-sm text-gray-500 mt-0.5 leading-relaxed">{{ $n->message }}</p>
                    @endif

                    <div class="flex items-center gap-3 mt-1.5">
                        <span class="text-xs text-gray-400">
                            {{ \Carbon\Carbon::parse($n->created_at)->locale('id')->diffForHumans() }}
                        </span>
                        @if(!$n->is_read)
                        <form method="POST" action="{{ route('notifications.read', $n->_id) }}">
                            @csrf
                            <button class="text-xs text-indigo-400 hover:text-indigo-600 font-medium transition">
                                ✓ Tandai dibaca
                            </button>
                        </form>
                        @else
                        <span class="text-xs text-gray-300 flex items-center gap-0.5">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            Dibaca
                        </span>
                        @endif
                    </div>
                </div>

                {{-- Delete button (always visible on hover, hidden in select mode) --}}
                <div x-show="!selectMode" class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                    <form method="POST" action="{{ route('notifications.destroy', $n->_id) }}"
                          onsubmit="return confirm('Hapus notifikasi ini?')">
                        @csrf @method('DELETE')
                        <button class="w-7 h-7 rounded-lg flex items-center justify-center
                                       text-gray-300 hover:text-red-400 hover:bg-red-50 transition">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl border border-gray-100 py-16 text-center">
            <div class="w-12 h-12 rounded-xl bg-gray-50 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-400">
                @if($filter === 'unread') Tidak ada notifikasi yang belum dibaca.
                @elseif($filter === 'read') Tidak ada notifikasi yang sudah dibaca.
                @else Belum ada notifikasi.
                @endif
            </p>
        </div>
        @endforelse

        {{ $notifications->links() }}
    </div>
</x-app-layout>