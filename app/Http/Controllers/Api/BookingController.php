<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ChatMessage;
use App\Models\Payment;
use App\Models\Rating;
use App\Models\User;
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
        ['bookings' => $bookings, 'payments' => $payments] = $this->indexForWeb(
            $request->merge(['per_page' => min((int) $request->get('per_page', 10), 50)])
        );

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
        [$booking, $payment] = $this->queryShow($request, $id);

        return response()->json([
            'success' => true,
            'data'    => $this->bookingResource($booking, $payment),
        ]);
    }

    // ── Internal query methods (dipakai web controller via ForWeb) ────

    /** Untuk web: ambil booking list + payments, zero JSON overhead */
    public function indexForWeb(Request $request): array
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

        $perPage  = min((int) $request->get('per_page', 15), 50);
        $bookings = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $bookingIds = $bookings->pluck('_id')->map(fn($id) => (string) $id)->toArray();
        $payments   = Payment::whereIn('booking_id', $bookingIds)
            ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_PENDING])
            ->get()
            ->keyBy('booking_id');

        return compact('bookings', 'payments');
    }

    /** Untuk web: ambil satu booking + payment (akses sudah divalidasi) */
    public function showForWeb(Request $request, string $id): array
    {
        [$booking, $payment] = $this->queryShow($request, $id);
        return compact('booking', 'payment');
    }

    /** Untuk web: daftar booking chat (pengguna atau driver) */
    public function chatListForWeb(Request $request): array
    {
        $user   = $request->user();
        $filter = $request->get('filter', 'active');

        if ($user->role === 'pengguna') {
            $this->bookingService->autoCompleteExpiredForUser((string) $user->_id);
        } elseif ($user->role === 'driver') {
            $this->bookingService->autoCompleteExpiredForDriver((string) $user->_id);
        }

        if ($user->role === 'driver') {
            $query = Booking::where('driver.driver_id', (string) $user->_id);
        } else {
            $query = Booking::where('user.user_id', (string) $user->_id)
                ->whereNotNull('driver.driver_id');
        }

        if ($filter === 'active') {
            $query->whereIn('status', ['confirmed', 'ongoing']);
        } else {
            $query->whereIn('status', ['completed', 'cancelled']);
        }

        $bookings = $query->orderBy('created_at', 'desc')->get();

        $readerRole   = $user->role === 'driver' ? 'driver' : 'pengguna';
        $unreadCounts = [];
        $ratings      = [];

        foreach ($bookings as $b) {
            $unreadCounts[(string) $b->_id] = ChatMessage::unreadCount((string) $b->_id, $readerRole);
        }
        foreach ($bookings->where('status', 'completed') as $b) {
            $ratings[(string) $b->_id] = Rating::forBooking((string) $b->_id);
        }

        return compact('bookings', 'unreadCounts', 'ratings');
    }

    /** Untuk web: vehicle IDs yang sudah dipesan dalam range tanggal */
    public function bookedVehicleIdsForWeb(Carbon $start, Carbon $end): array
    {
        return Booking::whereIn('status', [
                Booking::STATUS_CONFIRMED,
                Booking::STATUS_ONGOING,
            ])
            ->where('start_date', '<', $end)
            ->where('end_date', '>', $start)
            ->where('end_date', '>', Carbon::now())
            ->whereNotNull('vehicle.vehicle_id')
            ->get()
            ->pluck('vehicle.vehicle_id')
            ->map(fn($id) => (string) $id)
            ->unique()->toArray();
    }

    /** Untuk web: cek konflik booking kendaraan dalam range tanggal */
    public function conflictCheckForWeb(string $vehicleId, Carbon $start, Carbon $end): bool
    {
        return Booking::whereIn('status', [
                Booking::STATUS_CONFIRMED,
                Booking::STATUS_ONGOING,
            ])
            ->where('vehicle.vehicle_id', $vehicleId)
            ->where('start_date', '<', $end)
            ->where('end_date', '>', $start)
            ->exists();
    }

    /** Untuk web: daftar booking driver (paginated) */
    public function driverIndexForWeb(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Booking::where('driver.driver_id', (string) $request->user()->_id)
            ->orderBy('start_date', 'desc')
            ->paginate(10);
    }

    /** Untuk web: detail booking driver + flag canPickup */
    public function driverShowForWeb(Request $request, string $id): array
    {
        $booking = Booking::findOrFail($id);

        abort_if(
            ($booking->driver['driver_id'] ?? null) !== (string) $request->user()->_id,
            403,
            'Anda tidak punya akses ke pesanan ini.'
        );

        $canPickup = $booking->status === Booking::STATUS_CONFIRMED
            && Carbon::parse($booking->start_date)->subMinutes(30)->lte(Carbon::now());

        return compact('booking', 'canPickup');
    }

    /** Untuk web: daftar booking admin dengan filter (paginated) */
    public function adminIndexForWeb(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Booking::orderBy('created_at', 'desc');

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('search')) $query->where('booking_code', 'like', '%' . $request->search . '%');

        return $query->paginate(15);
    }

    private function queryShow(Request $request, string $id): array
    {
        $booking = Booking::findOrFail($id);
        $this->authorizeAccess($request->user(), $booking);
        $payment = Payment::activeForBooking($id);
        return [$booking, $payment];
    }

    /**
     * POST /api/v1/bookings
     * User buat booking baru → status pending, lanjut ke pembayaran
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id'      => 'required|string|exists:vehicles,_id',
            'start_date'      => 'required|date|after_or_equal:today',
            'end_date'        => 'required|date|after:start_date',
            'pickup_address'  => 'required|string|max:255',
            'pickup_lat'      => 'nullable|numeric',
            'pickup_lng'      => 'nullable|numeric',
            'dropoff_address' => 'nullable|string|max:255',
            'dropoff_lat'     => 'nullable|numeric',
            'dropoff_lng'     => 'nullable|numeric',
            'notes'           => 'nullable|string|max:500',
        ]);

        try {
            $data = array_merge($validated, [
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

    /**
     * GET /api/v1/bookings/{id}/available-drivers  (admin)
     */
    public function availableDrivers(Request $request, string $id): JsonResponse
    {
        $booking   = Booking::findOrFail($id);
        $startDate = Carbon::parse($booking->start_date);
        $endDate   = Carbon::parse($booking->end_date);

        $busyIds = Booking::busyDriverIdsInRange($startDate, $endDate);

        $drivers = User::where('role', 'driver')
            ->where('is_active', true)
            ->get()
            ->filter(fn($d) => !in_array((string) $d->_id, $busyIds))
            ->map(fn($d) => [
                'id'               => (string) $d->_id,
                'name'             => $d->name,
                'phone'            => $d->phone,
                'avatar'           => $d->avatar,
                'active_schedules' => Booking::activeScheduleForDriver((string) $d->_id),
            ])
            ->values();

        return response()->json(['success' => true, 'data' => $drivers]);
    }

    /**
     * POST /api/v1/bookings/{id}/assign-driver  (admin)
     */
    public function assignDriver(Request $request, string $id): JsonResponse
    {
        $request->validate(['driver_id' => 'required|string']);

        $booking = Booking::findOrFail($id);
        $driver  = User::where('role', 'driver')
            ->where('is_active', true)
            ->findOrFail($request->driver_id);

        try {
            $this->bookingService->adminAssignDriver($booking, $driver);

            return response()->json([
                'success' => true,
                'message' => "Driver {$driver->name} berhasil di-assign.",
                'data'    => $this->bookingResource($booking->refresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /api/v1/chats — daftar booking chat aktif milik user
     */
    public function chatList(Request $request): JsonResponse
    {
        $user   = $request->user();
        $filter = $request->get('filter', 'active');

        if ($user->role === 'driver') {
            $query = Booking::where('driver.driver_id', (string) $user->_id)
                ->whereNotNull('driver.driver_id');
        } else {
            $query = Booking::where('user.user_id', (string) $user->_id)
                ->whereNotNull('driver.driver_id');
        }

        if ($filter === 'active') {
            $query->whereIn('status', ['confirmed', 'ongoing']);
        } else {
            $query->whereIn('status', ['completed', 'cancelled']);
        }

        $bookings = $query->orderBy('created_at', 'desc')->get();

        $readerRole = $user->role === 'driver' ? 'driver' : 'pengguna';

        $data = $bookings->map(function ($b) use ($readerRole) {
            $lastMsg   = ChatMessage::where('booking_id', (string) $b->_id)
                ->orderBy('created_at', 'desc')->first();
            $unread    = ChatMessage::unreadCount((string) $b->_id, $readerRole);
            $rating    = $b->status === 'completed' ? Rating::forBooking((string) $b->_id) : null;

            return [
                'booking_id'   => (string) $b->_id,
                'booking_code' => $b->booking_code,
                'status'       => $b->status,
                'start_date'   => $b->start_date?->toIso8601String(),
                'vehicle_name' => $b->vehicle['name'] ?? '-',
                'partner_name' => $readerRole === 'driver'
                    ? ($b->user['name'] ?? '-')
                    : ($b->driver['name'] ?? '-'),
                'last_message' => $lastMsg?->message,
                'last_message_at' => $lastMsg?->created_at?->toIso8601String(),
                'unread_count' => $unread,
                'has_rating'   => $rating !== null,
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * POST /api/v1/bookings/{id}/rating
     */
    public function storeRating(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'score'   => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $booking = Booking::findOrFail($id);
        $user    = $request->user();

        if (($booking->user['user_id'] ?? null) !== (string) $user->_id) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        if ($booking->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Rating hanya bisa diberikan untuk pesanan yang sudah selesai.',
            ], 422);
        }

        if (Rating::existsForBooking($id)) {
            return response()->json(['success' => false, 'message' => 'Pesanan ini sudah dirating.'], 422);
        }

        $driverId = $booking->driver['driver_id'] ?? null;
        if (!$driverId) {
            return response()->json(['success' => false, 'message' => 'Data driver tidak ditemukan.'], 422);
        }

        Rating::create([
            'booking_id' => $id,
            'driver_id'  => (string) $driverId,
            'user_id'    => (string) $user->_id,
            'score'      => (int) $request->score,
            'comment'    => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih atas penilaian Anda!',
        ], 201);
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