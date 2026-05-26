{{-- resources/views/admin/vehicles/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Kelola Kendaraan</x-slot>

    @push('head-scripts')
    <link rel="stylesheet" href="{{ asset('css/admin/vehicles.css') }}">
    @endpush

    <div class="page-wrapper">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if(session('success'))
            <div class="alert alert-success" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                {{ session('success') }}
            </div>
            @endif

            @if($errors->any())
            <div class="alert alert-error" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                {{ $errors->first() }}
            </div>
            @endif

            {{-- Toolbar --}}
            <div class="toolbar">
                <div class="filter-tabs">
                    @foreach([''=>'Semua','available'=>'Tersedia','rented'=>'Disewa','maintenance'=>'Maintenance'] as $val => $label)
                    <a href="{{ route('admin.vehicles.index', array_filter(['status' => $val])) }}"
                       class="filter-tab {{ ($status ?? '') === $val ? 'active' : '' }}">
                        {{ $label }}
                    </a>
                    @endforeach
                </div>

                <a href="{{ route('admin.vehicles.create') }}" class="btn-add">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Tambah Kendaraan
                </a>
            </div>

            {{-- Grid --}}
            <div class="vehicle-grid">
                @forelse($vehicles as $v)
                <div class="vehicle-card">

                    {{-- Image --}}
                    <div class="card-image">
                        @php
                            $images = $v->images ?? [];
                            $firstImage = !empty($images) ? $images[0] : null;

                            // Parse focal point from filename: vehicles/abc123_50-70.jpg - x=50, y=70
                            $focalX = 50;
                            $focalY = 50;
                            if ($firstImage) {
                                if (preg_match('/_(\d+)-(\d+)\.\w+$/', $firstImage, $m)) {
                                    $focalX = (int)$m[1];
                                    $focalY = (int)$m[2];
                                }
                            }
                        @endphp

                        @if($firstImage)
                            <img
                                src="{{ str_starts_with($firstImage, 'http') ? $firstImage : url('storage/' . $firstImage) }}"
                                alt="{{ $v->name }}"
                                style="object-position: {{ $focalX }}% {{ $focalY }}%;"
                                loading="lazy"
                            >
                        @else
                            <div class="card-image-placeholder">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><circle cx="12" cy="13" r="3"/>
                                </svg>
                                <span>Belum ada foto</span>
                            </div>
                        @endif

                        <span class="status-badge status-{{ $v->status }}">
                            {{ ucfirst($v->status) }}
                        </span>
                    </div>

                    {{-- Body --}}
                    <div class="card-body">
                        <div class="card-header-row">
                            <div>
                                <h4 class="card-title">{{ $v->name }}</h4>
                                <p class="card-subtitle">{{ $v->plate_number }} &middot; {{ $v->year }}</p>
                            </div>
                        </div>

                        <div class="card-meta">
                            <div class="meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                                {{ $v->type }}
                            </div>
                            <div class="meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                {{ $v->capacity }} orang
                            </div>
                            <div class="meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                {{ $v->brand }} {{ $v->model }}
                            </div>
                            <div class="card-rating" style="margin:0">
                                <svg class="star-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                {{ number_format($v->rating_avg ?? 0, 1) }}
                            </div>
                        </div>

                        <div class="card-price">
                            <span class="price-label">Harga / hari</span>
                            <span class="price-value">Rp {{ number_format($v->price_per_day, 0, ',', '.') }}</span>
                        </div>

                        <div class="card-actions">
                            <a href="{{ route('admin.vehicles.edit', $v->_id) }}" class="btn-edit">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                Edit
                            </a>
                            <form method="POST" action="{{ route('admin.vehicles.destroy', $v->_id) }}" onsubmit="return confirm('Hapus kendaraan ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-delete">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/><rect x="9" y="11" width="14" height="10" rx="1"/><circle cx="12" cy="16" r="1"/></svg>
                    <p>Belum ada kendaraan yang terdaftar.</p>
                </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            <div class="pagination-wrapper">
                {{ $vehicles->links() }}
            </div>

        </div>
    </div>
</x-app-layout>
