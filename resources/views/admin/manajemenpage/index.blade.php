{{-- resources/views/admin/manajemenpage/index.blade.php --}}

<x-app-layout>
<div class="p-6 max-w-6xl mx-auto space-y-8">

    {{-- ── Header ── --}}
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Manajemen Landing Page</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola semua gambar yang tampil di halaman utama publik</p>
        </div>
        @if(session('success'))
        <div class="flex items-center gap-2 bg-green-50 text-green-700 border border-green-200 rounded-xl px-4 py-2.5 text-sm font-medium">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif
        @if(session('info'))
        <div class="flex items-center gap-2 bg-amber-50 text-amber-700 border border-amber-200 rounded-xl px-4 py-2.5 text-sm font-medium">
            {{ session('info') }}
        </div>
        @endif
    </div>

    {{-- ════════════════════════════════════════════
         SECTION 1 — HERO SLIDER
    ════════════════════════════════════════════ --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
         x-data="heroSliderManager()">

        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/70">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-1.5 h-5 bg-gray-900 rounded-full"></div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900 tracking-wide uppercase">Hero Slider</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Gambar background slider di halaman utama.</p>
                    </div>
                </div>
                <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200 rounded-lg px-3 py-1.5">
                    16 : 9 &nbsp;•&nbsp; Landscape &nbsp;•&nbsp; Rekomendasi <strong class="ml-1">1920 × 1080px</strong>
                </span>
            </div>
        </div>

        <form action="{{ route('admin.landing.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="p-6 space-y-6">

                {{-- Slide tersimpan --}}
                @if($heroSlides->count())
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">
                        Slide Tersimpan ({{ $heroSlides->count() }})
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($heroSlides as $key => $url)
                        <div class="group" x-data="imageUpload()">
                            <div class="relative overflow-hidden rounded-xl border-2 border-gray-200 cursor-pointer
                                        hover:border-blue-400 transition-colors bg-gray-100"
                                 style="aspect-ratio:16/9"
                                 @click="$refs.inp.click()">
                                <img :src="preview || '{{ $url }}'" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100
                                            transition-opacity flex items-center justify-center">
                                    <span class="text-white text-xs font-bold bg-black/60 rounded-lg px-3 py-1.5">Ganti Gambar</span>
                                </div>
                                <div x-show="preview"
                                     class="absolute top-2 left-2 bg-green-500 text-white text-[10px] font-bold rounded-full px-2 py-0.5">BARU</div>
                                <div class="absolute bottom-2 left-2 bg-black/60 text-white text-[10px] font-bold rounded px-2 py-0.5">
                                    Slide {{ str_replace('hero_slide_', '', $key) }}
                                </div>
                            </div>
                            {{-- name pakai key string yang eksplisit --}}
                            <input type="file" name="images[{{ $key }}]" accept="image/*"
                                   class="hidden" x-ref="inp" @change="handleFile($event)">
                            <div class="flex items-center justify-between mt-2">
                                <span x-show="preview" x-text="fileName"
                                      class="text-[11px] text-green-600 font-medium truncate max-w-[130px]"></span>
                                <span x-show="!preview" class="text-[11px] text-gray-400">Klik preview untuk ganti</span>
                                <div class="flex gap-1.5 shrink-0">
                                    <button type="button" x-show="preview"
                                            @click="preview=null;fileName=null;$refs.inp.value=''"
                                            class="text-[11px] font-semibold text-gray-500 bg-gray-100 hover:bg-gray-200 rounded-lg px-2 py-1">
                                        Batal
                                    </button>
                                    <a href="{{ route('admin.landing.slides.destroy', $key) }}"
                                       x-show="!preview"
                                       onclick="return confirm('Hapus slide ini? File akan langsung dihapus dari server.')"
                                       class="text-[11px] font-semibold text-red-500 bg-red-50 hover:bg-red-100 rounded-lg px-2 py-1">
                                        Hapus Slide
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-6 py-8 text-center">
                    <p class="text-sm text-gray-400">Belum ada slide tersimpan. Tambah slide baru di bawah.</p>
                </div>
                @endif

                {{-- Slot slide baru --}}
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Tambah Slide Baru</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <template x-for="(slot, index) in newSlots" :key="slot.id">
                            <div x-data="imageUpload()">
                                <div class="relative overflow-hidden rounded-xl border-2 border-dashed border-gray-300
                                            hover:border-blue-400 transition-colors bg-gray-50 cursor-pointer"
                                     style="aspect-ratio:16/9"
                                     @click="$refs.newInp.click()">
                                    <div x-show="!preview"
                                         class="absolute inset-0 flex flex-col items-center justify-center gap-2">
                                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span class="text-xs text-gray-400">Klik untuk pilih gambar</span>
                                    </div>
                                    <img x-show="preview" :src="preview" class="w-full h-full object-cover">
                                    <div x-show="preview"
                                         class="absolute top-2 left-2 bg-blue-500 text-white text-[10px] font-bold rounded-full px-2 py-0.5">BARU</div>
                                </div>
                                <input type="file" name="new_slides[]" accept="image/*"
                                       class="hidden" x-ref="newInp" @change="handleFile($event)">
                                <div class="flex items-center justify-between mt-2">
                                    <span x-show="preview" x-text="fileName"
                                          class="text-[11px] text-blue-600 font-medium truncate max-w-[140px]"></span>
                                    <span x-show="!preview" class="text-[11px] text-gray-400">Slide baru</span>
                                    <button type="button" @click="$parent.removeSlot(index)"
                                            class="text-[11px] font-semibold text-red-400 bg-red-50 hover:bg-red-100 rounded-lg px-2 py-1">
                                        Hapus Slot
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="addSlot()"
                            class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-gray-600
                                   hover:text-gray-900 border border-dashed border-gray-300 hover:border-gray-500
                                   rounded-xl px-5 py-3 transition-all hover:bg-gray-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Slide
                    </button>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50/70 border-t border-gray-100 flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-gray-900 hover:bg-gray-700 text-white
                               font-semibold text-sm rounded-xl px-6 py-2.5 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Simpan Slider
                </button>
            </div>
        </form>
    </div>

    {{-- ════════════════════════════════════════════
         SECTION 2 — GAMBAR STATIS
         FIX: groupBy manual pakai array biasa agar key string tetap terjaga
    ════════════════════════════════════════════ --}}

    {{-- Kelompokkan manual — JANGAN pakai collect()->groupBy() karena mereset key --}}
    @php
        $sections = [];
        foreach ($fields as $fieldKey => $meta) {
            $sections[$meta['section']][$fieldKey] = $meta;
        }
    @endphp

    <form action="{{ route('admin.landing.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            @foreach($sections as $sectionName => $sectionFields)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/70 flex items-center gap-3">
                    <div class="w-1.5 h-5 bg-gray-900 rounded-full"></div>
                    <h2 class="text-sm font-bold text-gray-900 tracking-wide uppercase">{{ $sectionName }}</h2>
                </div>

                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($sectionFields as $fieldKey => $meta)
                    @php $current = $settings[$fieldKey] ?? null; @endphp

                    <div class="flex flex-col gap-2" x-data="imageUpload()">

                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-700">{{ $meta['label'] }}</span>
                            <span class="text-[10px] font-semibold bg-blue-50 text-blue-600 border border-blue-100 rounded-md px-2 py-0.5">
                                {{ $meta['ratio_label'] }}
                            </span>
                        </div>

                        <div class="relative overflow-hidden rounded-xl border-2 border-dashed cursor-pointer
                                    transition-colors bg-gray-100 group"
                             :class="preview ? 'border-green-300 hover:border-green-400' : 'border-gray-200 hover:border-blue-400'"
                             style="aspect-ratio: {{ $meta['aspect'] }}"
                             @click="$refs.sInp.click()">

                            <img :src="preview || '{{ $current }}'"
                                 x-show="preview || {{ $current ? 'true' : 'false' }}"
                                 class="w-full h-full object-cover">

                            <div x-show="!preview && {{ $current ? 'false' : 'true' }}"
                                 class="absolute inset-0 flex flex-col items-center justify-center gap-2 p-4 text-center">
                                <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-[11px] text-gray-400 leading-tight">Pakai gambar default</span>
                            </div>

                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100
                                        transition-opacity flex items-center justify-center">
                                <span class="text-white text-xs font-bold bg-black/60 rounded-lg px-3 py-1.5">Ganti Gambar</span>
                            </div>

                            <div x-show="preview"
                                 class="absolute top-2 left-2 bg-green-500 text-white text-[10px] font-bold rounded-full px-2 py-0.5">BARU</div>
                        </div>

                        {{-- name pakai $fieldKey string yang benar --}}
                        <input type="file" name="images[{{ $fieldKey }}]" accept="image/*"
                               class="hidden" x-ref="sInp" @change="handleFile($event)">

                        {{-- Note ukuran --}}
                        <div class="flex items-start gap-1.5 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                            <svg class="w-3.5 h-3.5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-[10px] font-bold text-amber-700">Rekomendasi: {{ $meta['recommended'] }}</p>
                                <p class="text-[10px] text-amber-600 leading-snug mt-0.5">{{ $meta['note'] }}</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <span x-show="preview" x-text="fileName"
                                  class="text-[11px] text-green-600 font-medium truncate max-w-[140px]"></span>
                            <span x-show="!preview" class="text-[11px] text-gray-400">JPG, PNG, WEBP · max 5MB</span>
                            <div class="flex gap-1.5 shrink-0">
                                <button type="button" @click="$refs.sInp.click()"
                                        class="text-[11px] font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg px-2.5 py-1">
                                    <span x-text="preview ? 'Ganti' : 'Pilih'"></span>
                                </button>
                                <button type="button" x-show="preview"
                                        @click="preview=null;fileName=null;$refs.sInp.value=''"
                                        class="text-[11px] font-semibold text-red-400 bg-red-50 hover:bg-red-100 rounded-lg px-2.5 py-1">
                                    Batal
                                </button>
                                @if($current)
                                <a href="{{ route('admin.landing.destroy', $fieldKey) }}"
                                   x-show="!preview"
                                   onclick="return confirm('Hapus gambar ini? File akan langsung dihapus dari server.')"
                                   class="text-[11px] font-semibold text-red-400 bg-red-50 hover:bg-red-100 rounded-lg px-2.5 py-1">
                                    Hapus
                                </a>
                                @endif
                            </div>
                        </div>

                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        <div class="flex items-center justify-between pt-4">
            <p class="text-xs text-gray-400">Hanya gambar yang dipilih ulang yang akan diperbarui.</p>
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-gray-900 hover:bg-gray-700 text-white
                           font-semibold text-sm rounded-xl px-6 py-3 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Simpan Gambar Statis
            </button>
        </div>
    </form>

</div>

<script>
function imageUpload() {
    return {
        preview: null,
        fileName: null,
        handleFile(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.fileName = file.name;
            const reader = new FileReader();
            reader.onload = e => { this.preview = e.target.result; };
            reader.readAsDataURL(file);
        }
    }
}

function heroSliderManager() {
    return {
        newSlots: [],
        addSlot()         { this.newSlots.push({ id: Date.now() }); },
        removeSlot(index) { this.newSlots.splice(index, 1); }
    }
}
</script>
</x-app-layout>