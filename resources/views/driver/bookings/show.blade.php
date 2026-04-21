{{-- resources/views/driver/bookings/show.blade.php --}}
<x-app-layout>
    <x-slot name="header">Detail Pesanan</x-slot>
    <div class="py-6 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <a href="{{ route('driver.bookings.index') }}" class="text-sm text-indigo-500 hover:underline">← Pesanan Saya</a>

        @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-2">
                <h4 class="font-semibold text-gray-700 border-b pb-2">Info Pesanan</h4>
                <div class="text-sm space-y-1.5">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Kode</span>
                        <span class="font-medium">{{ $booking->booking_code }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        <span @class(['px-2 py-0.5 text-xs rounded-full font-medium',
                            'bg-indigo-100 text-indigo-700' => $booking->status === 'confirmed',
                            'bg-green-100 text-green-700'   => $booking->status === 'ongoing',
                            'bg-gray-100 text-gray-600'     => $booking->status === 'completed',
                        ])>{{ ucfirst($booking->status) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Mulai</span>
                        <span>{{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Selesai</span>
                        <span>{{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Durasi</span>
                        <span>{{ $booking->duration_days }} hari</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-2">
                <h4 class="font-semibold text-gray-700 border-b pb-2">Penumpang</h4>
                <div class="text-sm space-y-1.5">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Nama</span>
                        <span>{{ $booking->user['name'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Telepon</span>
                        <a href="tel:{{ $booking->user['phone'] ?? '' }}" class="text-indigo-500">
                            {{ $booking->user['phone'] ?? $booking->user['email'] }}
                        </a>
                    </div>
                </div>
                <h4 class="font-semibold text-gray-700 border-b pb-2 pt-2">Kendaraan</h4>
                <div class="text-sm space-y-1.5">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Nama</span>
                        <span>{{ $booking->vehicle['name'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Plat</span>
                        <span class="font-medium">{{ $booking->vehicle['plate_number'] }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 col-span-full space-y-2">
                <h4 class="font-semibold text-gray-700 border-b pb-2">Rute</h4>
                <div class="text-sm space-y-2">
                    <div class="flex items-start gap-2">
                        <span class="h-5 w-5 rounded-full bg-green-100 text-green-600 text-xs flex items-center justify-center shrink-0 mt-0.5">A</span>
                        <div>
                            <p class="text-gray-500 text-xs">Penjemputan</p>
                            <p>{{ $booking->pickup['address'] }}</p>
                        </div>
                    </div>
                    @if($booking->dropoff)
                    <div class="flex items-start gap-2">
                        <span class="h-5 w-5 rounded-full bg-red-100 text-red-600 text-xs flex items-center justify-center shrink-0 mt-0.5">B</span>
                        <div>
                            <p class="text-gray-500 text-xs">Tujuan</p>
                            <p>{{ $booking->dropoff['address'] }}</p>
                        </div>
                    </div>
                    @endif
                    @if($booking->notes)
                    <p class="text-xs text-gray-400 bg-gray-50 rounded-lg px-3 py-2">📝 {{ $booking->notes }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── CHAT ROOM ── --}}
        @include('partials.chat-room', [
            'booking'    => $booking,
            'senderRole' => 'driver',
            'fetchUrl'   => route('driver.chat.index', $booking->_id),
            'postUrl'    => route('driver.chat.store', $booking->_id),
        ])

    </div>
</x-app-layout>