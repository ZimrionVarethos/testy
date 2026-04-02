{{-- resources/views/pengguna/payments/finish.blade.php --}}
<x-app-layout>
    <x-slot name="header">Status Pembayaran</x-slot>

    <div class="py-10 max-w-md mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-5">

        @if($payment->status === 'paid')
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-800">Pembayaran Berhasil!</h2>
            <p class="text-gray-500 text-sm">
                Pesanan <span class="font-semibold text-gray-700">{{ $payment->booking_code }}</span>
                telah dikonfirmasi. Admin akan segera menugaskan driver.
            </p>

        @elseif($payment->status === 'pending')
            <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-8 h-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-800">Pembayaran Sedang Diverifikasi</h2>
            <p class="text-gray-500 text-sm">
                Midtrans sedang memverifikasi pembayaran Anda. Halaman ini akan otomatis update dalam beberapa detik.
            </p>
            {{-- Auto refresh setiap 5 detik sampai status berubah --}}
            <p class="text-xs text-gray-400" id="refresh-countdown">Mengecek status dalam <span id="countdown">5</span> detik...</p>

        @else
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-800">Pembayaran Gagal</h2>
            <p class="text-gray-500 text-sm">Terjadi masalah pada pembayaran Anda. Silakan buat pesanan baru.</p>
        @endif

        <div class="bg-gray-50 rounded-lg p-4 text-sm text-left space-y-2">
            <div class="flex justify-between">
                <span class="text-gray-500">Kode Pesanan</span>
                <span class="font-medium text-gray-800">{{ $payment->booking_code }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Total</span>
                <span class="font-semibold text-indigo-600">
                    Rp {{ number_format($payment->amount, 0, ',', '.') }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Status</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $payment->statusBadgeClass() }}">
                    {{ $payment->statusLabel() }}
                </span>
            </div>
        </div>

        <a href="{{ route('bookings.index') }}"
           class="inline-block px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
            Lihat Pesanan Saya
        </a>

    </div>

    @if($payment->status === 'pending')
    <script>
        // Auto refresh setiap 5 detik untuk cek apakah webhook sudah update status
        let count = 5;
        const el = document.getElementById('countdown');
        setInterval(() => {
            count--;
            if (el) el.textContent = count;
            if (count <= 0) {
                window.location.reload();
            }
        }, 1000);
    </script>
    @endif
</x-app-layout>