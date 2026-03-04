{{-- resources/views/pengguna/bookings/show.blade.php --}}
<x-app-layout>
    <x-slot name="header">Detail Pesanan</x-slot>
    <div class="py-6 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <a href="{{ route('bookings.index') }}" class="text-sm text-indigo-500 hover:underline">← Pesanan Saya</a>

        {{-- Status tracker --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            @php
                $steps = ['pending'=>'Menunggu Driver','accepted'=>'Driver Ditemukan','confirmed'=>'Dikonfirmasi','ongoing'=>'Sedang Berjalan','completed'=>'Selesai'];
                $order = array_keys($steps);
                $currentIdx = array_search($booking->status, $order) ?? 0;
            @endphp
            <div class="flex items-center justify-between">
                @foreach($steps as $key => $label)
                @php $idx = array_search($key, $order); $done = $idx <= $currentIdx && $booking->status !== 'cancelled'; @endphp
                <div class="flex flex-col items-center flex-1 {{ !$loop->last ? 'relative' : '' }}">
                    @if(!$loop->last)
                    <div class="absolute top-3 left-1/2 w-full h-0.5 {{ $done && $idx < $currentIdx ? 'bg-indigo-500' : 'bg-gray-200' }}"></div>
                    @endif
                    <div class="h-6 w-6 rounded-full z-10 flex items-center justify-center text-xs font-bold
                        {{ $done ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-400' }}">
                        {{ $done ? '✓' : ($idx + 1) }}
                    </div>
                    <p class="text-xs text-center mt-1 {{ $done ? 'text-indigo-600 font-medium' : 'text-gray-400' }} hidden sm:block leading-tight">{{ $label }}</p>
                </div>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-2">
                <h4 class="font-semibold text-gray-700 border-b pb-2">Kendaraan</h4>
                <div class="text-sm space-y-1.5">
                    <div class="flex justify-between"><span class="text-gray-500">Nama</span><span>{{ $booking->vehicle['name'] }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Plat</span><span>{{ $booking->vehicle['plate_number'] }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Mulai</span><span>{{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y H:i') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Selesai</span><span>{{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y H:i') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Durasi</span><span>{{ $booking->duration_days }} hari</span></div>
                    <div class="flex justify-between font-semibold"><span class="text-gray-700">Total</span><span class="text-indigo-600">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</span></div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-2">
                <h4 class="font-semibold text-gray-700 border-b pb-2">Driver</h4>
                @if($booking->driver)
                <div class="text-sm space-y-1.5">
                    <div class="flex justify-between"><span class="text-gray-500">Nama</span><span>{{ $booking->driver['name'] }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Telepon</span><a href="tel:{{ $booking->driver['phone'] }}" class="text-indigo-500">{{ $booking->driver['phone'] }}</a></div>
                </div>
                @else
                <p class="text-sm text-gray-400">Menunggu driver mengambil pesanan ini...</p>
                @endif
                <h4 class="font-semibold text-gray-700 border-b pb-2 pt-2">Penjemputan</h4>
                <p class="text-sm text-gray-600">{{ $booking->pickup['address'] }}</p>
                @if($booking->notes)<p class="text-xs text-gray-400 mt-1">Catatan: {{ $booking->notes }}</p>@endif
            </div>
        </div>

        @if(!in_array($booking->status, ['ongoing','completed','cancelled']))
        <form method="POST" action="{{ route('bookings.destroy', $booking->_id) }}" onsubmit="return confirm('Batalkan pesanan ini?')">
            @csrf @method('DELETE')
            <button class="w-full py-2.5 bg-red-50 text-red-500 border border-red-200 rounded-lg hover:bg-red-100 transition text-sm">Batalkan Pesanan</button>
        </form>
        @endif
    </div>
</x-app-layout>