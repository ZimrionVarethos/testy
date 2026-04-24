<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::where('role', 'customer')
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

    public function show(string $id): JsonResponse
    {
        $user = User::where('role', 'customer')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $this->userResource($user),
        ]);
    }

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

    private function userResource(User $u): array
    {
        return [
            'id'         => (string) $u->_id,
            'name'       => $u->name,
            'email'      => $u->email,
            'phone'      => $u->phone,
            'is_active'  => $u->is_active,
            'created_at' => $u->created_at?->toIso8601String(),
        ];
    }
}
