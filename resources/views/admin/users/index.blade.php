{{-- resources/views/admin/users/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Kelola Pengguna</x-slot>
    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('success'))<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm mb-4">{{ session('success') }}</div>@endif
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Pengguna</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Bergabung</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($users as $u)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 font-bold text-sm">{{ strtoupper(substr($u->name,0,1)) }}</div>
                                <div>
                                    <p class="font-medium text-gray-800">{{ $u->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $u->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-gray-500 text-xs">{{ \Carbon\Carbon::parse($u->created_at)->format('d M Y') }}</td>
                        <td class="px-5 py-3">
                            <span class="{{ $u->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }} px-2 py-1 text-xs rounded-full font-medium">
                                {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 flex items-center gap-2">
                            <a href="{{ route('admin.users.show', $u->_id) }}" class="text-indigo-500 hover:underline text-xs">Detail</a>
                            <form method="POST" action="{{ route('admin.users.toggle', $u->_id) }}">
                                @csrf
                                <button class="text-xs {{ $u->is_active ? 'text-red-500' : 'text-green-600' }} hover:underline">
                                    {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-gray-400">Belum ada pengguna.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-5 py-3 border-t border-gray-100">{{ $users->links() }}</div>
        </div>
    </div>
</x-app-layout>