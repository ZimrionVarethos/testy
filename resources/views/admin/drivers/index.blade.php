{{-- resources/views/admin/drivers/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Kelola Driver</x-slot>
    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('success'))<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm mb-4">{{ session('success') }}</div>@endif
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Driver</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">SIM</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Rating</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trip</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($drivers as $d)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm">{{ strtoupper(substr($d->name,0,1)) }}</div>
                                <div>
                                    <p class="font-medium text-gray-800">{{ $d->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $d->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-gray-600">{{ $d->driver_profile['license_number'] ?? '-' }}</td>
                        <td class="px-5 py-3">⭐ {{ $d->driver_profile['rating_avg'] ?? 0 }}</td>
                        <td class="px-5 py-3">{{ $d->driver_profile['total_trips'] ?? 0 }}</td>
                        <td class="px-5 py-3">
                            <div class="flex flex-col gap-1">
                                <span class="{{ $d->is_active ? 'text-green-600' : 'text-red-500' }} text-xs font-medium">{{ $d->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                <span class="{{ ($d->driver_profile['is_available'] ?? false) ? 'text-blue-500' : 'text-gray-400' }} text-xs">{{ ($d->driver_profile['is_available'] ?? false) ? 'Tersedia' : 'Sibuk' }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3 flex items-center gap-2">
                            <a href="{{ route('admin.drivers.show', $d->_id) }}" class="text-indigo-500 hover:underline text-xs">Detail</a>
                            <form method="POST" action="{{ route('admin.drivers.toggle', $d->_id) }}">
                                @csrf
                                <button class="text-xs {{ $d->is_active ? 'text-red-500' : 'text-green-600' }} hover:underline">
                                    {{ $d->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-gray-400">Belum ada driver.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-5 py-3 border-t border-gray-100">{{ $drivers->links() }}</div>
        </div>
    </div>
</x-app-layout>