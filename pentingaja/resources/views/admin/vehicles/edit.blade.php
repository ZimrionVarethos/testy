{{-- resources/views/admin/vehicles/edit.blade.php --}}
<x-app-layout>
    <x-slot name="header">Edit Kendaraan</x-slot>
    <div class="py-6 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mb-4">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('admin.vehicles.update', $vehicle->_id) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('admin.vehicles._form')
                <div class="mt-6 flex justify-end gap-3">
                    <a href="{{ route('admin.vehicles.index') }}" class="px-4 py-2 text-sm rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition">Batal</a>
                    <button type="submit" class="px-4 py-2 text-sm rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition">Update</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>