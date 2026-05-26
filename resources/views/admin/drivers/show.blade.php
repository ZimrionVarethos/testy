{{-- resources/views/admin/drivers/show.blade.php --}}
<x-app-layout>
    <x-slot name="header">Detail Driver</x-slot>

    @push('head-scripts')
    <link rel="stylesheet" href="{{ asset('css/admin/drivers-show.css') }}">
    @endpush

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
