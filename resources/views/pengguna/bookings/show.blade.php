{{-- resources/views/pengguna/bookings/show.blade.php --}}
<x-app-layout>
    <x-slot name="header">Detail Pesanan</x-slot>
    <div class="py-6 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <a href="{{ route('bookings.index') }}" class="text-sm text-indigo-500 hover:underline">← Pesanan Saya</a>

        @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
        @endif

        @php
            $activePayment = \App\Models\Payment::activeForBooking((string) $booking->_id);
            $sudahBayar    = $activePayment && $activePayment->isPaid();
        @endphp

        {{-- ══ BANNER STATUS SESUAI ALUR ════════════════════════════════
             Alur: pending (belum bayar) → pending (sudah bayar) → confirmed → ongoing → completed
        ════════════════════════════════════════════════════════════════ --}}
        @if($booking->status === 'pending' && !$sudahBayar)
        {{-- Step 1: Belum bayar --}}
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex gap-3 items-start">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-amber-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm text-amber-800 space-y-1">
                <p class="font-medium">Selesaikan pembayaran untuk mengkonfirmasi pesanan</p>
                <p>Batas waktu pembayaran:
                    <span class="font-semibold">{{ $booking->confirmationDeadline()->format('d M Y, H:i') }}</span>
                    <span class="text-amber-600">({{ $booking->confirmationDeadlineLabel() }})</span>
                </p>
                <p class="text-xs text-amber-600">Pesanan akan dibatalkan otomatis jika melewati batas waktu.</p>
            </div>
        </div>

        @elseif($booking->status === 'pending' && $sudahBayar)
        {{-- Step 1 selesai: Sudah bayar, nunggu admin --}}
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex gap-3 items-start">
            <svg class="w-5 h-5 text-blue-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm text-blue-800 space-y-1">
                <p class="font-medium">Pembayaran diterima — menunggu admin assign driver</p>
                <p class="text-xs text-blue-600">Admin akan segera mengkonfirmasi dan menugaskan driver untuk pesanan Anda.</p>
            </div>
        </div>

        @elseif($booking->status === 'confirmed')
        {{-- Step 2: Driver sudah di-assign --}}
        <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 flex gap-3 items-start">
            <svg class="w-5 h-5 text-indigo-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <div class="text-sm text-indigo-800 space-y-1">
                <p class="font-medium">Pesanan dikonfirmasi!</p>
                @if(!empty($booking->driver['name']))
                <p>Driver <span class="font-semibold">{{ $booking->driver['name'] }}</span>
                   akan menjemput pada
                   <span class="font-semibold">{{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y, H:i') }}</span>.
                </p>
                @endif
                <p class="text-xs text-indigo-600">Harap siap di lokasi penjemputan tepat waktu.</p>
            </div>
        </div>

        @elseif($booking->status === 'ongoing')
        {{-- Step 3: Sedang berjalan --}}
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex gap-3 items-start">
            <div class="h-2.5 w-2.5 rounded-full bg-green-500 animate-pulse shrink-0 mt-1.5"></div>
            <div class="text-sm text-green-800 space-y-1">
                <p class="font-medium">Perjalanan sedang berjalan</p>
                @if(!empty($booking->driver['phone']))
                <p class="text-xs">Hubungi driver:
                    <a href="tel:{{ $booking->driver['phone'] }}" class="text-green-700 font-semibold underline">
                        {{ $booking->driver['phone'] }}
                    </a>
                </p>
                @endif
            </div>
        </div>
        @endif

        {{-- ══ STATUS TRACKER (3 langkah utama) ═══════════════════════ --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            @php
                $steps = [
                    'pending'   => 'Bayar',
                    'confirmed' => 'Dikonfirmasi',
                    'ongoing'   => 'Berjalan',
                    'completed' => 'Selesai',
                ];
                $order      = array_keys($steps);
                $currentIdx = $booking->status === 'cancelled'
                    ? -1
                    : (array_search($booking->status, $order) ?? 0);
                // Kalau sudah bayar tapi masih pending, anggap step 1 selesai
                if ($booking->status === 'pending' && $sudahBayar) $currentIdx = 0;
            @endphp

            @if($booking->status === 'cancelled')
            <div class="flex items-center gap-2 text-red-500 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <div>
                    <span class="font-medium">Pesanan dibatalkan</span>
                    @if($booking->cancel_reason)
                    <p class="text-xs text-red-400 mt-0.5">{{ $booking->cancel_reason }}</p>
                    @endif
                </div>
            </div>
            @else
            <div class="flex items-center justify-between">
                @foreach($steps as $key => $label)
                @php $idx = array_search($key, $order); $done = $idx <= $currentIdx; @endphp
                <div class="flex flex-col items-center flex-1 {{ !$loop->last ? 'relative' : '' }}">
                    @if(!$loop->last)
                    <div class="absolute top-3 left-1/2 w-full h-0.5
                        {{ ($done && $idx < $currentIdx) ? 'bg-indigo-500' : 'bg-gray-200' }}"></div>
                    @endif
                    <div class="h-6 w-6 rounded-full z-10 flex items-center justify-center text-xs font-bold
                        {{ $done ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-400' }}">
                        {{ $done ? '✓' : ($idx + 1) }}
                    </div>
                    <p class="text-xs text-center mt-1 leading-tight hidden sm:block
                        {{ $done ? 'text-indigo-600 font-medium' : 'text-gray-400' }}">{{ $label }}</p>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Detail kendaraan & driver --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-2">
                <h4 class="font-semibold text-gray-700 border-b pb-2">Kendaraan</h4>
                <div class="text-sm space-y-1.5">
                    <div class="flex justify-between"><span class="text-gray-500">Nama</span><span>{{ $booking->vehicle['name'] }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Plat</span><span>{{ $booking->vehicle['plate_number'] }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Mulai</span><span>{{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y H:i') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Selesai</span><span>{{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y H:i') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Durasi</span><span>{{ $booking->duration_days }} hari</span></div>
                    <div class="flex justify-between font-semibold">
                        <span class="text-gray-700">Total</span>
                        <span class="text-indigo-600">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-2">
                <h4 class="font-semibold text-gray-700 border-b pb-2">Driver</h4>
                @if(!empty($booking->driver['driver_id']))
                <div class="text-sm space-y-1.5">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Nama</span><span>{{ $booking->driver['name'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Telepon</span>
                        <a href="tel:{{ $booking->driver['phone'] }}" class="text-indigo-500">{{ $booking->driver['phone'] }}</a>
                    </div>
                </div>
                @else
                <p class="text-sm text-gray-400">Driver akan ditugaskan oleh admin setelah pembayaran dikonfirmasi.</p>
                @endif

                <h4 class="font-semibold text-gray-700 border-b pb-2 pt-2">Penjemputan</h4>
                <p class="text-sm text-gray-600">{{ $booking->pickup['address'] }}</p>
                @if($booking->notes)
                <p class="text-xs text-gray-400 mt-1">Catatan: {{ $booking->notes }}</p>
                @endif
            </div>
        </div>

        {{-- ══ AKSI PEMBAYARAN ═══════════════════════════════════════ --}}
        @if(!in_array($booking->status, ['ongoing', 'completed', 'cancelled']))
            @if($sudahBayar)
                <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-sm text-green-700 flex gap-2 items-center">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Pembayaran lunas. Menunggu admin mengkonfirmasi pesanan.
                </div>
            @elseif($activePayment && $activePayment->isPending() && !$activePayment->isExpired())
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 space-y-3">
                    <p class="text-sm text-amber-800 font-medium">Pembayaran belum selesai</p>
                    <p class="text-xs text-amber-700">
                        Selesaikan sebelum
                        <span class="font-semibold">{{ \Carbon\Carbon::parse($activePayment->expired_at)->format('d M Y, H:i \W\I\B') }}</span>
                        · {{ $activePayment->expiryLabel() }}
                    </p>
                    <a href="{{ route('bookings.pay', $booking->_id) }}"
                       class="block w-full text-center py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                        Lanjutkan Pembayaran
                    </a>
                </div>
            @else
                @if($activePayment && $activePayment->isExpired())
                <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-600 mb-2">
                    Batas waktu pembayaran sebelumnya sudah habis.
                </div>
                @endif
                <a href="{{ route('bookings.pay', $booking->_id) }}"
                   class="block w-full text-center py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    Bayar Sekarang
                </a>
            @endif

            {{-- Tiket bantuan — hanya muncul kalau sudah bayar --}}
            @if($sudahBayar)
                @php
                    $existingTicket = \App\Models\Ticket::where('booking_id', (string) $booking->_id)
                        ->whereIn('status', ['open', 'in_progress'])->first();
                @endphp
                @if($existingTicket)
                <a href="{{ route('tickets.show', $existingTicket->_id) }}"
                   class="flex items-center justify-center gap-2 w-full py-2.5 bg-amber-50 text-amber-700
                          border border-amber-200 rounded-lg hover:bg-amber-100 transition text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                    Lihat Tiket Bantuan Aktif
                </a>
                @else
                <a href="{{ route('tickets.create', $booking->_id) }}"
                   class="flex items-center justify-center gap-2 w-full py-2.5 bg-amber-50 text-amber-700
                          border border-amber-200 rounded-lg hover:bg-amber-100 transition text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                    Butuh Bantuan? Buka Tiket
                </a>
                @endif
            @else
            {{-- Belum bayar = masih bisa cancel --}}
            <form method="POST" action="{{ route('bookings.destroy', $booking->_id) }}"
                  onsubmit="return confirm('Batalkan pesanan ini?')">
                @csrf @method('DELETE')
                <button class="w-full py-2.5 bg-red-50 text-red-500 border border-red-200 rounded-lg hover:bg-red-100 transition text-sm">
                    Batalkan Pesanan
                </button>
            </form>
            @endif
        @endif

        {{-- Chat Room --}}
        @include('partials.chat-room', [
            'booking'    => $booking,
            'senderRole' => 'pengguna',
            'fetchUrl'   => route('chat.index', $booking->_id),
            'postUrl'    => route('chat.store', $booking->_id),
        ])

    </div>
</x-app-layout>