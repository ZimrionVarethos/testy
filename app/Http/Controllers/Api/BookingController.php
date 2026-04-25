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

    /**
     * GET /api/v1/bookings
     * - pengguna : booking milik sendiri
     * - driver   : booking yang di-assign ke dia
     * - admin    : semua (tapi admin tidak bisa login mobile — fallback saja)
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Booking::query();

        if ($user->role === 'driver') {
            $query->where('driver.driver_id', (string) $user->_id);
        } elseif ($user->role === 'pengguna') {
            $query->where('user.user_id', (string) $user->_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage  = min((int) $request->get('per_page', 10), 50);
        $bookings = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Preload payments untuk semua booking di halaman ini
        $bookingIds = $bookings->pluck('_id')->map(fn($id) => (string) $id)->toArray();
        $payments   = Payment::whereIn('booking_id', $bookingIds)
            ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_PENDING])
            ->get()
            ->keyBy('booking_id');

        return response()->json([
            'success' => true,
            'data'    => $bookings->map(fn($b) => $this->bookingResource($b, $payments[(string) $b->_id] ?? null)),
            'meta'    => [
                'current_page' => $bookings->currentPage(),
                'last_page'    => $bookings->lastPage(),
                'per_page'     => $bookings->perPage(),
                'total'        => $bookings->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/bookings/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        $this->authorizeAccess($request->user(), $booking);

        $payment = Payment::activeForBooking($id);

        return response()->json([
            'success' => true,
            'data'    => $this->bookingResource($booking, $payment),
        ]);
    }

    /**
     * POST /api/v1/bookings
     * User buat booking baru → status pending, lanjut ke pembayaran
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

    /**
     * POST /api/v1/bookings/{id}/cancel
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        $this->authorizeAccess($request->user(), $booking);

        try {
            $this->bookingService->cancelBooking(
                $booking,
                $request->input('reason', 'Dibatalkan oleh pengguna.')
            );

            return response()->json([
                'success' => true,
                'message' => 'Booking berhasil dibatalkan.',
                'data'    => $this->bookingResource($booking->refresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/v1/bookings/{id}/pickup
     * Driver klik "Sudah Jemput" → confirmed → ongoing
     *
     * MENGGANTIKAN: accept() dan confirm() yang dihapus
     * Alur baru: admin assign driver via web, driver konfirmasi pickup via mobile
     */
    public function pickup(Request $request, string $id): JsonResponse
    {
        $driver  = $request->user();
        $booking = Booking::findOrFail($id);

        if ($driver->role !== 'driver') {
            return response()->json(['success' => false, 'message' => 'Hanya driver yang bisa konfirmasi penjemputan.'], 403);
        }

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

    /**
     * GET /api/v1/bookings/{id}/payment-status
     * Cek status payment untuk booking tertentu (dipakai mobile setelah Snap selesai)
     */
    public function paymentStatus(Request $request, string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        $this->authorizeAccess($request->user(), $booking);

        $payment = Payment::activeForBooking($id);

        return response()->json([
            'success' => true,
            'data'    => $payment ? [
                'id'         => (string) $payment->_id,
                'status'     => $payment->status,
                'amount'     => $payment->amount,
                'method'     => $payment->method,
                'snap_token' => $payment->midtrans['snap_token'] ?? null,
                'expired_at' => $payment->expired_at,
                'paid_at'    => $payment->paid_at,
            ] : null,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function authorizeAccess(mixed $user, Booking $booking): void
    {
        if ($user->role === 'admin') return;

        if ($user->role === 'driver'
            && ($booking->driver['driver_id'] ?? null) === (string) $user->_id) return;

        if ($user->role === 'pengguna'
            && ($booking->user['user_id'] ?? null) === (string) $user->_id) return;

        abort(403, 'Anda tidak memiliki akses ke booking ini.');
    }

    private function bookingResource(Booking $b, ?Payment $payment = null): array
    {
        // Tentukan status label yang ramah untuk mobile
        $sudahBayar = $payment && $payment->isPaid();

        $statusLabel = match(true) {
            $b->status === 'pending' && $sudahBayar  => 'Menunggu Admin',
            $b->status === 'pending' && !$sudahBayar => 'Belum Dibayar',
            $b->status === 'confirmed'               => 'Dikonfirmasi',
            $b->status === 'ongoing'                 => 'Sedang Berjalan',
            $b->status === 'completed'               => 'Selesai',
            $b->status === 'cancelled'               => 'Dibatalkan',
            default                                  => ucfirst($b->status),
        };

        return [
            'id'            => (string) $b->_id,
            'booking_code'  => $b->booking_code,
            'status'        => $b->status,
            'status_label'  => $statusLabel,       // ← label Indonesia untuk UI mobile
            'start_date'    => $b->start_date?->toIso8601String(),
            'end_date'      => $b->end_date?->toIso8601String(),
            'duration_days' => $b->duration_days,
            'total_price'   => $b->total_price,
            'notes'         => $b->notes,
            'pickup'        => $b->pickup,
            'dropoff'       => $b->dropoff,
            'user'          => $b->user,
            'vehicle'       => $b->vehicle,
            'driver'        => $b->driver,
            // Payment info — dipakai mobile untuk tahu apakah perlu redirect ke Snap
            'payment'       => $payment ? [
                'status'     => $payment->status,
                'snap_token' => $payment->midtrans['snap_token'] ?? null,
                'expired_at' => $payment->expired_at?->toIso8601String(),
                'paid_at'    => $payment->paid_at?->toIso8601String(),
            ] : null,
            // Timestamps
            'confirmed_at'  => $b->confirmed_at?->toIso8601String(),
            'started_at'    => $b->started_at?->toIso8601String(),
            'completed_at'  => $b->completed_at?->toIso8601String(),
            'cancelled_at'  => $b->cancelled_at?->toIso8601String(),
            'cancel_reason' => $b->cancel_reason,
            'created_at'    => $b->created_at?->toIso8601String(),
        ];
    }
}