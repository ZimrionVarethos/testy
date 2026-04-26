{{-- resources/views/pengguna/vehicles/book.blade.php --}}
<x-app-layout>
    <x-slot name="header">Konfirmasi Pemesanan</x-slot>
    <div class="py-6 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

        <a href="{{ route('vehicles.index', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
           class="text-sm text-indigo-500 hover:underline">← Pilih kendaraan lain</a>

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            {{ $errors->first() }}
        </div>
        @endif

        {{-- Ringkasan kendaraan --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex gap-4 items-start">
            <div class="h-16 w-24 rounded-lg overflow-hidden bg-gray-100 shrink-0">
                @if(!empty($vehicle->images[0]))
                    <img src="{{ Storage::url($vehicle->images[0]) }}"
                         class="w-full h-full object-cover" alt="{{ $vehicle->name }}">
                @else
                    <div class="h-full flex items-center justify-center bg-indigo-50">
                        <svg class="h-8 w-8 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M19 9l-7-7-7 7M5 9v10a1 1 0 001 1h3m10-11v10a1 1 0 01-1 1h-3"/>
                        </svg>
                    </div>
                @endif
            </div>
            <div>
                <h3 class="font-semibold text-gray-800">{{ $vehicle->name }}</h3>
                <p class="text-xs text-gray-400">{{ $vehicle->type }} · {{ $vehicle->capacity }} orang · {{ $vehicle->year }}</p>
                <p class="text-sm font-bold text-indigo-600 mt-1">
                    Rp {{ number_format($vehicle->price_per_day, 0, ',', '.') }}/hari
                </p>
            </div>
        </div>

        {{-- Ringkasan tanggal & harga --}}
        <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">Tanggal Mulai</span>
                <span class="font-medium">{{ \Carbon\Carbon::parse($startDate)->format('d M Y H:i') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Tanggal Selesai</span>
                <span class="font-medium">{{ \Carbon\Carbon::parse($endDate)->format('d M Y H:i') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Durasi</span>
                <span class="font-medium">{{ $durationDays }} hari</span>
            </div>
            <div class="border-t border-indigo-100 pt-2 flex justify-between font-semibold">
                <span class="text-gray-700">Total</span>
                <span class="text-indigo-600 text-base">
                    Rp {{ number_format($totalPrice, 0, ',', '.') }}
                </span>
            </div>
        </div>

        {{-- Form detail pemesanan --}}
        <form method="POST" action="{{ route('vehicles.store-booking', $vehicle->_id) }}" class="space-y-4">
            @csrf

            {{-- Tanggal dikirim sebagai hidden field — tidak bisa diubah di sini --}}
            <input type="hidden" name="start_date" value="{{ $startDate }}">
            <input type="hidden" name="end_date"   value="{{ $endDate }}">

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
                <h4 class="font-semibold text-gray-700">Detail Penjemputan</h4>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Alamat Penjemputan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="pickup_address"
                           value="{{ old('pickup_address') }}"
                           placeholder="Masukkan alamat lengkap penjemputan"
                           required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-400 focus:outline-none @error('pickup_address') border-red-300 @enderror">
                    @error('pickup_address')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (opsional)</label>
                    <textarea name="notes" rows="3"
                              placeholder="Instruksi tambahan untuk driver..."
                              class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-400 focus:outline-none resize-none">{{ old('notes') }}</textarea>
                </div>
            </div>

            {{-- Info pembayaran --}}
            <div class="bg-amber-50 border border-amber-100 rounded-xl px-4 py-3 text-xs text-amber-700 space-y-1">
                <p class="font-semibold">⏱ Batas waktu pembayaran: 30 menit</p>
                <p>Setelah pesanan dibuat, selesaikan pembayaran dalam 30 menit. Pesanan akan dibatalkan otomatis jika melewati batas waktu.</p>
            </div>

            <button type="submit"
                    class="w-full py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 active:scale-95 transition">
                Buat Pesanan & Bayar
            </button>
        </form>

    </div>
</x-app-layout>