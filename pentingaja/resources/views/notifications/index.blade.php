{{-- resources/views/notifications/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Notifikasi</x-slot>
    <div class="py-6 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-3">
        @if(session('success'))<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>@endif

        <div class="flex justify-end">
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button class="text-sm text-indigo-500 hover:underline">Tandai semua dibaca</button>
            </form>
        </div>

        @forelse($notifications as $n)
        <div class="bg-white rounded-xl shadow-sm border {{ $n->is_read ? 'border-gray-100' : 'border-indigo-200' }} p-4 flex items-start gap-3">
            <div class="h-2.5 w-2.5 rounded-full mt-1.5 shrink-0 {{ $n->is_read ? 'bg-gray-300' : 'bg-indigo-500' }}"></div>
            <div class="flex-1">
                <p class="text-sm font-semibold text-gray-800">{{ $n->title }}</p>
                <p class="text-sm text-gray-500 mt-0.5">{{ $n->message }}</p>
                <p class="text-xs text-gray-300 mt-1">{{ \Carbon\Carbon::parse($n->created_at)->diffForHumans() }}</p>
            </div>
            @if(!$n->is_read)
            <form method="POST" action="{{ route('notifications.read', $n->_id) }}">
                @csrf
                <button class="text-xs text-gray-400 hover:text-indigo-500 shrink-0">✓ Baca</button>
            </form>
            @endif
        </div>
        @empty
        <div class="text-center py-12 text-gray-400">Tidak ada notifikasi.</div>
        @endforelse
        <div>{{ $notifications->links() }}</div>
    </div>
</x-app-layout>