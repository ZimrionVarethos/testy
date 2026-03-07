{{-- resources/views/admin/vehicles/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Kelola Kendaraan</x-slot>

    <style>
        :root {
            /* Diselaraskan dengan sidebar bg-gray-900 + indigo-500 */
            --brand-primary:      #111827; /* gray-900 — sama dengan sidebar */
            --brand-accent:       #6366f1; /* indigo-500 */
            --brand-accent-hover: #4f46e5; /* indigo-600 */
            --brand-accent-light: #eef2ff; /* indigo-50 */
            --surface:            #ffffff;
            --surface-2:          #f9fafb; /* gray-50 */
            --border:             #e5e7eb; /* gray-200 */
            --text-primary:       #111827; /* gray-900 */
            --text-secondary:     #6b7280; /* gray-500 */
            --text-muted:         #9ca3af; /* gray-400 */
            --status-available-bg:   #ecfdf5;
            --status-available-text: #065f46;
            --status-available-dot:  #10b981;
            --status-rented-bg:      #eff6ff;
            --status-rented-text:    #1e40af;
            --status-rented-dot:     #3b82f6;
            --status-maintenance-bg: #fffbeb;
            --status-maintenance-text:#92400e;
            --status-maintenance-dot:#f59e0b;
        }

        .page-wrapper {
            background: var(--surface-2);
            min-height: 100vh;
            padding: 2rem 0;
        }

        /* ── Alert ── */
        .alert {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        /* ── Toolbar ── */
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }
        .filter-tabs {
            display: flex;
            gap: 0.375rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0.625rem;
            padding: 0.25rem;
        }
        .filter-tab {
            padding: 0.4rem 0.875rem;
            border-radius: 0.4rem;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .filter-tab:hover {
            color: var(--text-primary);
            background: var(--surface-2);
        }
        .filter-tab.active {
            background: var(--brand-primary);
            color: #ffffff;
        }
        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1.125rem;
            background: var(--brand-accent);
            color: #ffffff;
            font-size: 0.8125rem;
            font-weight: 600;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: background 0.15s ease;
            letter-spacing: 0.01em;
        }
        .btn-add:hover {
            background: var(--brand-accent-hover);
        }

        /* ── Grid ── */
        .vehicle-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.25rem;
        }

        /* ── Card ── */
        .vehicle-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0.875rem;
            overflow: hidden;
            transition: box-shadow 0.2s ease;
        }
        .vehicle-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.07);
        }

        /* Image area */
        .card-image {
            position: relative;
            width: 100%;
            height: 180px;
            background: #f1f3f5;
            overflow: hidden;
        }
        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }
        .card-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            color: var(--text-muted);
        }
        .card-image-placeholder svg {
            width: 2.5rem;
            height: 2.5rem;
            opacity: 0.35;
        }
        .card-image-placeholder span {
            font-size: 0.75rem;
        }

        /* Status badge overlaid on image */
        .status-badge {
            position: absolute;
            top: 0.625rem;
            right: 0.625rem;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.65rem;
            border-radius: 2rem;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            backdrop-filter: blur(6px);
            text-transform: capitalize;
        }
        .status-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .status-available  { background: var(--status-available-bg);   color: var(--status-available-text); }
        .status-available::before  { background: var(--status-available-dot); }
        .status-rented     { background: var(--status-rented-bg);       color: var(--status-rented-text); }
        .status-rented::before     { background: var(--status-rented-dot); }
        .status-maintenance{ background: var(--status-maintenance-bg);  color: var(--status-maintenance-text); }
        .status-maintenance::before{ background: var(--status-maintenance-dot); }

        /* Card body */
        .card-body {
            padding: 1rem 1.125rem;
        }
        .card-header-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }
        .card-title {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 0.125rem;
            line-height: 1.3;
        }
        .card-subtitle {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .card-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.375rem 0.75rem;
            font-size: 0.8rem;
            margin-bottom: 0.875rem;
        }
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            color: var(--text-secondary);
        }
        .meta-item svg {
            width: 13px;
            height: 13px;
            flex-shrink: 0;
            color: var(--text-muted);
        }

        .card-price {
            display: flex;
            align-items: baseline;
            gap: 0.25rem;
            margin-bottom: 1rem;
            padding: 0.625rem 0.75rem;
            background: var(--brand-primary);
            border-radius: 0.5rem;
        }
        .price-label {
            font-size: 0.7rem;
            color: rgba(255,255,255,0.5);
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .price-value {
            font-size: 1rem;
            font-weight: 700;
            color: #a5b4fc; /* indigo-300 — kontras di atas dark */
            margin-left: auto;
        }
        .price-unit {
            font-size: 0.7rem;
            color: rgba(255,255,255,0.4);
        }

        .card-rating {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-bottom: 0.875rem;
        }
        .star-icon {
            color: #f59e0b;
            width: 13px;
            height: 13px;
        }

        /* Actions */
        .card-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }
        .btn-edit, .btn-delete {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
            padding: 0.5rem 0;
            border-radius: 0.45rem;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.15s ease;
            cursor: pointer;
            text-decoration: none;
            border: none;
            width: 100%;
        }
        .btn-edit {
            background: var(--surface-2);
            border: 1px solid var(--border);
            color: var(--text-primary);
        }
        .btn-edit:hover {
            background: var(--brand-accent-light);
            border-color: var(--brand-accent);
            color: var(--brand-accent);
        }
        .btn-delete {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        .btn-delete:hover {
            background: #dc2626;
            border-color: #dc2626;
            color: #fff;
        }
        .btn-edit svg, .btn-delete svg {
            width: 13px;
            height: 13px;
        }

        /* Empty state */
        .empty-state {
            grid-column: 1 / -1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem 2rem;
            text-align: center;
            color: var(--text-muted);
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0.875rem;
        }
        .empty-state svg {
            width: 3rem;
            height: 3rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        .empty-state p {
            font-size: 0.875rem;
            margin: 0;
        }

        /* Pagination wrapper */
        .pagination-wrapper {
            margin-top: 1.5rem;
        }
    </style>

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

                            // Parse focal point from filename: vehicles/abc123_50-70.jpg → x=50, y=70
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
                                src="{{ url('storage/' . $firstImage) }}"
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