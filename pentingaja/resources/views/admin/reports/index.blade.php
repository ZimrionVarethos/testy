{{-- resources/views/admin/reports/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Laporan & Statistik</x-slot>
    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach([
                ['Total Pesanan',    $stats['total_bookings'],     'text-gray-800'],
                ['Selesai',          $stats['completed_bookings'], 'text-green-500'],
                ['Dibatalkan',       $stats['cancelled_bookings'], 'text-red-500'],
                ['Sedang Berjalan',  $stats['ongoing_bookings'],   'text-blue-500'],
            ] as [$label, $val, $color])
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 text-center">
                <p class="text-3xl font-bold {{ $color }}">{{ $val }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $label }}</p>
            </div>
            @endforeach
        </div>

        <div class="grid grid-cols-3 gap-4">
            @foreach([
                ['Total Kendaraan', $stats['total_vehicles'], 'bg-indigo-50 text-indigo-700'],
                ['Total Driver',    $stats['total_drivers'],  'bg-blue-50 text-blue-700'],
                ['Total Pengguna',  $stats['total_users'],    'bg-purple-50 text-purple-700'],
            ] as [$label, $val, $color])
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
                <div class="h-12 w-12 rounded-xl {{ $color }} flex items-center justify-center text-2xl font-bold">{{ $val }}</div>
                <p class="text-sm font-medium text-gray-700">{{ $label }}</p>
            </div>
            @endforeach
        </div>

        {{-- Booking per bulan --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h4 class="font-semibold text-gray-700 mb-4">Pesanan Selesai per Bulan</h4>
            <div class="flex items-end gap-2 h-32">
                @php $max = max(array_column($monthlyData, 'count')) ?: 1; @endphp
                @foreach($monthlyData as $m)
                <div class="flex-1 flex flex-col items-center gap-1">
                    <span class="text-xs text-gray-500">{{ $m['count'] }}</span>
                    <div class="w-full bg-indigo-500 rounded-t-md transition-all"
                         style="height: {{ ($m['count'] / $max) * 100 }}px; min-height: 4px;"></div>
                    <span class="text-xs text-gray-400 text-center leading-tight">{{ $m['label'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>