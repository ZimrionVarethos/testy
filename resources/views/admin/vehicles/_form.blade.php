{{-- resources/views/admin/vehicles/_form.blade.php --}}
@php $isEdit = isset($vehicle); @endphp

<div class="grid grid-cols-2 gap-4">
    <div class="col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kendaraan</label>
        <input type="text" name="name" value="{{ old('name', $vehicle->name ?? '') }}"
            class="w-full border border-gray-300 px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500" required>
        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
        <input type="text" name="brand" value="{{ old('brand', $vehicle->brand ?? '') }}"
            class="w-full border border-gray-300 px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500" required>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
        <input type="text" name="model" value="{{ old('model', $vehicle->model ?? '') }}"
            class="w-full border border-gray-300 px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500" required>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
        <input type="number" name="year" value="{{ old('year', $vehicle->year ?? date('Y')) }}"
            class="w-full border border-gray-300 px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500" required>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Plat Nomor</label>
        <input type="text" name="plate_number" value="{{ old('plate_number', $vehicle->plate_number ?? '') }}"
            class="w-full border border-gray-300 px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500" required>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe</label>
        <select name="type" class="w-full border border-gray-300 px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500" required>
            @foreach(['MPV','SUV','Van','Sedan','Minibus'] as $t)
            <option value="{{ $t }}" {{ old('type', $vehicle->type ?? '') == $t ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Kapasitas (orang)</label>
        <input type="number" name="capacity" min="2" max="20" value="{{ old('capacity', $vehicle->capacity ?? '') }}"
            class="w-full border border-gray-300 px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500" required>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Harga per Hari (Rp)</label>
        <input type="number" name="price_per_day" min="100000" value="{{ old('price_per_day', $vehicle->price_per_day ?? '') }}"
            class="w-full border border-gray-300 px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500" required>
    </div>
    @if($isEdit)
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
        <select name="status" class="w-full border border-gray-300 px-3 py-2 text-sm">
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
               class="w-full border border-gray-300 px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
        <p class="text-xs text-gray-400 mt-1">Contoh: AC, Musik, GPS</p>
    </div>

    {{-- ── FOTO KENDARAAN (maks 1 foto) ── --}}
    <div class="col-span-2" x-data="imageManager()" x-init="init()">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Foto Kendaraan
            <span class="text-xs font-normal text-gray-400 ml-1">(maks. 1 foto)</span>
        </label>

        {{-- STATE: belum ada gambar - drop zone --}}
        <div x-show="!image"
             class="border-2 border-dashed border-gray-300 flex flex-col items-center justify-center cursor-pointer hover:border-blue-400 transition bg-gray-50"
             style="height: 280px;"
             @click="openPicker()">
            <svg class="h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M4 16l4.586-4.586A2 2 0 0111 11h2a2 2 0 011.414.586L19 16M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M4 16h16M12 3v10m0 0l-3-3m3 3l3-3"/>
            </svg>
            <p class="text-sm font-medium text-gray-400">Klik untuk buka galeri Cloudinary</p>
            <p class="text-xs text-gray-300 mt-1">Upload lokal tersedia di dalam popup</p>
        </div>

        {{-- STATE: ada gambar - editor focal point --}}
        <div x-show="image">

            <div class="relative overflow-hidden border border-gray-200 bg-gray-900 select-none"
                 style="height: 320px;">

                {{-- Gambar --}}
                <img x-show="image" :src="image ? image.preview : ''"
                     class="w-full h-full object-cover pointer-events-none"
                     :style="image ? `object-position: ${image.x}% ${image.y}%` : ''">

                {{-- Frame preview dari popup Cloudinary --}}
                <div class="absolute inset-0 pointer-events-none">

                    <div x-show="image" class="absolute pointer-events-none"
                         :style="image ? `left: ${image.x}%; top: ${image.y}%; transform: translate(-50%, -50%)` : ''">
                        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:40px; height:1px; background:white; box-shadow:none;"></div>
                        <div style="position:absolute; left:50%; top:50%; transform:translate(-50%,-50%); width:1px; height:40px; background:white; box-shadow:none;"></div>
                        <div style="position:absolute; width:14px; height:14px; top:50%; left:50%; transform:translate(-50%,-50%); border:2px solid white; border-radius:0; background:rgba(255,255,255,0.25); box-shadow:none;"></div>
                    </div>
                </div>

                {{-- Info bar atas --}}
                <div class="absolute top-0 left-0 right-0 flex items-center justify-between px-3 py-2 pointer-events-none"
                     style="background: linear-gradient(to bottom, rgba(0,0,0,0.55), transparent)">
                    <span x-show="image && image.isNew"
                          class="px-2 py-0.5 bg-blue-500 text-white text-xs">Baru</span>
                    <span class="ml-auto font-mono text-white text-xs opacity-80"
                          x-text="image ? `X: ${image.x}%  Y: ${image.y}%` : ''"></span>
                </div>

                {{-- Info bar bawah --}}
                <div class="absolute bottom-0 left-0 right-0 px-3 py-2 pointer-events-none"
                     style="background: linear-gradient(to top, rgba(0,0,0,0.5), transparent)">
                    <p class="text-white text-xs opacity-80 text-center">Frame diatur dari popup Cloudinary</p>
                </div>

                {{-- Tombol hapus --}}
                <button type="button"
                        @click.stop="removeImage()"
                        class="pointer-events-auto absolute top-2 right-2 w-7 h-7 bg-red-500 hover:bg-red-600 text-white text-sm flex items-center justify-center transition z-10">
                    ✕
                </button>
                <button type="button"
                        @click.stop="openPicker()"
                        class="pointer-events-auto absolute top-2 left-2 px-3 h-7 bg-white text-gray-900 text-xs font-semibold flex items-center justify-center transition z-10">
                    Ganti
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

        <input type="hidden" name="asset_id" :value="image && image.assetId ? image.assetId : ''">

        @error('images.*')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
</div>

@include('admin.partials.cloudinary-picker')

@push('scripts')
<script>
function imageManager() {
    return {
        image: null,

        init() {
            @if($isEdit)
            @php
                $img = !empty($vehicle->images) ? $vehicle->images[0] : null;
                if ($img) {
                    $base = pathinfo(parse_url($img, PHP_URL_PATH) ?? $img, PATHINFO_FILENAME);
                    preg_match('/_(\d+)-(\d+)/', $base, $m);
                    $existingImg = [
                        'path'    => $img,
                        'preview' => str_starts_with($img, 'http') ? $img : '/storage/' . $img,
                        'x'       => isset($m[1]) ? (int)$m[1] : 50,
                        'y'       => isset($m[2]) ? (int)$m[2] : 50,
                    ];
                } else {
                    $existingImg = null;
                }
            @endphp
            @if($existingImg ?? null)
            this.image = {
                preview: @json($existingImg['preview']),
                path: @json($existingImg['path']),
                isNew: false,
                assetId: null,
                x: {{ $existingImg['x'] }},
                y: {{ $existingImg['y'] }},
                file: null,
            };
            @endif
            @endif
        },

        removeImage() {
            this.image = null;
        },

        openPicker() {
            window.CloudinaryPicker.open({
                uploadFolder: 'vehicles',
                label: 'Foto Kendaraan',
                aspect: '3/2',
                width: 1200,
                height: 800,
                onSelect: (asset) => this.selectAsset(asset),
            });
        },

        selectAsset(asset) {
            const frame = asset.frame || {};
            this.image = {
                preview: asset.url,
                path: asset.url,
                isNew: true,
                assetId: asset.id,
                x: frame.x || 50,
                y: frame.y || 50,
                file: null,
            };
        },
    }
}
</script>
@endpush
