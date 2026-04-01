{{-- resources/views/pengguna/payments/snap.blade.php --}}
<x-app-layout>
    <x-slot name="header">Pembayaran</x-slot>

    <div class="py-6 max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">

            {{-- Detail Booking --}}
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

            {{-- Tombol Bayar --}}
            <button id="pay-button"
                    class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition-colors">
                Bayar Sekarang
            </button>

            <p class="text-xs text-center text-gray-400">
                Pembayaran diproses aman via Midtrans. Jangan tutup halaman ini.
            </p>
        </div>
    </div>

    {{-- Midtrans Snap JS --}}
    @if(config('midtrans.is_production'))
        @php $snapUrl = 'https://app.midtrans.com/snap/snap.js'; @endphp
    @else
        @php $snapUrl = 'https://app.sandbox.midtrans.com/snap/snap.js'; @endphp
    @endif

    <script src="{{ $snapUrl }}"
            data-client-key="{{ $client_key }}"></script>

    <script>
        const snapToken   = @json($snap_token);
        const finishUrl   = @json(route('payments.finish', $payment->_id));

        document.getElementById('pay-button').addEventListener('click', function () {
            snap.pay(snapToken, {
                onSuccess: function (result) {
                    // Webhook sudah handle update status — kita cukup redirect
                    window.location.href = finishUrl;
                },
                onPending: function (result) {
                    window.location.href = finishUrl;
                },
                onError: function (result) {
                    alert('Pembayaran gagal. Silakan coba lagi.');
                    console.error('Midtrans error:', result);
                },
                onClose: function () {
                    // Pengguna tutup popup — tidak perlu redirect
                },
            });
        });
    </script>
</x-app-layout>