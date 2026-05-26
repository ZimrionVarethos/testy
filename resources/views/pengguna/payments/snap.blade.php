<x-app-layout>
    <x-slot name="header">Pembayaran</x-slot>

    <div class="py-6 max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white border border-gray-100 p-6 space-y-5">

            {{-- Ringkasan pesanan --}}
            <div>
                <h2 class="text-base font-semibold text-gray-800 mb-3">Ringkasan Pesanan</h2>
                <div class="space-y-2 text-sm text-gray-600">
                    <div class="flex justify-between">
                        <span>Kode Pesanan</span>
                        <span class="font-medium text-gray-800">{{ $booking->booking_code }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Kendaraan</span>
                        <span class="font-medium text-gray-800">{{ $booking->vehicle['name'] ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Durasi</span>
                        <span class="font-medium text-gray-800">{{ $booking->duration_days }} hari</span>
                    </div>
                    <div class="flex justify-between border-t pt-2 mt-2">
                        <span class="font-semibold text-gray-800">Total</span>
                        <span class="font-bold text-blue-600 text-base">
                            Rp {{ number_format($booking->total_price, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- ── BARU: countdown batas waktu bayar ── --}}
            @if(isset($expired_at))
            <div class="bg-amber-50 border border-amber-200 px-4 py-3 text-sm">
                <p class="text-amber-700">
                    ⏱ Selesaikan pembayaran sebelum
                    <span class="font-semibold">
                        {{ \Carbon\Carbon::parse($expired_at)->format('d M Y, H:i \W\I\B') }}
                    </span>
                </p>
                <p class="text-amber-600 text-xs mt-0.5" id="pay-countdown"></p>
            </div>
            @endif

            {{-- Tombol utama bayar --}}
            <button id="pay-button"
                class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white
                       font-semibold transition-colors">
                Bayar Sekarang
            </button>

            {{-- ── BARU: banner muncul setelah user klik silang (onClose) ── --}}
            <div id="close-banner" class="hidden bg-yellow-50 border border-yellow-200 p-4 space-y-3">
                <div class="flex gap-2 items-start">
                    <svg class="w-5 h-5 text-yellow-500 flex-shrink-0 mt-0.5" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667
                                 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464
                                 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="text-sm text-yellow-800">
                        <p class="font-medium">Pembayaran belum selesai</p>
                        <p class="text-yellow-700 mt-0.5">
                            Kamu bisa kembali membayar kapan saja sebelum batas waktu.
                            @if(isset($expired_at))
                            Sisa waktu: <span id="banner-countdown" class="font-semibold"></span>.
                            @endif
                        </p>
                    </div>
                </div>
                <button id="reopen-button"
                    class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white
                           text-sm font-medium transition-colors">
                    Lanjutkan Pembayaran
                </button>
            </div>

            <p class="text-xs text-center text-gray-400">
                Pembayaran diproses aman via Midtrans.
            </p>
        </div>
    </div>

    @if(config('midtrans.is_production'))
        @php $snapUrl = 'https://app.midtrans.com/snap/snap.js'; @endphp
    @else
        @php $snapUrl = 'https://app.sandbox.midtrans.com/snap/snap.js'; @endphp
    @endif
    <script src="{{ $snapUrl }}" data-client-key="{{ $client_key }}"></script>

    <script>
    window.SnapData = {
        snapToken: @json($snap_token),
        finishUrl: @json(route('payments.finish', $payment->_id)),
        expiredAt: @json(isset($expired_at) ? \Carbon\Carbon::parse($expired_at)->toISOString() : null),
    };
    </script>
    <script src="{{ asset('js/pengguna/snap.js') }}"></script>
</x-app-layout>
