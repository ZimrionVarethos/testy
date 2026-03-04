{{-- resources/views/pengguna/payments/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Riwayat Pembayaran</x-slot>
    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-3">
        @forelse($bookings as $b)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center justify-between">
            <div>
                <p class="font-semibold text-gray-800">{{ $b->booking_code }}</p>
                <p class="text-sm text-gray-500">{{ $b->vehicle['name'] ?? '-' }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ \Carbon\Carbon::parse($b->confirmed_at ?? $b->created_at)->format('d M Y H:i') }}</p>
            </div>
            <div class="text-right">
                <p class="font-bold text-indigo-600">Rp {{ number_format($b->total_price, 0, ',', '.') }}</p>
                <span class="px-2 py-1 text-xs rounded-full font-medium bg-green-100 text-green-700">Lunas</span>
            </div>
        </div>
        @empty
        <div class="text-center py-12 text-gray-400">Belum ada riwayat pembayaran.</div>
        @endforelse
        <div>{{ $bookings->links() }}</div>
    </div>
</x-app-layout>