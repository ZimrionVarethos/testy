<x-app-layout>
    <x-slot name="header">Pembayaran</x-slot>

    <div class="py-6 max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">

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
                        <span class="font-bold text-indigo-600 text-base">
                            Rp {{ number_format($booking->total_price, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- ── BARU: countdown batas waktu bayar ── --}}
            @if(isset($expired_at))
            <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm">
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
                class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white
                       font-semibold rounded-lg transition-colors">
                Bayar Sekarang
            </button>

            {{-- ── BARU: banner muncul setelah user klik silang (onClose) ── --}}
            <div id="close-banner" class="hidden bg-yellow-50 border border-yellow-200
                 rounded-lg p-4 space-y-3">
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
                    class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white
                           text-sm font-medium rounded-lg transition-colors">
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
        const snapToken   = @json($snap_token);
        const finishUrl   = @json(route('payments.finish', $payment->_id));
        const expiredAt   = @json(isset($expired_at) ? \Carbon\Carbon::parse($expired_at)->toISOString() : null);

        // ── Countdown helper ──
        function formatCountdown(ms) {
            if (ms <= 0) return 'sudah kedaluwarsa';
            const h = Math.floor(ms / 3600000);
            const m = Math.floor((ms % 3600000) / 60000);
            const s = Math.floor((ms % 60000) / 1000);
            if (h > 0) return `${h} jam ${m} menit lagi`;
            if (m > 0) return `${m} menit ${s} detik lagi`;
            return `${s} detik lagi`;
        }

        if (expiredAt) {
            const deadline = new Date(expiredAt).getTime();
            function tick() {
                const left = deadline - Date.now();
                const label = formatCountdown(left);
                const el1 = document.getElementById('pay-countdown');
                const el2 = document.getElementById('banner-countdown');
                if (el1) el1.textContent = label;
                if (el2) el2.textContent = label;
                if (left <= 0) {
                    // Expired — nonaktifkan tombol bayar
                    document.getElementById('pay-button').disabled = true;
                    document.getElementById('pay-button').textContent = 'Waktu pembayaran habis';
                    document.getElementById('pay-button').className =
                        'w-full py-3 bg-gray-300 text-gray-500 font-semibold rounded-lg cursor-not-allowed';
                    clearInterval(timer);
                }
            }
            tick();
            const timer = setInterval(tick, 1000);
        }

        // ── Buka Snap popup ──
        function openSnap() {
            document.getElementById('close-banner').classList.add('hidden');
            document.getElementById('pay-button').classList.remove('hidden');

            snap.pay(snapToken, {
                onSuccess(result) {
                    window.location.href = finishUrl;
                },
                onPending(result) {
                    window.location.href = finishUrl;
                },
                onError(result) {
                    alert('Pembayaran gagal. Silakan coba lagi.');
                },
                onClose() {
                    // ── BARU: tampilkan banner, jangan loading ──
                    document.getElementById('close-banner').classList.remove('hidden');
                },
            });
        }

        document.getElementById('pay-button').addEventListener('click', openSnap);
        document.getElementById('reopen-button').addEventListener('click', openSnap);
    </script>
</x-app-layout>