{{-- resources/views/admin/bookings/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Kelola Pesanan</x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ $errors->first() }}</div>
        @endif

        {{-- Filter status — 'accepted' dihapus dari alur utama --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-2">
            @foreach([
                ''          => 'Semua',
                'pending'   => 'Pending',
                'confirmed' => 'Confirmed',
                'ongoing'   => 'Ongoing',
                'completed' => 'Selesai',
                'cancelled' => 'Dibatalkan',
            ] as $val => $label)
            <a href="{{ route('admin.bookings.index', ['status' => $val]) }}"
               class="px-3 py-1.5 rounded-lg text-sm font-medium transition
                      {{ $status == $val ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kode</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Pengguna</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kendaraan</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Driver</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($bookings as $b)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-800">{{ $b->booking_code }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $b->user['name'] ?? '-' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $b->vehicle['name'] ?? '-' }}</td>
                        <td class="px-5 py-3 text-gray-500 text-xs">
                            {{ \Carbon\Carbon::parse($b->start_date)->format('d M Y') }}<br>
                            {{ \Carbon\Carbon::parse($b->end_date)->format('d M Y') }}
                        </td>
                        <td class="px-5 py-3 text-gray-600 text-xs">
                            @if(!empty($b->driver['name']))
                                <span class="text-green-600 font-medium">{{ $b->driver['name'] }}</span>
                            @else
                                <span class="text-yellow-500">Belum di-assign</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <span @class(['px-2 py-1 text-xs rounded-full font-medium',
                                'bg-yellow-100 text-yellow-700' => $b->status === 'pending',
                                'bg-indigo-100 text-indigo-700' => $b->status === 'confirmed',
                                'bg-green-100 text-green-700'   => $b->status === 'ongoing',
                                'bg-gray-100 text-gray-600'     => $b->status === 'completed',
                                'bg-red-100 text-red-600'       => $b->status === 'cancelled',
                            ])>{{ ucfirst($b->status) }}</span>
                        </td>
                        <td class="px-5 py-3 flex items-center gap-2">
                            <a href="{{ route('admin.bookings.show', $b->_id) }}"
                               class="text-indigo-500 hover:underline text-xs">
                                @if($b->status === 'pending' && empty($b->driver['driver_id']))
                                    Assign Driver
                                @else
                                    Detail
                                @endif
                            </a>
                            @if(!in_array($b->status, ['ongoing', 'completed', 'cancelled']))
                            <form method="POST" action="{{ route('admin.bookings.cancel', $b->_id) }}"
                                  onsubmit="return confirm('Batalkan pesanan ini?')">
                                @csrf
                                <button class="text-red-500 hover:underline text-xs">Batal</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-5 py-8 text-center text-gray-400">Tidak ada pesanan.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-5 py-3 border-t border-gray-100">{{ $bookings->links() }}</div>
        </div>
    </div>
</x-app-layout>