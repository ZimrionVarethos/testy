{{-- resources/views/pengguna/vehicles/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Sewa Kendaraan</x-slot>
    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            {{ $errors->first() }}
        </div>
        @endif

        {{-- ── FORM PILIH TANGGAL ────────────────────────────────────────
             Selalu tampil di atas. User WAJIB isi ini dulu sebelum lihat kendaraan.
        ──────────────────────────────────────────────────────────────── --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-800 mb-1">Kapan Anda membutuhkan kendaraan?</h3>
            <p class="text-xs text-gray-400 mb-4">Isi tanggal terlebih dahulu untuk melihat kendaraan yang tersedia.</p>
            <form method="GET" action="{{ route('vehicles.index') }}" class="flex flex-wrap gap-3 items-end">
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-600">Tanggal Mulai</label>
                    <input type="datetime-local" name="start_date"
                           value="{{ $startDate ?? '' }}"
                           min="{{ now()->format('Y-m-d\TH:i') }}"
                           required
                           class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-600">Tanggal Selesai</label>
                    <input type="datetime-local" name="end_date"
                           value="{{ $endDate ?? '' }}"
                           min="{{ now()->addDay()->format('Y-m-d\TH:i') }}"
                           required
                           class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                </div>
                @if($type)
                <input type="hidden" name="type" value="{{ $type }}">
                @endif
                <button type="submit"
                        class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    Cari Kendaraan
                </button>
                @if($startDate && $endDate)
                <a href="{{ route('vehicles.index') }}"
                   class="px-4 py-2 border border-gray-200 text-gray-500 text-sm rounded-lg hover:bg-gray-50 transition">
                    Reset
                </a>
                @endif
            </form>
        </div>

        {{-- ── HASIL PENCARIAN ──────────────────────────────────────────
             Hanya muncul setelah user memilih tanggal
        ──────────────────────────────────────────────────────────────── --}}
        @if($vehicles !== null)

        {{-- Info rentang yang dipilih --}}
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div class="text-sm text-gray-600">
                Kendaraan tersedia untuk
                <span class="font-semibold text-indigo-600">
                    {{ \Carbon\Carbon::parse($startDate)->format('d M Y H:i') }}
                </span>
                —
                <span class="font-semibold text-indigo-600">
                    {{ \Carbon\Carbon::parse($endDate)->format('d M Y H:i') }}
                </span>
                <span class="text-gray-400">({{ $durationDays }} hari)</span>
            </div>
            <span class="text-xs text-gray-400">{{ $vehicles->total() }} kendaraan tersedia</span>
        </div>

        {{-- Filter tipe --}}
        <div class="flex gap-2 flex-wrap">
            @foreach([''=>'Semua','MPV'=>'MPV','SUV'=>'SUV','Van'=>'Van','Sedan'=>'Sedan','Minibus'=>'Minibus'] as $val => $label)
            <a href="{{ route('vehicles.index', ['type' => $val, 'start_date' => $startDate, 'end_date' => $endDate]) }}"
               class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ $type == $val ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>

        {{-- Grid kendaraan --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($vehicles as $v)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
                <div class="h-36 bg-gray-100 overflow-hidden">
                    @if(!empty($v->images[0]))
                        @php
                            preg_match('/_(\d+)-(\d+)/', pathinfo($v->images[0], PATHINFO_FILENAME), $m);
                            $fx = $m[1] ?? 50; $fy = $m[2] ?? 50;
                        @endphp
                        <img src="{{ Storage::url($v->images[0]) }}"
                             alt="{{ $v->name }}"
                             class="w-full h-full object-cover"
                             style="object-position: {{ $fx }}% {{ $fy }}%">
                    @else
                        <div class="h-full flex items-center justify-center bg-gradient-to-br from-indigo-50 to-gray-100">
                            <svg class="h-16 w-16 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                      d="M19 9l-7-7-7 7M5 9v10a1 1 0 001 1h3m10-11v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                        </div>
                    @endif
                </div>
                <div class="p-4">
                    <h4 class="font-semibold text-gray-800">{{ $v->name }}</h4>
                    <p class="text-xs text-gray-400">{{ $v->type }} · {{ $v->capacity }} orang · {{ $v->year }}</p>
                    <div class="flex flex-wrap gap-1 mt-2">
                        @foreach(array_slice($v->features ?? [], 0, 3) as $f)
                        <span class="px-2 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-full">{{ $f }}</span>
                        @endforeach
                    </div>
                    <div class="flex items-center justify-between mt-3">
                        <div>
                            <p class="text-xs text-gray-400">Total {{ $durationDays }} hari</p>
                            <p class="font-bold text-indigo-600">
                                Rp {{ number_format($v->price_per_day * $durationDays, 0, ',', '.') }}
                                <span class="text-xs font-normal text-gray-400">
                                    (Rp {{ number_format($v->price_per_day, 0, ',', '.') }}/hari)
                                </span>
                            </p>
                        </div>
                        {{-- Tombol Sewa bawa tanggal ke halaman book --}}
                        <a href="{{ route('vehicles.book', ['id' => $v->_id, 'start_date' => $startDate, 'end_date' => $endDate]) }}"
                           class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition">
                            Pilih
                        </a>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-3 text-center py-12">
                <p class="text-gray-400 mb-2">Tidak ada kendaraan yang tersedia di tanggal tersebut.</p>
                <p class="text-xs text-gray-400">Coba ubah rentang tanggal atau periksa kembali nanti.</p>
            </div>
            @endforelse
        </div>

        <div>{{ $vehicles->links() }}</div>

        @else
        {{-- Belum pilih tanggal — tampilkan ilustrasi --}}
        <div class="text-center py-16">
            <svg class="h-16 w-16 text-indigo-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-gray-400 text-sm">Pilih tanggal di atas untuk melihat kendaraan yang tersedia.</p>
        </div>
        @endif

    </div>
</x-app-layout>