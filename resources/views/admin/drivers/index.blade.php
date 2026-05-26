{{-- resources/views/admin/drivers/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Kelola Driver</x-slot>

    @push('head-scripts')
    <link rel="stylesheet" href="{{ asset('css/admin/drivers-index.css') }}">
    @endpush

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
