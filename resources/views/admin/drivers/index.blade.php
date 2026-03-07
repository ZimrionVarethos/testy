{{-- resources/views/admin/drivers/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Kelola Driver</x-slot>

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

        /* ── Table card ── */
        .table-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0.875rem;
            overflow: hidden;
        }

        .table-card table {
            width: 100%;
            font-size: 0.8125rem;
            border-collapse: collapse;
        }

        .table-card thead {
            background: var(--surface-2);
            border-bottom: 1px solid var(--border);
        }

        .table-card thead th {
            padding: 0.75rem 1.25rem;
            text-align: left;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
        }

        .table-card tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background 0.1s ease;
        }
        .table-card tbody tr:last-child { border-bottom: none; }
        .table-card tbody tr:hover { background: var(--surface-2); }

        .table-card tbody td {
            padding: 0.875rem 1.25rem;
            color: var(--text-secondary);
            vertical-align: middle;
        }

        /* ── Avatar ── */
        .driver-avatar {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 50%;
            background: var(--brand-accent-light);
            color: var(--brand-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.875rem;
            flex-shrink: 0;
            border: 1.5px solid #c7d2fe;
        }

        .driver-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.125rem;
        }
        .driver-email {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* ── Status dots ── */
        .status-row {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.7rem;
            font-weight: 500;
        }
        .status-pill::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .pill-active   { color: #065f46; }
        .pill-active::before   { background: #10b981; }
        .pill-inactive { color: #991b1b; }
        .pill-inactive::before { background: #ef4444; }
        .pill-available { color: #1e40af; }
        .pill-available::before { background: #3b82f6; }
        .pill-busy { color: var(--text-muted); }
        .pill-busy::before { background: #d1d5db; }

        /* ── Rating ── */
        .rating-cell {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            color: var(--text-primary);
            font-weight: 500;
        }
        .star-svg {
            width: 13px;
            height: 13px;
            color: #f59e0b;
            flex-shrink: 0;
        }

        /* ── SIM badge ── */
        .sim-code {
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
            background: var(--surface-2);
            border: 1px solid var(--border);
            color: var(--text-primary);
            padding: 0.2rem 0.5rem;
            border-radius: 0.3rem;
            letter-spacing: 0.03em;
        }

        /* ── Action buttons ── */
        .action-cell {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-detail {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.35rem 0.75rem;
            background: var(--brand-accent-light);
            color: var(--brand-accent);
            border: 1px solid #c7d2fe;
            border-radius: 0.4rem;
            font-size: 0.75rem;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.15s ease, border-color 0.15s ease;
        }
        .btn-detail:hover {
            background: #e0e7ff;
            border-color: var(--brand-accent);
        }
        .btn-toggle-deactivate {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.35rem 0.75rem;
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
            border-radius: 0.4rem;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .btn-toggle-deactivate:hover { background: #fee2e2; }
        .btn-toggle-activate {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.35rem 0.75rem;
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
            border-radius: 0.4rem;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .btn-toggle-activate:hover { background: #d1fae5; }

        .btn-detail svg,
        .btn-toggle-deactivate svg,
        .btn-toggle-activate svg {
            width: 12px;
            height: 12px;
        }

        /* ── Empty state ── */
        .empty-row td {
            padding: 3.5rem 1.25rem;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.8125rem;
        }

        /* ── Pagination ── */
        .pagination-wrap {
            padding: 0.875rem 1.25rem;
            border-top: 1px solid var(--border);
            background: var(--surface-2);
        }
    </style>

    <div class="page-wrapper">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if(session('success'))
            <div class="alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                {{ session('success') }}
            </div>
            @endif

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Driver</th>
                            <th>No. SIM</th>
                            <th>Rating</th>
                            <th>Total Trip</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($drivers as $d)
                        @php $dp = $d->driver_profile ?? []; @endphp
                        <tr>
                            {{-- Driver --}}
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="driver-avatar">{{ strtoupper(substr($d->name, 0, 1)) }}</div>
                                    <div>
                                        <p class="driver-name">{{ $d->name }}</p>
                                        <p class="driver-email">{{ $d->email }}</p>
                                    </div>
                                </div>
                            </td>

                            {{-- SIM --}}
                            <td>
                                @if(!empty($dp['license_number']))
                                    <span class="sim-code">{{ $dp['license_number'] }}</span>
                                @else
                                    <span style="color:var(--text-muted)">—</span>
                                @endif
                            </td>

                            {{-- Rating --}}
                            <td>
                                <div class="rating-cell">
                                    <svg class="star-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                    {{ number_format($dp['rating_avg'] ?? 0, 1) }}
                                </div>
                            </td>

                            {{-- Trip --}}
                            <td style="font-weight:500; color:var(--text-primary)">
                                {{ $dp['total_trips'] ?? 0 }}
                                <span style="font-weight:400; color:var(--text-muted); font-size:0.75rem"> trip</span>
                            </td>

                            {{-- Status --}}
                            <td>
                                <div class="status-row">
                                    <span class="status-pill {{ $d->is_active ? 'pill-active' : 'pill-inactive' }}">
                                        {{ $d->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                    <span class="status-pill {{ ($dp['is_available'] ?? false) ? 'pill-available' : 'pill-busy' }}">
                                        {{ ($dp['is_available'] ?? false) ? 'Tersedia' : 'Sedang Bertugas' }}
                                    </span>
                                </div>
                            </td>

                            {{-- Aksi --}}
                            <td>
                                <div class="action-cell">
                                    <a href="{{ route('admin.drivers.show', $d->_id) }}" class="btn-detail">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Detail
                                    </a>
                                    <form method="POST" action="{{ route('admin.drivers.toggle', $d->_id) }}">
                                        @csrf
                                        @if($d->is_active)
                                        <button type="submit" class="btn-toggle-deactivate">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                                            Nonaktifkan
                                        </button>
                                        @else
                                        <button type="submit" class="btn-toggle-activate">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                                            Aktifkan
                                        </button>
                                        @endif
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr class="empty-row">
                            <td colspan="6">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 0.75rem; opacity:0.25; display:block"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                Belum ada driver terdaftar.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="pagination-wrap">{{ $drivers->links() }}</div>
            </div>

        </div>
    </div>
</x-app-layout>