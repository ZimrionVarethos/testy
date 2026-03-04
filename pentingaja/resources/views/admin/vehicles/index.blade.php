{{-- resources/views/admin/vehicles/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Kelola Kendaraan</x-slot>
    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @if(session('success'))<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ $errors->first() }}</div>@endif

        <div class="flex items-center justify-between">
            <div class="flex gap-2">
                @foreach([''=>'Semua','available'=>'Tersedia','rented'=>'Disewa','maintenance'=>'Maintenance'] as $val => $label)
                <a href="{{ route('admin.vehicles.index', ['status' => $val]) }}"
                   class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ $status == $val ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    {{ $label }}
                </a>
                @endforeach
            </div>
            <a href="{{ route('admin.vehicles.create') }}" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition">+ Tambah Kendaraan</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($vehicles as $v)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h4 class="font-semibold text-gray-800">{{ $v->name }}</h4>
                        <p class="text-xs text-gray-800">{{ $v->plate_number }} · {{ $v->year }}</p>
                    </div>
                    <span @class(['px-2 py-1 text-xs rounded-full font-medium',
                        'bg-green-100 text-green-700'  => $v->status === 'available',
                        'bg-blue-100 text-blue-700'    => $v->status === 'rented',
                        'bg-yellow-100 text-yellow-700'=> $v->status === 'maintenance',
                    ])>{{ ucfirst($v->status) }}</span>
                </div>
                <div class="text-sm text-gray-600 space-y-1 mb-4">
                    <div class="flex justify-between"><span>Tipe</span><span>{{ $v->type }}</span></div>
                    <div class="flex justify-between"><span>Kapasitas</span><span>{{ $v->capacity }} orang</span></div>
                    <div class="flex justify-between"><span>Harga/hari</span><span class="font-medium text-indigo-600">Rp {{ number_format($v->price_per_day, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span>Rating</span><span>⭐ {{ $v->rating_avg }}</span></div>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.vehicles.edit', $v->_id) }}" class="flex-1 text-center px-3 py-1.5 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200 transition">Edit</a>
                    <form method="POST" action="{{ route('admin.vehicles.destroy', $v->_id) }}" onsubmit="return confirm('Hapus kendaraan ini?')" class="flex-1">
                        @csrf @method('DELETE')
                        <button class="w-full px-3 py-1.5 bg-red-50 text-red-500 text-sm rounded-lg hover:bg-red-100 transition">Hapus</button>
                    </form>
                </div>
            </div>
            @empty
            <div class="col-span-3 text-center py-12 text-gray-400">Belum ada kendaraan.</div>
            @endforelse
        </div>
        <div>{{ $vehicles->links() }}</div>
    </div>
</x-app-layout>