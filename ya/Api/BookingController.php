<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookingService) {}

    // ══════════════════════════════════════════════════════════════
    //  LIST
    // ══════════════════════════════════════════════════════════════

    /**
     * GET /api/v1/bookings
     *
     * - Admin   : semua booking, bisa filter status
     * - Pengguna: booking milik sendiri  (query: user.user_id)
     * - Driver  : booking yang di-assign  (query: driver.driver_id)
     *
     * Query params: status, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Booking::query();

        // ── Filter berdasarkan role ──────────────────────────────
        if ($user->role === 'admin') {
            // admin lihat semua — tidak ada filter tambahan
        } elseif ($user->role === 'driver') {
            // 🔧 FIX: pakai nested field driver.driver_id
            $query->where('driver.driver_id', (string) $user->_id);
        } else {
            // 🔧 FIX: pakai nested field user.user_id
            $query->where('user.user_id', (string) $user->_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Auto-cancel & auto-complete sebelum return data
        if ($user->role === 'pengguna') {
            $this->bookingService->autoCancelExpiredForUser((string) $user->_id);
            $this->bookingService->autoCompleteExpiredForUser((string) $user->_id);
        } elseif ($user->role === 'driver') {
            $this->bookingService->autoCompleteExpiredForDriver((string) $user->_id);
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

    // ══════════════════════════════════════════════════════════════
    //  SHOW
    // ══════════════════════════════════════════════════════════════

    public function show(Request $request, string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        $this->authorizeAccess($request->user(), $booking);

        return response()->json([
            'success' => true,
            'data'    => $this->bookingResource($booking),
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    //  CREATE (Pengguna)
    // ══════════════════════════════════════════════════════════════

    /**
     * POST /api/v1/bookings
     * Menggunakan BookingService — logic sama persis dengan web.
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        try {
            $data = array_merge($request->validated(), [
                'pickup' => [
                    'address' => $request->pickup_address,
                    'lat'     => $request->pickup_lat ?? 0,
                    'lng'     => $request->pickup_lng ?? 0,
                ],
            ]);

            $booking = $this->bookingService->createBooking($data, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Booking berhasil dibuat. Silakan selesaikan pembayaran.',
                'data'    => $this->bookingResource($booking),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  CANCEL (Pengguna / Admin)
    // ══════════════════════════════════════════════════════════════

    public function cancel(Request $request, string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        $this->authorizeAccess($request->user(), $booking);

        try {
            $reason  = $request->input('reason', 'Dibatalkan oleh pengguna.');
            $booking = $this->bookingService->cancelBooking($booking, $reason);

            return response()->json([
                'success' => true,
                'message' => 'Booking berhasil dibatalkan.',
                'data'    => $this->bookingResource($booking),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  DRIVER: ACCEPT (ambil dari pool — flow mobile)
    // ══════════════════════════════════════════════════════════════

    public function accept(Request $request, string $id): JsonResponse
    {
        $driver = $request->user();

        if ($driver->role !== 'driver') {
            return response()->json(['success' => false, 'message' => 'Hanya driver yang bisa menerima booking.'], 403);
        }

        try {
            $booking = $this->bookingService->driverAcceptBooking($id, $driver);

            return response()->json([
                'success' => true,
                'message' => 'Booking diterima. Menunggu konfirmasi admin.',
                'data'    => $this->bookingResource($booking),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  DRIVER: MARK PICKUP (confirmed → ongoing)
    // ══════════════════════════════════════════════════════════════

    public function pickup(Request $request, string $id): JsonResponse
    {
        $driver  = $request->user();
        $booking = Booking::findOrFail($id);

        if (Carbon::parse($booking->start_date)->subMinutes(30)->gt(Carbon::now())) {
            return response()->json([
                'success' => false,
                'message' => 'Tombol pickup hanya bisa diklik mulai 30 menit sebelum waktu penjemputan.',
            ], 422);
        }

        try {
            $booking = $this->bookingService->driverMarkPickup($booking, $driver);

            return response()->json([
                'success' => true,
                'message' => 'Status diperbarui menjadi Sedang Berjalan.',
                'data'    => $this->bookingResource($booking),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  ADMIN: CONFIRM (accepted → confirmed — flow mobile)
    // ══════════════════════════════════════════════════════════════

    public function confirm(Request $request, string $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        try {
            $booking = $this->bookingService->adminConfirmBooking($id);

            return response()->json([
                'success' => true,
                'message' => 'Booking berhasil dikonfirmasi.',
                'data'    => $this->bookingResource($booking),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  Payment status untuk booking tertentu
    // ══════════════════════════════════════════════════════════════

    public function paymentStatus(Request $request, string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        $this->authorizeAccess($request->user(), $booking);

        $payment = Payment::activeForBooking($id);

        return response()->json([
            'success' => true,
            'data'    => $payment ? [
                'id'           => (string) $payment->_id,
                'status'       => $payment->status,
                'amount'       => $payment->amount,
                'method'       => $payment->method,
                'snap_token'   => $payment->midtrans['snap_token'] ?? null,
                'expired_at'   => $payment->expired_at,
                'paid_at'      => $payment->paid_at,
            ] : null,
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    //  HELPERS
    // ══════════════════════════════════════════════════════════════

    /**
     * 🔧 FIX: gunakan nested field yang konsisten dengan web
     */
    private function authorizeAccess(mixed $user, Booking $booking): void
    {
        if ($user->role === 'admin') return;

        // 🔧 FIX: cek driver.driver_id bukan driver_id
        if ($user->role === 'driver' && ($booking->driver['driver_id'] ?? null) === (string) $user->_id) return;

        // 🔧 FIX: cek user.user_id bukan user_id
        if ($user->role === 'pengguna' && ($booking->user['user_id'] ?? null) === (string) $user->_id) return;

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
            // Embedded snapshots — sudah include user_id dan driver_id di dalamnya
            'user'          => $b->user,
            'vehicle'       => $b->vehicle,
            'driver'        => $b->driver,
            // Timestamps
            'accepted_at'   => $b->accepted_at,
            'confirmed_at'  => $b->confirmed_at,
            'started_at'    => $b->started_at ?? null,
            'completed_at'  => $b->completed_at ?? null,
            'cancelled_at'  => $b->cancelled_at ?? null,
            'cancel_reason' => $b->cancel_reason ?? null,
            'created_at'    => $b->created_at?->toIso8601String(),
        ];
    }
}
