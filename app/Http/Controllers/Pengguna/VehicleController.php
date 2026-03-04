<?php
// ─────────────────────────────────────────────────────────────

namespace App\Http\Controllers\Pengguna;
use App\Http\Controllers\Controller;
use App\Models\Vehicle; use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\Request; use Illuminate\Support\Facades\Auth;

class VehicleController extends Controller{
    public function __construct(private BookingService $bookingService) {}

    public function index(Request $request)
    {
        $type  = $request->query('type');
        $query = Vehicle::where('status', 'available');
        if ($type) $query->where('type', $type);
        $vehicles = $query->orderBy('price_per_day')->paginate(9);
        return view('pengguna.vehicles.index', compact('vehicles', 'type'));
    }

    public function show(string $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        return view('pengguna.vehicles.show', compact('vehicle'));
    }

    public function book(string $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        if (!$vehicle->isAvailable()) return back()->withErrors(['error' => 'Kendaraan tidak tersedia.']);
        return view('pengguna.vehicles.book', compact('vehicle'));
    }

    public function storeBooking(Request $request, string $id)
    {
        $request->validate([
            'start_date'     => 'required|date|after:now',
            'end_date'       => 'required|date|after:start_date',
            'pickup_address' => 'required|string',
            'notes'          => 'nullable|string|max:500',
        ]);

        try {
            $data = [
                'vehicle_id' => $id,
                'start_date' => $request->start_date,
                'end_date'   => $request->end_date,
                'pickup'     => ['address' => $request->pickup_address, 'lat' => 0, 'lng' => 0],
                'notes'      => $request->notes,
            ];
            $booking = $this->bookingService->createBooking($data, Auth::user());
            return redirect()->route('bookings.show', $booking->_id)->with('success', 'Pesanan berhasil dibuat!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}

// ─────────────────────────────────────────────────────────────