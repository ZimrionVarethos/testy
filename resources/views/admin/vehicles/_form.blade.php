{{-- resources/views/admin/vehicles/_form.blade.php --}}
@php $isEdit = isset($vehicle); @endphp

<div class="grid grid-cols-2 gap-4">
    <div class="col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kendaraan</label>
        <input type="text" name="name" value="{{ old('name', $vehicle->name ?? '') }}"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500" required>
        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
        <input type="text" name="brand" value="{{ old('brand', $vehicle->brand ?? '') }}"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500" required>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
        <input type="text" name="model" value="{{ old('model', $vehicle->model ?? '') }}"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500" required>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
        <input type="number" name="year" value="{{ old('year', $vehicle->year ?? date('Y')) }}"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500" required>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Plat Nomor</label>
        <input type="text" name="plate_number" value="{{ old('plate_number', $vehicle->plate_number ?? '') }}"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500" required>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe</label>
        <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500" required>
            @foreach(['MPV','SUV','Van','Sedan','Minibus'] as $t)
            <option value="{{ $t }}" {{ old('type', $vehicle->type ?? '') == $t ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Kapasitas (orang)</label>
        <input type="number" name="capacity" min="2" max="20" value="{{ old('capacity', $vehicle->capacity ?? '') }}"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500" required>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Harga per Hari (Rp)</label>
        <input type="number" name="price_per_day" min="100000" value="{{ old('price_per_day', $vehicle->price_per_day ?? '') }}"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500" required>
    </div>
    @if($isEdit)
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
        <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            @foreach(['available'=>'Tersedia','rented'=>'Disewa','maintenance'=>'Maintenance'] as $val => $label)
            <option value="{{ $val }}" {{ ($vehicle->status ?? '') == $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @endif
    <div class="col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Fitur (pisahkan dengan koma)</label>
        <input type="text" name="features_raw"
               value="{{ old('features_raw', implode(', ', $vehicle->features ?? [])) }}"
               placeholder="AC, Musik, GPS, Kamera Mundur"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
        <p class="text-xs text-gray-400 mt-1">Contoh: AC, Musik, GPS</p>
    </div>

    {{-- ── FOTO KENDARAAN (maks 1 foto) ── --}}
    <div class="col-span-2" x-data="imageManager()" x-init="init()">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Foto Kendaraan
            <span class="text-xs font-normal text-gray-400 ml-1">(maks. 1 foto)</span>
        </label>

        {{-- STATE: belum ada gambar → drop zone --}}
        <div x-show="!image"
             class="border-2 border-dashed border-gray-300 rounded-xl flex flex-col items-center justify-center cursor-pointer hover:border-indigo-400 transition bg-gray-50"
             style="height: 280px;"
             @click="$refs.fileInput.click()"
             @dragover.prevent
             @drop.prevent="handleDrop($event)">
            <svg class="h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M4 16l4.586-4.586A2 2 0 0111 11h2a2 2 0 011.414.586L19 16M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M4 16h16M12 3v10m0 0l-3-3m3 3l3-3"/>
            </svg>
            <p class="text-sm font-medium text-gray-400">Klik atau drag foto ke sini</p>
            <p class="text-xs text-gray-300 mt-1">JPG, PNG, WEBP · maks 2MB</p>
        </div>

        {{-- STATE: ada gambar → editor focal point --}}
        <div x-show="image">

            <div class="relative rounded-xl overflow-hidden border border-gray-200 bg-gray-900 select-none"
                 style="height: 320px;">

                {{-- Gambar --}}
                <img x-show="image" :src="image ? image.preview : ''"
                     class="w-full h-full object-cover pointer-events-none"
                     :style="image ? `object-position: ${image.x}% ${image.y}%` : ''">

                {{-- Overlay drag (semua arah) --}}
                <div class="absolute inset-0 cursor-crosshair"
                     @mousedown.prevent="startDrag($event)"
                     @mousemove.prevent="onDrag($event)"
                     @mouseup="stopDrag()"
                     @mouseleave="stopDrag()"
                     @touchstart.prevent="startDragTouch($event)"
                     @touchmove.prevent="onDragTouch($event)"
                     @touchend="stopDrag()">

                    {{-- Crosshair di titik fokus --}}
                    <div x-show="image" class="absolute pointer-events-none"
                         :style="image ? `left: ${image.x}%; top: ${image.y}%; transform: translate(-50%, -50%)` : ''">
                        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:40px; height:1px; background:white; box-shadow:0 0 3px rgba(0,0,0,0.8);"></div>
                        <div style="position:absolute; left:50%; top:50%; transform:translate(-50%,-50%); width:1px; height:40px; background:white; box-shadow:0 0 3px rgba(0,0,0,0.8);"></div>
                        <div style="position:absolute; width:14px; height:14px; top:50%; left:50%; transform:translate(-50%,-50%); border:2px solid white; border-radius:50%; background:rgba(255,255,255,0.25); box-shadow:0 0 4px rgba(0,0,0,0.6);"></div>
                    </div>
                </div>

                {{-- Info bar atas --}}
                <div class="absolute top-0 left-0 right-0 flex items-center justify-between px-3 py-2 pointer-events-none"
                     style="background: linear-gradient(to bottom, rgba(0,0,0,0.55), transparent)">
                    <span x-show="image && image.isNew"
                          class="px-2 py-0.5 bg-indigo-500 text-white text-xs rounded-full">Baru</span>
                    <span class="ml-auto font-mono text-white text-xs opacity-80"
                          x-text="image ? `X: ${image.x}%  Y: ${image.y}%` : ''"></span>
                </div>

                {{-- Info bar bawah --}}
                <div class="absolute bottom-0 left-0 right-0 px-3 py-2 pointer-events-none"
                     style="background: linear-gradient(to top, rgba(0,0,0,0.5), transparent)">
                    <p class="text-white text-xs opacity-80 text-center">Drag ke segala arah untuk mengatur posisi fokus</p>
                </div>

                {{-- Tombol hapus --}}
                <button type="button"
                        @click.stop="removeImage()"
                        class="pointer-events-auto absolute top-2 right-2 w-7 h-7 bg-red-500 hover:bg-red-600 text-white rounded-full text-sm flex items-center justify-center shadow transition z-10">
                    ✕
                </button>
            </div>

            {{-- Hidden inputs --}}
            <template x-if="image && !image.isNew">
                <span>
                    <input type="hidden" name="kept_images[]" :value="image.path">
                    <input type="hidden" name="kept_focal_x" :value="image.x">
                    <input type="hidden" name="kept_focal_y" :value="image.y">
                </span>
            </template>
            <template x-if="image && image.isNew">
                <span>
                    <input type="hidden" name="new_focal_x" :value="image.x">
                    <input type="hidden" name="new_focal_y" :value="image.y">
                </span>
            </template>
        </div>

        {{-- File input (single) --}}
        <input type="file" name="images[]" accept="image/*"
               x-ref="fileInput" class="hidden"
               @change="handleFiles($event.target.files); $event.target.value = '';">

        @error('images.*')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
</div>

@push('scripts')
<script>
function imageManager() {
    return {
        image      : null,
        isDragging : false,

        init() {
            @if($isEdit)
            @php
                $img = !empty($vehicle->images) ? $vehicle->images[0] : null;
                if ($img) {
                    $base = pathinfo($img, PATHINFO_FILENAME);
                    preg_match('/_(\d+)-(\d+)/', $base, $m);
                    $existingImg = [
                        'path'    => $img,
                        'preview' => '/storage/' . $img,
                        'x'       => isset($m[1]) ? (int)$m[1] : 50,
                        'y'       => isset($m[2]) ? (int)$m[2] : 50,
                    ];
                } else {
                    $existingImg = null;
                }
            @endphp
            @if($existingImg ?? null)
            this.image = {
                preview : @json($existingImg['preview']),
                path    : @json($existingImg['path']),
                isNew   : false,
                x       : {{ $existingImg['x'] }},
                y       : {{ $existingImg['y'] }},
                file    : null,
            };
            @endif
            @endif
        },

        handleFiles(fileList) {
            const file = fileList[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => {
                this.image = {
                    preview : e.target.result,
                    path    : null,
                    isNew   : true,
                    x       : 50,
                    y       : 50,
                    file    : file,
                };
                this.syncFileInput();
            };
            reader.readAsDataURL(file);
        },

        handleDrop(e) { this.handleFiles(e.dataTransfer.files); },

        removeImage() {
            this.image = null;
            this.syncFileInput();
        },

        // ── Mouse drag ────────────────────────────────────────────────────
        startDrag(e) {
            this.isDragging = true;
            this.applyFocal(e.currentTarget, e.clientX, e.clientY);
        },
        onDrag(e) {
            if (!this.isDragging) return;
            this.applyFocal(e.currentTarget, e.clientX, e.clientY);
        },
        stopDrag() { this.isDragging = false; },

        // ── Touch drag ────────────────────────────────────────────────────
        startDragTouch(e) {
            this.isDragging = true;
            this.applyFocal(e.currentTarget, e.touches[0].clientX, e.touches[0].clientY);
        },
        onDragTouch(e) {
            if (!this.isDragging) return;
            this.applyFocal(e.currentTarget, e.touches[0].clientX, e.touches[0].clientY);
        },

        // ── Hitung posisi X dan Y dari koordinat kursor/sentuhan ──────────
        applyFocal(el, clientX, clientY) {
            if (!this.image) return;
            const rect = el.getBoundingClientRect();
            this.image.x = Math.min(100, Math.max(0, Math.round(((clientX - rect.left)  / rect.width)  * 100)));
            this.image.y = Math.min(100, Math.max(0, Math.round(((clientY - rect.top)   / rect.height) * 100)));
        },

        syncFileInput() {
            const dt = new DataTransfer();
            if (this.image && this.image.isNew && this.image.file) {
                dt.items.add(this.image.file);
            }
            this.$refs.fileInput.files = dt.files;
        },
    }
}
</script>
@endpush