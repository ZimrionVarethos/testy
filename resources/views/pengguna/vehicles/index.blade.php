{{-- resources/views/pengguna/vehicles/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Sewa Kendaraan</x-slot>
    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

        {{-- Filter tipe --}}
        <div class="flex gap-2 flex-wrap">
            @foreach([''=>'Semua','MPV'=>'MPV','SUV'=>'SUV','Van'=>'Van','Sedan'=>'Sedan','Minibus'=>'Minibus'] as $val => $label)
            <a href="{{ route('vehicles.index', ['type' => $val]) }}"
               class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ $type == $val ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($vehicles as $v)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">

                {{-- Gambar tunggal dengan focal point --}}
                <div class="h-36 bg-gray-100 overflow-hidden">
                    @if(!empty($v->images[0]))
                        @php
                            preg_match('/_(\d+)-(\d+)/', pathinfo($v->images[0], PATHINFO_FILENAME), $m);
                            $fx = $m[1] ?? 50;
                            $fy = $m[2] ?? 50;
                        @endphp
                        <img src="{{ Storage::url($v->images[0]) }}"
                             alt="{{ $v->name }}"
                             class="w-full h-full object-cover"
                             style="object-position: {{ $fx }}% {{ $fy }}%">
                    @else
                        {{-- Fallback placeholder --}}
                        <div class="h-full flex items-center justify-center bg-gradient-to-br from-indigo-50 to-gray-100">
                            <svg class="h-16 w-16 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                      d="M19 9l-7-7-7 7M5 9v10a1 1 0 001 1h3m10-11v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                        </div>
                    @endif
                </div>

                {{-- Info --}}
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
                            <p class="text-xs text-gray-400">Mulai dari</p>
                            <p class="font-bold text-indigo-600">
                                Rp {{ number_format($v->price_per_day, 0, ',', '.') }}
                                <span class="text-xs font-normal text-gray-400">/hari</span>
                            </p>
                        </div>
                        <a href="{{ route('vehicles.book', $v->_id) }}"
                           class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition">
                            Sewa
                        </a>
                    </div>
                </div>

            </div>
            @empty
            <div class="col-span-3 text-center py-12 text-gray-400">Tidak ada kendaraan tersedia.</div>
            @endforelse
        </div>

        <div>{{ $vehicles->links() }}</div>
    </div>
</x-app-layout>