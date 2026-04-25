<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * GET /api/v1/users — daftar pengguna (Admin only)
     */
    public function index(Request $request): JsonResponse
    {
        // 🔧 FIX: role adalah 'pengguna', bukan 'customer'
        $users = User::where('role', 'pengguna')
            ->orderBy('created_at', 'desc')
            ->paginate((int) $request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $users->map(fn($u) => $this->userResource($u)),
            'meta'    => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/users/{id} — detail pengguna beserta riwayat booking
     */
    public function show(string $id): JsonResponse
    {
        // 🔧 FIX: role adalah 'pengguna'
        $user = User::where('role', 'pengguna')->findOrFail($id);

        // 🔧 FIX: pakai nested field user.user_id (konsisten dengan web Admin/UserController)
        $bookings = Booking::where('user.user_id', $id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => array_merge($this->userResource($user), [
                'recent_bookings' => $bookings->map(fn($b) => [
                    'id'           => (string) $b->_id,
                    'booking_code' => $b->booking_code,
                    'status'       => $b->status,
                    'total_price'  => $b->total_price,
                    'start_date'   => $b->start_date,
                    'end_date'     => $b->end_date,
                    'vehicle_name' => $b->vehicle['name'] ?? '-',
                ]),
            ]),
        ]);
    }

    /**
     * POST /api/v1/users/{id}/toggle — aktifkan / nonaktifkan (Admin)
     */
    public function toggle(string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => ! $user->is_active]);

        return response()->json([
            'success' => true,
            'message' => $user->is_active ? 'Pengguna diaktifkan.' : 'Pengguna dinonaktifkan.',
            'data'    => $this->userResource($user->fresh()),
        ]);
    }

    // ── Helper ────────────────────────────────────────────────────

    private function userResource(User $u): array
    {
        return [
            'id'         => (string) $u->_id,
            'name'       => $u->name,
            'email'      => $u->email,
            'phone'      => $u->phone,
            'role'       => $u->role,
            'is_active'  => $u->is_active,
            'created_at' => $u->created_at?->toIso8601String(),
        ];
    }
}
