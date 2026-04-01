{{-- resources/views/admin/payments/show.blade.php --}}
<x-app-layout>
    <x-slot name="header">Detail Pembayaran</x-slot>

    <div class="py-6 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

        <a href="{{ route('admin.payments.index') }}"
           class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-indigo-600 transition-colors">
            ← Kembali
        </a>

        {{-- Payment Info --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm divide-y divide-gray-100">
            <div class="px-6 py-4 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">{{ $payment->booking_code }}</h2>
                <span class="px-3 py-1 rounded-full text-sm font-medium {{ $payment->statusBadgeClass() }}">
                    {{ $payment->statusLabel() }}
                </span>
            </div>
            <div class="px-6 py-4 grid grid-cols-2 gap-y-3 text-sm">
                <span class="text-gray-500">Payment ID</span>
                <span class="font-mono text-gray-700 text-xs">{{ $payment->_id }}</span>

                <span class="text-gray-500">Jumlah</span>
                <span class="font-bold text-indigo-600">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>

                <span class="text-gray-500">Metode</span>
                <span class="text-gray-800">{{ $payment->method ? strtoupper($payment->method) : '-' }}</span>

                <span class="text-gray-500">Dibayar Pada</span>
                <span class="text-gray-800">
                    {{ $payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at)->format('d M Y H:i') : '-' }}
                </span>

                <span class="text-gray-500">Dibuat</span>
                <span class="text-gray-800">
                    {{ \Carbon\Carbon::parse($payment->created_at)->format('d M Y H:i') }}
                </span>
            </div>
        </div>

        {{-- Midtrans Data --}}
        @if(!empty($payment->midtrans))
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-700 text-sm">Data Midtrans</h3>
            </div>
            <div class="px-6 py-4 grid grid-cols-2 gap-y-3 text-sm">
                <span class="text-gray-500">Order ID</span>
                <span class="font-mono text-gray-700 text-xs">{{ $payment->midtrans['order_id'] ?? '-' }}</span>

                <span class="text-gray-500">Transaction ID</span>
                <span class="font-mono text-gray-700 text-xs">{{ $payment->midtrans['transaction_id'] ?? '-' }}</span>

                <span class="text-gray-500">Transaction Status</span>
                <span class="text-gray-800">{{ $payment->midtrans['transaction_status'] ?? '-' }}</span>

                <span class="text-gray-500">Payment Type</span>
                <span class="text-gray-800">{{ $payment->midtrans['payment_type'] ?? '-' }}</span>

                <span class="text-gray-500">Fraud Status</span>
                <span class="text-gray-800">{{ $payment->midtrans['fraud_status'] ?? '-' }}</span>
            </div>
        </div>
        @endif

        {{-- Booking Info --}}
        @if($booking)
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-700 text-sm">Booking Terkait</h3>
            </div>
            <div class="px-6 py-4 grid grid-cols-2 gap-y-3 text-sm">
                <span class="text-gray-500">Status Booking</span>
                <span class="text-gray-800">{{ ucfirst($booking->status) }}</span>

                <span class="text-gray-500">Pengguna</span>
                <span class="text-gray-800">{{ $booking->user['name'] ?? '-' }}</span>

                <span class="text-gray-500">Kendaraan</span>
                <span class="text-gray-800">{{ $booking->vehicle['name'] ?? '-' }}</span>

                <span class="text-gray-500">Tanggal Mulai</span>
                <span class="text-gray-800">
                    {{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y') }}
                </span>

                <span class="text-gray-500">Tanggal Selesai</span>
                <span class="text-gray-800">
                    {{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y') }}
                </span>
            </div>
            <div class="px-6 py-3 border-t border-gray-100">
                <a href="{{ route('admin.bookings.show', $booking->_id) }}"
                   class="text-sm text-indigo-600 hover:underline font-medium">
                    Lihat Detail Booking →
                </a>
            </div>
        </div>
        @endif

    </div>
</x-app-layout>