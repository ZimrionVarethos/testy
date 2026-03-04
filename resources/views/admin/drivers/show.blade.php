{{-- resources/views/admin/drivers/show.blade.php --}}
<x-app-layout>
    <x-slot name="header">Detail Driver</x-slot>

    <div class="py-6 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
        @endif

        {{-- Header Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-start justify-between flex-wrap gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center text-2xl font-bold text-indigo-600">
                        {{ strtoupper(substr($driver->name, 0, 1)) }}
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">{{ $driver->name }}</h2>
                        <p class="text-sm text-gray-500">{{ $driver->email }}</p>
                        <p class="text-sm text-gray-500">{{ $driver->phone ?? '-' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 flex-wrap">
                    <span @class([
                        'px-3 py-1 rounded-full text-xs font-medium',
                        'bg-green-100 text-green-700' => $driver->is_active,
                        'bg-red-100 text-red-600'     => !$driver->is_active,
                    ])>{{ $driver->is_active ? 'Aktif' : 'Nonaktif' }}</span>

                    <form method="POST" action="{{ route('admin.drivers.toggle', $driver->_id) }}">
                        @csrf
                        <button class="px-4 py-1.5 text-sm rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition">
                            {{ $driver->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                    </form>

                    <a href="{{ route('admin.drivers.index') }}" class="px-4 py-1.5 text-sm rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition">
                        ← Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Info Akun --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide border-b pb-2 mb-3">Info Akun</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Role</span>
                        <span class="font-medium text-gray-800 capitalize">{{ $driver->role }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Bergabung</span>
                        <span class="font-medium text-gray-800">{{ \Carbon\Carbon::parse($driver->created_at)->format('d M Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Email Verified</span>
                        <span class="font-medium text-gray-800">
                            {{ $driver->email_verified_at ? \Carbon\Carbon::parse($driver->email_verified_at)->format('d M Y') : '-' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Driver Profile --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide border-b pb-2 mb-3">Profil Driver</h3>
                @php $dp = $driver->driver_profile ?? []; @endphp
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">No. SIM</span>
                        <span class="font-medium text-gray-800">{{ $dp['license_number'] ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Masa Berlaku SIM</span>
                        <span class="font-medium text-gray-800">
                            {{ isset($dp['license_expiry']) ? \Carbon\Carbon::parse($dp['license_expiry'])->format('d M Y') : '-' }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Ketersediaan</span>
                        <span @class(['font-medium', 'text-green-600' => ($dp['is_available'] ?? false), 'text-gray-500' => !($dp['is_available'] ?? false)])>
                            {{ ($dp['is_available'] ?? false) ? 'Tersedia' : 'Sedang Bertugas' }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Rating</span>
                        <span class="font-medium text-gray-800">⭐ {{ number_format($dp['rating_avg'] ?? 0, 1) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Total Trip</span>
                        <span class="font-medium text-gray-800">{{ $dp['total_trips'] ?? 0 }} trip</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Riwayat Booking --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">Riwayat Booking (10 Terakhir)</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kode</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Pengguna</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kendaraan</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($bookings as $b)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium">
                            <a href="{{ route('admin.bookings.show', $b->_id) }}" class="text-indigo-500 hover:underline">{{ $b->booking_code }}</a>
                        </td>
                        <td class="px-5 py-3 text-gray-600">{{ $b->user['name'] ?? '-' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $b->vehicle['name'] ?? '-' }}</td>
                        <td class="px-5 py-3 text-gray-500 text-xs">
                            {{ \Carbon\Carbon::parse($b->start_date)->format('d M Y') }} –
                            {{ \Carbon\Carbon::parse($b->end_date)->format('d M Y') }}
                        </td>
                        <td class="px-5 py-3">
                            <span @class(['px-2 py-1 text-xs rounded-full font-medium',
                                'bg-yellow-100 text-yellow-700' => $b->status === 'pending',
                                'bg-blue-100 text-blue-700'     => $b->status === 'accepted',
                                'bg-indigo-100 text-indigo-700' => $b->status === 'confirmed',
                                'bg-green-100 text-green-700'   => $b->status === 'ongoing',
                                'bg-gray-100 text-gray-600'     => $b->status === 'completed',
                                'bg-red-100 text-red-600'       => $b->status === 'cancelled',
                            ])>{{ ucfirst($b->status) }}</span>
                        </td>
                        <td class="px-5 py-3 text-gray-800 font-medium">Rp {{ number_format($b->total_price, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-gray-400">Belum ada riwayat booking.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</x-app-layout>