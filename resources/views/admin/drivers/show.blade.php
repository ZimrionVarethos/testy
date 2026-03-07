{{-- resources/views/admin/drivers/show.blade.php --}}
<x-app-layout>
    <x-slot name="header">Detail Driver</x-slot>

    <style>
        :root {
            --brand-primary:      #111827;
            --brand-accent:       #6366f1;
            --brand-accent-hover: #4f46e5;
            --brand-accent-light: #eef2ff;
            --surface:            #ffffff;
            --surface-2:          #f9fafb;
            --border:             #e5e7eb;
            --text-primary:       #111827;
            --text-secondary:     #6b7280;
            --text-muted:         #9ca3af;
        }

        .page-wrapper {
            background: var(--surface-2);
            min-height: 100vh;
            padding: 2rem 0;
        }

        .alert-success {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            margin-bottom: 1.25rem;
        }

        /* ── Section card ── */
        .section-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0.875rem;
            overflow: hidden;
            margin-bottom: 1.25rem;
        }

        /* ── Header card ── */
        .header-card {
            padding: 1.5rem;
        }
        .header-inner {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .driver-identity {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .driver-avatar-lg {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 50%;
            background: var(--brand-accent-light);
            color: var(--brand-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.375rem;
            flex-shrink: 0;
            border: 2px solid #c7d2fe;
        }
        .driver-name-lg {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.125rem;
        }
        .driver-contact {
            font-size: 0.8rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* Header actions */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            flex-wrap: wrap;
        }
        .status-pill-lg {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-pill-lg::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }
        .pill-active-lg   { background: #ecfdf5; color: #065f46; }
        .pill-active-lg::before   { background: #10b981; }
        .pill-inactive-lg { background: #fef2f2; color: #991b1b; }
        .pill-inactive-lg::before { background: #ef4444; }

        .btn-toggle {
            padding: 0.4rem 1rem;
            font-size: 0.8rem;
            font-weight: 500;
            border-radius: 0.45rem;
            border: 1px solid var(--border);
            background: var(--surface-2);
            color: var(--text-secondary);
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease;
        }
        .btn-toggle:hover {
            background: var(--border);
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 1rem;
            font-size: 0.8rem;
            font-weight: 500;
            border-radius: 0.45rem;
            background: var(--brand-accent-light);
            color: var(--brand-accent);
            border: 1px solid #c7d2fe;
            text-decoration: none;
            transition: background 0.15s ease;
        }
        .btn-back:hover { background: #e0e7ff; }
        .btn-back svg { width: 13px; height: 13px; }

        /* ── Info grid ── */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.25rem;
        }
        .info-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0.875rem;
            overflow: hidden;
        }
        .info-card-header {
            padding: 0.75rem 1.25rem;
            background: var(--surface-2);
            border-bottom: 1px solid var(--border);
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .info-card-body {
            padding: 1rem 1.25rem;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.45rem 0;
            border-bottom: 1px solid var(--surface-2);
            font-size: 0.8125rem;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: var(--text-muted); }
        .info-value { font-weight: 500; color: var(--text-primary); }

        /* ── Rating display ── */
        .rating-display {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        .star-svg {
            width: 13px;
            height: 13px;
            color: #f59e0b;
        }

        /* ── Table card ── */
        .table-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0.875rem;
            overflow: hidden;
        }
        .table-card-header {
            padding: 0.875rem 1.25rem;
            border-bottom: 1px solid var(--border);
            background: var(--surface-2);
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .table-card table {
            width: 100%;
            font-size: 0.8125rem;
            border-collapse: collapse;
        }
        .table-card thead th {
            padding: 0.625rem 1.25rem;
            text-align: left;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: var(--surface-2);
            border-bottom: 1px solid var(--border);
        }
        .table-card tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background 0.1s ease;
        }
        .table-card tbody tr:last-child { border-bottom: none; }
        .table-card tbody tr:hover { background: var(--surface-2); }
        .table-card tbody td {
            padding: 0.75rem 1.25rem;
            color: var(--text-secondary);
            vertical-align: middle;
        }

        /* Status badges in table */
        .booking-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.2rem 0.6rem;
            border-radius: 2rem;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        .badge-pending     { background: #fffbeb; color: #92400e; }
        .badge-accepted    { background: #eff6ff; color: #1e40af; }
        .badge-confirmed   { background: var(--brand-accent-light); color: var(--brand-accent); }
        .badge-ongoing     { background: #ecfdf5; color: #065f46; }
        .badge-completed   { background: var(--surface-2); color: var(--text-secondary); border: 1px solid var(--border); }
        .badge-cancelled   { background: #fef2f2; color: #991b1b; }

        .booking-code-link {
            color: var(--brand-accent);
            font-weight: 500;
            text-decoration: none;
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
        }
        .booking-code-link:hover { text-decoration: underline; }

        .empty-row td {
            padding: 3rem 1.25rem;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.8125rem;
        }
    </style>

    <div class="page-wrapper">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            @if(session('success'))
            <div class="alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                {{ session('success') }}
            </div>
            @endif

            {{-- Header Card --}}
            <div class="section-card" style="margin-bottom:1.25rem">
                <div class="header-card">
                    <div class="header-inner">
                        <div class="driver-identity">
                            <div class="driver-avatar-lg">{{ strtoupper(substr($driver->name, 0, 1)) }}</div>
                            <div>
                                <p class="driver-name-lg">{{ $driver->name }}</p>
                                <p class="driver-contact">
                                    {{ $driver->email }}<br>
                                    {{ $driver->phone ?? '-' }}
                                </p>
                            </div>
                        </div>
                        <div class="header-actions">
                            <span class="status-pill-lg {{ $driver->is_active ? 'pill-active-lg' : 'pill-inactive-lg' }}">
                                {{ $driver->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                            <form method="POST" action="{{ route('admin.drivers.toggle', $driver->_id) }}">
                                @csrf
                                <button type="submit" class="btn-toggle">
                                    {{ $driver->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                            <a href="{{ route('admin.drivers.index') }}" class="btn-back">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                                Kembali
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Info Grid --}}
            @php $dp = $driver->driver_profile ?? []; @endphp
            <div class="info-grid">

                <div class="info-card">
                    <div class="info-card-header">Info Akun</div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <span class="info-label">Role</span>
                            <span class="info-value" style="text-transform:capitalize">{{ $driver->role }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Bergabung</span>
                            <span class="info-value">{{ \Carbon\Carbon::parse($driver->created_at)->format('d M Y') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email Verified</span>
                            <span class="info-value">
                                {{ $driver->email_verified_at ? \Carbon\Carbon::parse($driver->email_verified_at)->format('d M Y') : '—' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-card-header">Profil Driver</div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <span class="info-label">No. SIM</span>
                            <span class="info-value" style="font-family:'Courier New',monospace;font-size:0.75rem">{{ $dp['license_number'] ?? '—' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Masa Berlaku</span>
                            <span class="info-value">
                                {{ isset($dp['license_expiry']) ? \Carbon\Carbon::parse($dp['license_expiry'])->format('d M Y') : '—' }}
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Ketersediaan</span>
                            <span class="info-value {{ ($dp['is_available'] ?? false) ? '' : '' }}"
                                  style="color: {{ ($dp['is_available'] ?? false) ? '#059669' : 'var(--text-muted)' }}">
                                {{ ($dp['is_available'] ?? false) ? 'Tersedia' : 'Sedang Bertugas' }}
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Rating</span>
                            <span class="info-value">
                                <span class="rating-display">
                                    <svg class="star-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    {{ number_format($dp['rating_avg'] ?? 0, 1) }}
                                </span>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Total Trip</span>
                            <span class="info-value">{{ $dp['total_trips'] ?? 0 }} trip</span>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Riwayat Booking --}}
            <div class="table-card">
                <div class="table-card-header">Riwayat Booking — 10 Terakhir</div>
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Pengguna</th>
                            <th>Kendaraan</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $b)
                        <tr>
                            <td>
                                <a href="{{ route('admin.bookings.show', $b->_id) }}" class="booking-code-link">
                                    {{ $b->booking_code }}
                                </a>
                            </td>
                            <td>{{ $b->user['name'] ?? '—' }}</td>
                            <td>{{ $b->vehicle['name'] ?? '—' }}</td>
                            <td style="font-size:0.75rem; white-space:nowrap">
                                {{ \Carbon\Carbon::parse($b->start_date)->format('d M Y') }} –
                                {{ \Carbon\Carbon::parse($b->end_date)->format('d M Y') }}
                            </td>
                            <td>
                                <span class="booking-badge badge-{{ $b->status }}">
                                    {{ ucfirst($b->status) }}
                                </span>
                            </td>
                            <td style="font-weight:600; color:var(--text-primary); white-space:nowrap">
                                Rp {{ number_format($b->total_price, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr class="empty-row">
                            <td colspan="6">
                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 0.625rem;opacity:0.25;display:block"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                Belum ada riwayat booking.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>