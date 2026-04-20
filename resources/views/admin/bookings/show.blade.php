{{-- resources/views/admin/bookings/show.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        Detail Pesanan — {{ $booking->booking_code }}
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <a href="{{ route('admin.bookings.index') }}" class="text-sm text-indigo-500 hover:underline">← Kembali</a>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Info Pesanan --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-3">
                <h3 class="font-semibold text-gray-700 border-b pb-2">Info Pesanan</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Kode</span><span class="font-medium">{{ $booking->booking_code }}</span></div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        <span @class(['px-2 py-0.5 text-xs rounded-full font-medium',
                            'bg-yellow-100 text-yellow-700' => $booking->status === 'pending',
                            'bg-indigo-100 text-indigo-700' => $booking->status === 'confirmed',
                            'bg-green-100 text-green-700'   => $booking->status === 'ongoing',
                            'bg-gray-100 text-gray-600'     => $booking->status === 'completed',
                            'bg-red-100 text-red-600'       => $booking->status === 'cancelled',
                        ])>{{ ucfirst($booking->status) }}</span>
                    </div>
                    <div class="flex justify-between"><span class="text-gray-500">Mulai</span><span>{{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y H:i') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Selesai</span><span>{{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y H:i') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Durasi</span><span>{{ $booking->duration_days }} hari</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Total</span><span class="font-semibold text-indigo-600">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</span></div>
                </div>
            </div>

            {{-- Info Pengguna + Kendaraan --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-3">
                <h3 class="font-semibold text-gray-700 border-b pb-2">Pengguna</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Nama</span><span>{{ $booking->user['name'] ?? '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Email</span><span>{{ $booking->user['email'] ?? '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Telepon</span><span>{{ $booking->user['phone'] ?? '-' }}</span></div>
                </div>
                <h3 class="font-semibold text-gray-700 border-b pb-2 pt-2">Kendaraan</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Nama</span><span>{{ $booking->vehicle['name'] ?? '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Plat</span><span>{{ $booking->vehicle['plate_number'] ?? '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Harga/hari</span><span>Rp {{ number_format($booking->vehicle['price_per_day'] ?? 0, 0, ',', '.') }}</span></div>
                </div>
            </div>

            {{-- Driver --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-3">
                <h3 class="font-semibold text-gray-700 border-b pb-2">Driver</h3>

                @if(!empty($booking->driver['driver_id']))
                    {{-- Driver sudah di-assign --}}
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">Nama</span><span class="font-medium">{{ $booking->driver['name'] }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Telepon</span><span>{{ $booking->driver['phone'] }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">SIM</span><span>{{ $booking->driver['license_number'] }}</span></div>
                        @if($booking->assigned_at)
                        <div class="flex justify-between"><span class="text-gray-500">Di-assign</span><span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($booking->assigned_at)->format('d M Y H:i') }}</span></div>
                        @endif
                    </div>

                @elseif(in_array($booking->status, ['pending', 'confirmed']) && $availableDrivers->isNotEmpty())
                    {{-- Form assign driver --}}
                    <form method="POST" action="{{ route('admin.bookings.assign-driver', $booking->_id) }}" class="space-y-3">
                        @csrf
                        <p class="text-xs text-gray-400">Hanya driver aktif yang tidak sedang punya pesanan berjalan yang ditampilkan.</p>
                        <select name="driver_id" required
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                            <option value="">— Pilih driver —</option>
                            @foreach($availableDrivers as $driver)
                            <option value="{{ $driver->_id }}">
                                {{ $driver->name }}
                                @if($driver->phone) · {{ $driver->phone }} @endif
                                @if($driver->driver_profile['license_number'] ?? false)
                                    · SIM: {{ $driver->driver_profile['license_number'] }}
                                @endif
                            </option>
                            @endforeach
                        </select>
                        <button type="submit"
                            class="w-full px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                            Assign Driver & Konfirmasi Pesanan
                        </button>
                    </form>

                @elseif(in_array($booking->status, ['pending', 'confirmed']) && $availableDrivers->isEmpty())
                    <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-lg px-3 py-2 text-sm">
                        ⚠ Tidak ada driver tersedia saat ini. Semua driver sedang aktif atau tidak aktif.
                    </div>

                @else
                    <p class="text-sm text-gray-400">Tidak ada driver yang di-assign.</p>
                @endif
            </div>

            {{-- Lokasi --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-3">
                <h3 class="font-semibold text-gray-700 border-b pb-2">Lokasi</h3>
                <div class="space-y-2 text-sm">
                    <div><span class="text-gray-500 block">Penjemputan</span><span>{{ $booking->pickup['address'] ?? '-' }}</span></div>
                    @if($booking->dropoff)
                    <div><span class="text-gray-500 block">Tujuan</span><span>{{ $booking->dropoff['address'] ?? '-' }}</span></div>
                    @endif
                    @if($booking->notes)
                    <div><span class="text-gray-500 block">Catatan</span><span>{{ $booking->notes }}</span></div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Aksi bawah --}}
        @if(!in_array($booking->status, ['ongoing', 'completed', 'cancelled']))
        <div class="flex gap-3">
            <form method="POST" action="{{ route('admin.bookings.cancel', $booking->_id) }}"
                  onsubmit="return confirm('Batalkan pesanan ini?')">
                @csrf
                <button class="px-4 py-2 bg-red-500 text-white text-sm rounded-lg hover:bg-red-600 transition">
                    Batalkan Pesanan
                </button>
            </form>
        </div>
        @endif
    </div>
</x-app-layout>