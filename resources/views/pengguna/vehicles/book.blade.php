{{-- resources/views/pengguna/vehicles/book.blade.php --}}
<x-app-layout>
    <x-slot name="header">Formulir Pemesanan</x-slot>
    <div class="py-6 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <a href="{{ route('vehicles.index') }}" class="text-sm text-indigo-500 hover:underline">← Kembali</a>

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ $errors->first('error') }}</div>
        @endif

        {{-- ── Preview Gambar Kendaraan ── --}}
        @php
            $img = $vehicle->images[0] ?? null;
            $fx  = 50; $fy = 50;
            if ($img) {
                preg_match('/_(\d+)-(\d+)/', pathinfo($img, PATHINFO_FILENAME), $m);
                $fx = $m[1] ?? 50;
                $fy = $m[2] ?? 50;
            }
        @endphp

        @if($img)
        <div class="rounded-xl overflow-hidden border border-gray-200 bg-gray-900"
             style="height: 300px;">
            <img src="{{ Storage::url($img) }}"
                 alt="{{ $vehicle->name }}"
                 class="w-full h-full object-cover"
                 style="object-position: {{ $fx }}% {{ $fy }}%">
        </div>
        @endif

        {{-- Info Kendaraan --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            @if(!$img)
            <div class="h-14 w-14 rounded-xl bg-indigo-50 flex items-center justify-center flex-shrink-0">
                <svg class="h-8 w-8 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7-7-7 7M5 9v10a1 1 0 001 1h3m10-11v10a1 1 0 01-1 1h-3"/>
                </svg>
            </div>
            @endif
            <div>
                <h3 class="font-semibold text-gray-800">{{ $vehicle->name }}</h3>
                <p class="text-sm text-gray-500">{{ $vehicle->type }} · {{ $vehicle->capacity }} orang · {{ $vehicle->plate_number }}</p>
                <p class="text-indigo-600 font-bold mt-0.5">Rp {{ number_format($vehicle->price_per_day, 0, ',', '.') }}/hari</p>
            </div>
        </div>

        {{-- Form --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <form method="POST" action="{{ route('vehicles.store-booking', $vehicle->_id) }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                        <input type="datetime-local" name="start_date" value="{{ old('start_date') }}"
                            min="{{ now()->addHours(2)->format('Y-m-d\TH:i') }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                        @error('start_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                        <input type="datetime-local" name="end_date" value="{{ old('end_date') }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                        @error('end_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Estimasi harga (JS) --}}
                <div id="price-estimate" class="hidden bg-indigo-50 border border-indigo-100 rounded-lg px-4 py-3 text-sm">
                    <span class="text-indigo-600 font-medium">Estimasi: </span>
                    <span id="price-text" class="text-indigo-800 font-bold"></span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Penjemputan</label>
                    <input type="text" name="pickup_address" value="{{ old('pickup_address') }}"
                        placeholder="Jl. Sudirman No. 10, Jakarta"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                    @error('pickup_address')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (opsional)</label>
                    <textarea name="notes" rows="3" placeholder="Catatan khusus untuk driver..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500 resize-none">{{ old('notes') }}</textarea>
                </div>

                <button type="submit" class="w-full py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium">
                    Buat Pesanan
                </button>
            </form>
        </div>
    </div>

    <script>
    const pricePerDay = {{ $vehicle->price_per_day }};
    const startInput  = document.querySelector('[name=start_date]');
    const endInput    = document.querySelector('[name=end_date]');
    const estimate    = document.getElementById('price-estimate');
    const priceText   = document.getElementById('price-text');

    function updateEstimate() {
        if (!startInput.value || !endInput.value) return;
        const diff = (new Date(endInput.value) - new Date(startInput.value)) / (1000 * 60 * 60 * 24);
        const days = Math.max(1, Math.ceil(diff));
        const total = days * pricePerDay;
        priceText.textContent = `${days} hari × Rp ${pricePerDay.toLocaleString('id-ID')} = Rp ${total.toLocaleString('id-ID')}`;
        estimate.classList.remove('hidden');
    }

    startInput.addEventListener('change', updateEstimate);
    endInput.addEventListener('change', updateEstimate);
    </script>
</x-app-layout>