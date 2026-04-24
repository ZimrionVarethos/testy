{{-- resources/views/driver/bookings/show.blade.php --}}
<x-app-layout>
    <x-slot name="header">Detail Pesanan</x-slot>
    <div class="py-6 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <a href="{{ route('driver.bookings.index') }}" class="text-sm text-indigo-500 hover:underline">← Pesanan Saya</a>

        @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            {{ $errors->first() }}
        </div>
        @endif

        {{-- ══ TOMBOL SUDAH JEMPUT ══════════════════════════════════════
             Hanya muncul jika:
             - status = confirmed
             - $canPickup = true (start_date sudah ≤ now + toleransi 30 menit, diset dari controller)
        ════════════════════════════════════════════════════════════════ --}}
        @if($booking->status === 'confirmed')
            @if($canPickup)
            <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 space-y-3">
                <div class="flex items-start gap-3">
                    <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-indigo-800">Waktunya menjemput!</p>
                        <p class="text-xs text-indigo-600 mt-0.5">
                            Klik tombol di bawah setelah Anda tiba di lokasi penjemputan dan penumpang sudah masuk kendaraan.
                        </p>
                    </div>
                </div>
                <form method="POST" action="{{ route('driver.bookings.pickup', $booking->_id) }}"
                      onsubmit="return confirm('Konfirmasi: Anda sudah menjemput penumpang?')">
                    @csrf
                    <button type="submit"
                            class="w-full py-3 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 active:scale-95 transition">
                        ✓ Sudah Jemput — Mulai Perjalanan
                    </button>
                </form>
            </div>
            @else
            {{-- Pesanan confirmed tapi belum waktunya --}}
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm text-blue-800">
                    <p class="font-medium">Pesanan dikonfirmasi — belum waktunya berangkat</p>
                    <p class="mt-0.5 text-blue-600">
                        Jemput penumpang pada
                        <span class="font-semibold">
                            {{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y, H:i') }}
                        </span>.
                        Tombol "Sudah Jemput" akan muncul 30 menit sebelum waktu penjemputan.
                    </p>
                </div>
            </div>
            @endif
        @endif

        @if($booking->status === 'ongoing')
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-3">
            <div class="h-2.5 w-2.5 rounded-full bg-green-500 animate-pulse shrink-0"></div>
            <p class="text-sm font-semibold text-green-700">Perjalanan sedang berjalan</p>
        </div>
        @endif

        @if($booking->status === 'completed')
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 flex items-center gap-3">
            <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <p class="text-sm text-gray-500">Perjalanan selesai pada
                <span class="font-medium">{{ \Carbon\Carbon::parse($booking->completed_at)->format('d M Y H:i') }}</span>.
            </p>
        </div>
        @endif

        {{-- Detail kartu --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-2">
                <h4 class="font-semibold text-gray-700 border-b pb-2">Info Pesanan</h4>
                <div class="text-sm space-y-1.5">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Kode</span>
                        <span class="font-medium">{{ $booking->booking_code }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        <span @class(['px-2 py-0.5 text-xs rounded-full font-medium',
                            'bg-indigo-100 text-indigo-700' => $booking->status === 'confirmed',
                            'bg-green-100 text-green-700'   => $booking->status === 'ongoing',
                            'bg-gray-100 text-gray-600'     => $booking->status === 'completed',
                            'bg-red-100 text-red-600'       => $booking->status === 'cancelled',
                        ])>{{ $booking->statusLabel() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Mulai</span>
                        <span>{{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Selesai</span>
                        <span>{{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Durasi</span>
                        <span>{{ $booking->duration_days }} hari</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-2">
                <h4 class="font-semibold text-gray-700 border-b pb-2">Penumpang</h4>
                <div class="text-sm space-y-1.5">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Nama</span>
                        <span>{{ $booking->user['name'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Telepon</span>
                        <a href="tel:{{ $booking->user['phone'] ?? '' }}" class="text-indigo-500">
                            {{ $booking->user['phone'] ?? $booking->user['email'] }}
                        </a>
                    </div>
                </div>
                <h4 class="font-semibold text-gray-700 border-b pb-2 pt-2">Kendaraan</h4>
                <div class="text-sm space-y-1.5">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Nama</span>
                        <span>{{ $booking->vehicle['name'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Plat</span>
                        <span class="font-medium">{{ $booking->vehicle['plate_number'] }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 col-span-full space-y-2">
                <h4 class="font-semibold text-gray-700 border-b pb-2">Rute</h4>
                <div class="text-sm space-y-2">
                    <div class="flex items-start gap-2">
                        <span class="h-5 w-5 rounded-full bg-green-100 text-green-600 text-xs flex items-center justify-center shrink-0 mt-0.5">A</span>
                        <div>
                            <p class="text-gray-500 text-xs">Penjemputan</p>
                            <p>{{ $booking->pickup['address'] }}</p>
                        </div>
                    </div>
                    @if($booking->dropoff)
                    <div class="flex items-start gap-2">
                        <span class="h-5 w-5 rounded-full bg-red-100 text-red-600 text-xs flex items-center justify-center shrink-0 mt-0.5">B</span>
                        <div>
                            <p class="text-gray-500 text-xs">Tujuan</p>
                            <p>{{ $booking->dropoff['address'] }}</p>
                        </div>
                    </div>
                    @endif
                    @if($booking->notes)
                    <p class="text-xs text-gray-400 bg-gray-50 rounded-lg px-3 py-2">📝 {{ $booking->notes }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Chat Room --}}
        @include('partials.chat-room', [
            'booking'    => $booking,
            'senderRole' => 'driver',
            'fetchUrl'   => route('driver.chat.index', $booking->_id),
            'postUrl'    => route('driver.chat.store', $booking->_id),
        ])

    </div>
</x-app-layout>