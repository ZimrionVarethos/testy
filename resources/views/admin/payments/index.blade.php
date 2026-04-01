{{-- resources/views/admin/payments/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Pembayaran</x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Total Pendapatan</p>
                <p class="text-2xl font-bold text-green-600 mt-1">
                    Rp {{ number_format($summary['total_paid'], 0, ',', '.') }}
                </p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Transaksi Lunas</p>
                <p class="text-2xl font-bold text-indigo-600 mt-1">{{ number_format($summary['count_paid']) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Menunggu Bayar</p>
                <p class="text-2xl font-bold text-yellow-500 mt-1">{{ number_format($summary['total_pending']) }}</p>
            </div>
        </div>

        {{-- Filter --}}
        <form method="GET" class="flex gap-3 flex-wrap items-end">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Status</label>
                <select name="status"
                        class="rounded-lg border-gray-200 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Semua</option>
                    <option value="pending"   @selected(request('status') === 'pending')>Pending</option>
                    <option value="paid"      @selected(request('status') === 'paid')>Lunas</option>
                    <option value="failed"    @selected(request('status') === 'failed')>Gagal</option>
                    <option value="expired"   @selected(request('status') === 'expired')>Kedaluwarsa</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Dibatalkan</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Cari Kode</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="BRN-..."
                       class="rounded-lg border-gray-200 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <button type="submit"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                Filter
            </button>
            @if(request()->hasAny(['status','search']))
            <a href="{{ route('admin.payments.index') }}"
               class="px-4 py-2 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50 transition-colors">
                Reset
            </a>
            @endif
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kode Pesanan</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Metode</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Total</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($payments as $payment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-800">{{ $payment->booking_code }}</td>
                        <td class="px-5 py-3 text-gray-600">
                            {{ $payment->method ? strtoupper($payment->method) : '-' }}
                        </td>
                        <td class="px-5 py-3 font-semibold text-indigo-600">
                            Rp {{ number_format($payment->amount, 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-1 text-xs rounded-full font-medium {{ $payment->statusBadgeClass() }}">
                                {{ $payment->statusLabel() }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-gray-500 text-xs">
                            {{ $payment->paid_at
                                ? \Carbon\Carbon::parse($payment->paid_at)->format('d M Y H:i')
                                : \Carbon\Carbon::parse($payment->created_at)->format('d M Y H:i') }}
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.payments.show', $payment->_id) }}"
                               class="text-xs text-indigo-600 hover:underline font-medium">Detail</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-gray-400">
                            Belum ada data pembayaran.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-5 py-3 border-t border-gray-100">{{ $payments->links() }}</div>
        </div>
    </div>
</x-app-layout>