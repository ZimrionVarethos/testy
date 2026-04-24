<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    /**
     * Daftar booking.
     *
     * - Admin   : semua booking, bisa filter status
     * - Customer: booking milik sendiri
     * - Driver  : booking yang di-assign ke dia
     *
     * Query params:
     *   status   : pending | accepted | confirmed | ongoing | completed | cancelled
     *   per_page : integer (default 10)
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Booking::query();

        if ($user->role === 'admin') {
            // Admin lihat semua
        } elseif ($user->role === 'driver') {
            $query->where('driver_id', (string) $user->_id);
        } else {
            // Customer
            $query->where('user_id', (string) $user->_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage  = min((int) $request->get('per_page', 10), 50);
        $bookings = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $bookings->map(fn($b) => $this->bookingResource($b)),
            'meta'    => [
                'current_page' => $bookings->currentPage(),
                'last_page'    => $bookings->lastPage(),
                'per_page'     => $bookings->perPage(),
                'total'        => $bookings->total(),
            ],
        ]);
    }

    /**
     * Detail booking.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        $this->authorizeBookingAccess($request->user(), $booking);

        return response()->json([
            'success' => true,
            'data'    => $this->bookingResource($booking),
        ]);
    }

    /**
     * Buat booking baru (Customer).
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $user    = $request->user();
        $vehicle = Vehicle::findOrFail($request->vehicle_id);

        if ($vehicle->status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => 'Kendaraan tidak tersedia saat ini.',
            ], 422);
        }

        $startDate    = Carbon::parse($request->start_date);
        $endDate      = Carbon::parse($request->end_date);
        $durationDays = max(1, $startDate->diffInDays($endDate));
        $totalPrice   = $durationDays * $vehicle->price_per_day;

        $booking = Booking::create([
            'booking_code'  => 'BRN-' . strtoupper(Str::random(8)),
            'user_id'       => (string) $user->_id,
            'vehicle_id'    => (string) $vehicle->_id,
            'start_date'    => $startDate->toDateTimeString(),
            'end_date'      => $endDate->toDateTimeString(),
            'duration_days' => $durationDays,
            'total_price'   => $totalPrice,
            'status'        => 'pending',
            'pickup'        => [
                'address' => $request->pickup_address,
                'lat'     => $request->pickup_lat,
                'lng'     => $request->pickup_lng,
            ],
            'dropoff'       => $request->filled('dropoff_address') ? [
                'address' => $request->dropoff_address,
                'lat'     => $request->dropoff_lat,
                'lng'     => $request->dropoff_lng,
            ] : null,
            'notes'         => $request->notes,
            // Embed snapshot data
            'user'          => [
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            'vehicle'       => [
                'name'          => $vehicle->name,
                'plate_number'  => $vehicle->plate_number,
                'price_per_day' => $vehicle->price_per_day,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking berhasil dibuat. Menunggu konfirmasi driver.',
            'data'    => $this->bookingResource($booking),
        ], 201);
    }

    /**
     * Batalkan booking (Customer / Admin).
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        $user    = $request->user();

        $this->authorizeBookingAccess($user, $booking);

        if (in_array($booking->status, ['ongoing', 'completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Booking tidak dapat dibatalkan pada status ini.',
            ], 422);
        }

        $booking->update([
            'status'       => 'cancelled',
            'cancelled_at' => now()->toDateTimeString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking berhasil dibatalkan.',
            'data'    => $this->bookingResource($booking->fresh()),
        ]);
    }

    /**
     * Driver menerima booking (Driver).
     */
    public function accept(Request $request, string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        $driver  = $request->user();

        if ($booking->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Booking sudah tidak berstatus pending.',
            ], 422);
        }

        $booking->update([
            'status'    => 'accepted',
            'driver_id' => (string) $driver->_id,
            'driver'    => [
                'name'           => $driver->name,
                'phone'          => $driver->phone,
                'license_number' => $driver->driver_profile['license_number'] ?? '-',
            ],
            'accepted_at' => now()->toDateTimeString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking diterima. Menunggu konfirmasi admin.',
            'data'    => $this->bookingResource($booking->fresh()),
        ]);
    }

    /**
     * Admin mengkonfirmasi booking.
     */
    public function confirm(Request $request, string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status !== 'accepted') {
            return response()->json([
                'success' => false,
                'message' => 'Booking harus berstatus accepted untuk dikonfirmasi.',
            ], 422);
        }

        // Update status kendaraan
        Vehicle::where('_id', $booking->vehicle_id)
               ->update(['status' => 'rented']);

        $booking->update([
            'status'       => 'confirmed',
            'confirmed_at' => now()->toDateTimeString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking berhasil dikonfirmasi.',
            'data'    => $this->bookingResource($booking->fresh()),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function authorizeBookingAccess($user, Booking $booking): void
    {
        $userId      = (string) $user->_id;
        $bookingUser = (string) $booking->user_id;   // ← paksa string keduanya
    
        if ($user->role === 'admin') return;
    
        if ($user->role === 'driver'
            && (string) $booking->driver_id === $userId) return;
    
        if ($user->role === 'pengguna'
            && $bookingUser === $userId) return;
    
        abort(403, 'Anda tidak memiliki akses ke booking ini.');
    }

    private function bookingResource(Booking $b): array
    {
        return [
            'id'            => (string) $b->_id,
            'booking_code'  => $b->booking_code,
            'status'        => $b->status,
            'start_date'    => $b->start_date,
            'end_date'      => $b->end_date,
            'duration_days' => $b->duration_days,
            'total_price'   => $b->total_price,
            'notes'         => $b->notes,
            'pickup'        => $b->pickup,
            'dropoff'       => $b->dropoff,
            'user'          => $b->user,
            'vehicle'       => $b->vehicle,
            'driver'        => $b->driver,
            'accepted_at'   => $b->accepted_at,
            'confirmed_at'  => $b->confirmed_at,
            'cancelled_at'  => $b->cancelled_at,
            'created_at'    => $b->created_at?->toIso8601String(),
        ];
    }
}
