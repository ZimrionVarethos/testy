<?php

namespace App\Http\Controllers\Pengguna;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\Booking;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VehicleController extends Controller
{
    public function __construct(private BookingService $bookingService) {}

    /**
     * Step 1 — Pilih tanggal dulu, baru tampil kendaraan yang tersedia.
     * Kalau tanggal belum dipilih, tampilkan form pilih tanggal saja.
     */
    public function index(Request $request)
    {
        $type      = $request->query('type');
        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');

        // Belum pilih tanggal — hanya tampilkan form tanggal
        if (!$startDate || !$endDate) {
            return view('pengguna.vehicles.index', [
                'vehicles'  => null,
                'type'      => $type,
                'startDate' => null,
                'endDate'   => null,
            ]);
        }

        // Validasi tanggal
        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end   = Carbon::parse($endDate)->endOfDay();
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Format tanggal tidak valid.']);
        }

        if ($start->isPast()) {
            return back()->withErrors(['error' => 'Tanggal mulai tidak boleh di masa lalu.']);
        }

        if ($end->lte($start)) {
            return back()->withErrors(['error' => 'Tanggal selesai harus setelah tanggal mulai.']);
        }

        // Cari vehicle ID yang sudah dipesan di rentang ini
        // Exclude booking yang end_date-nya sudah lewat (sudah expired tapi belum di-complete scheduler)
        $bookedVehicleIds = Booking::whereIn('status', [
                Booking::STATUS_CONFIRMED,
                Booking::STATUS_ONGOING,
            ])
            ->where('start_date', '<', $end)
            ->where('end_date', '>', $start)
            ->where('end_date', '>', Carbon::now()) // exclude booking yang sudah expired
            ->whereNotNull('vehicle.vehicle_id')
            ->get()
            ->pluck('vehicle.vehicle_id')
            ->map(fn($id) => (string) $id)
            ->unique()
            ->toArray();

        // Filter kendaraan: exclude maintenance + yang punya booking overlap di rentang ini
        // Kendaraan 'rented' sekarang tetap bisa dipesan kalau tidak ada overlap di rentang yang diminta
        $query = Vehicle::where('status', '!=', 'maintenance')
            ->whereNotIn('_id', $bookedVehicleIds);

        if ($type) $query->where('type', $type);

        $vehicles = $query->orderBy('price_per_day')->paginate(9);

        $durationDays = max(1, (int) ceil($start->floatDiffInDays($end)));

        return view('pengguna.vehicles.index', compact(
            'vehicles', 'type', 'startDate', 'endDate', 'durationDays'
        ));
    }

    public function show(string $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        return view('pengguna.vehicles.show', compact('vehicle'));
    }

    /**
     * Step 2 — Form konfirmasi booking.
     * Tanggal diambil dari query string (sudah dipilih di step 1).
     * Kalau tidak ada tanggal, redirect balik ke index.
     */
    public function book(Request $request, string $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        // Jangan cek status vehicle karena kendaraan 'rented' sekarang
        // bisa saja kosong di tanggal yang diminta
        if ($vehicle->status === 'maintenance') {
            return redirect()->route('vehicles.index')
                ->withErrors(['error' => 'Kendaraan sedang dalam perawatan.']);
        }

        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');

        // Tanggal wajib ada — kalau tidak ada, balik ke index
        if (!$startDate || !$endDate) {
            return redirect()->route('vehicles.index')
                ->withErrors(['error' => 'Pilih tanggal terlebih dahulu.']);
        }

        $start        = Carbon::parse($startDate);
        $end          = Carbon::parse($endDate);
        $durationDays = max(1, (int) ceil($start->floatDiffInDays($end)));
        $totalPrice   = $durationDays * $vehicle->price_per_day;

        // Cek konflik sekali lagi (race condition guard)
        $conflict = Booking::whereIn('status', [
                Booking::STATUS_CONFIRMED,
                Booking::STATUS_ONGOING,
            ])
            ->where('vehicle.vehicle_id', (string) $vehicle->_id)
            ->where('start_date', '<', $end)
            ->where('end_date', '>', $start)
            ->exists();

        if ($conflict) {
            return redirect()->route('vehicles.index', [
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                ])
                ->withErrors(['error' => 'Maaf, kendaraan ini baru saja dipesan orang lain. Pilih kendaraan lain.']);
        }

        return view('pengguna.vehicles.book', compact(
            'vehicle', 'startDate', 'endDate', 'durationDays', 'totalPrice'
        ));
    }

    public function storeBooking(Request $request, string $id)
    {
        $request->validate([
            'start_date'     => 'required|date|after_or_equal:today',
            'end_date'       => 'required|date|after:start_date',
            'pickup_address' => 'required|string',
            'notes'          => 'nullable|string|max:500',
        ]);

        try {
            $booking = $this->bookingService->createBooking([
                'vehicle_id' => $id,
                'start_date' => $request->start_date,
                'end_date'   => $request->end_date,
                'pickup'     => [
                    'address' => $request->pickup_address,
                    'lat'     => (float) $request->pickup_lat ?? 0,
                    'lng'     => (float) $request->pickup_lng ?? 0,
                ],
                'notes' => $request->notes,
            ], Auth::user());

            return redirect(route('bookings.pay', (string) $booking->_id))
                ->with('success', 'Pesanan dibuat! Selesaikan pembayaran dalam 30 menit.');

        } catch (\Exception $e) {
            Log::error('storeBooking error', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}