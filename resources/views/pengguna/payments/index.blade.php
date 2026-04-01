{{-- resources/views/pengguna/payments/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Riwayat Pembayaran</x-slot>

    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-3">

        @forelse($payments as $payment)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center justify-between gap-4">
            <div class="min-w-0">
                <p class="font-semibold text-gray-800 truncate">{{ $payment->booking_code }}</p>
                <p class="text-xs text-gray-400 mt-1">
                    {{ $payment->paid_at
                        ? \Carbon\Carbon::parse($payment->paid_at)->format('d M Y H:i')
                        : \Carbon\Carbon::parse($payment->created_at)->format('d M Y H:i') }}
                </p>
            </div>

            <div class="text-right shrink-0 space-y-1">
                <p class="font-bold text-indigo-600">
                    Rp {{ number_format($payment->amount, 0, ',', '.') }}
                </p>
                <span class="px-2 py-0.5 text-xs rounded-full font-medium {{ $payment->statusBadgeClass() }}">
                    {{ $payment->statusLabel() }}
                </span>
                {{-- Tombol bayar jika masih pending --}}
                @if($payment->status === 'pending')
                <div>
                    <a href="{{ route('bookings.pay', $payment->booking_id) }}"
                       class="text-xs text-indigo-600 hover:underline font-medium">
                        Selesaikan Pembayaran →
                    </a>
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center py-12 text-gray-400">Belum ada riwayat pembayaran.</div>
        @endforelse

        <div>{{ $payments->links() }}</div>
    </div>
</x-app-layout>