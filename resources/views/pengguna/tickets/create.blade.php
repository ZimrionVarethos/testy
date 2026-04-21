{{-- resources/views/pengguna/tickets/create.blade.php --}}
<x-app-layout>
    <x-slot name="header">Buat Tiket Bantuan</x-slot>

    <div class="py-6 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <a href="{{ route('bookings.show', $booking->_id) }}"
           class="text-sm text-indigo-500 hover:underline">← Kembali ke Pesanan</a>

        {{-- Info pesanan --}}
        <div class="bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-3 flex gap-3 items-center">
            <svg class="w-4 h-4 text-indigo-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/>
            </svg>
            <div class="text-sm text-indigo-700">
                Pesanan <span class="font-semibold">{{ $booking->booking_code }}</span>
                · {{ $booking->vehicle['name'] }}
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-800 mb-4">Detail Tiket</h2>

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-lg px-4 py-3 mb-4">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('tickets.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="booking_id" value="{{ $booking->_id }}">

                {{-- Subject --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subjek</label>
                    <input type="text" name="subject"
                           value="{{ old('subject', 'Bantuan untuk pesanan ' . $booking->booking_code) }}"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400"
                           maxlength="200" required>
                </div>

                {{-- Priority --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prioritas</label>
                    <div class="flex gap-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="priority" value="normal"
                                   {{ old('priority', 'normal') === 'normal' ? 'checked' : '' }}
                                   class="text-indigo-600">
                            <span class="text-sm text-gray-700">Normal</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="priority" value="urgent"
                                   {{ old('priority') === 'urgent' ? 'checked' : '' }}
                                   class="text-indigo-600">
                            <span class="text-sm text-red-600 font-medium">Urgent</span>
                        </label>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Pilih Urgent hanya untuk masalah kritis yang membutuhkan respons segera.</p>
                </div>

                {{-- Message --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pesan</label>
                    <textarea name="message" rows="5"
                              class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm
                                     focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400
                                     resize-none"
                              placeholder="Jelaskan masalah atau pertanyaan Anda secara detail..."
                              maxlength="2000" required>{{ old('message') }}</textarea>
                    <p class="text-xs text-gray-400 mt-1">Maks. 2000 karakter.</p>
                </div>

                <button type="submit"
                        class="w-full py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg
                               hover:bg-indigo-700 transition">
                    Kirim Tiket
                </button>
            </form>
        </div>
    </div>
</x-app-layout>