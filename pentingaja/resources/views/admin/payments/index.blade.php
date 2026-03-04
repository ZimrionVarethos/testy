{{-- resources/views/admin/payments/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Pembayaran</x-slot>
    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kode Pesanan</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Pengguna</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Total</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($bookings as $b)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-800">{{ $b->booking_code }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $b->user['name'] ?? '-' }}</td>
                        <td class="px-5 py-3 font-semibold text-indigo-600">Rp {{ number_format($b->total_price, 0, ',', '.') }}</td>
                        <td class="px-5 py-3">
                            <span @class(['px-2 py-1 text-xs rounded-full font-medium',
                                'bg-indigo-100 text-indigo-700' => $b->status === 'confirmed',
                                'bg-green-100 text-green-700'   => in_array($b->status, ['ongoing','completed']),
                            ])>{{ ucfirst($b->status) }}</span>
                        </td>
                        <td class="px-5 py-3 text-gray-500 text-xs">{{ \Carbon\Carbon::parse($b->confirmed_at)->format('d M Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-gray-400">Belum ada data pembayaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-5 py-3 border-t border-gray-100">{{ $bookings->links() }}</div>
        </div>
    </div>
</x-app-layout>