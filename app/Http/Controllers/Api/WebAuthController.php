<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class WebAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email belum diverifikasi. Cek inbox email Anda terlebih dahulu.',
            ], 403);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda telah dinonaktifkan. Hubungi admin.',
            ], 403);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        PersonalAccessToken::where('tokenable_id', (string) $user->getKey())
                           ->where('tokenable_type', User::class)
                           ->where('name', 'web-spa')
                           ->delete();
        $token = $user->createToken('web-spa')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login web berhasil. Session aktif.',
            'data'    => [
                'user'  => $this->userResource($user),
                'token' => $token,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->userResource($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        if ($user = $request->user()) {
            $request->user()->currentAccessToken()?->delete();
            PersonalAccessToken::where('tokenable_id', (string) $user->getKey())
                               ->where('tokenable_type', User::class)
                               ->where('name', 'web-spa')
                               ->delete();
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logout web berhasil. Session dihapus.',
        ]);
    }

    private function userResource(User $user): array
    {
        return [
            'id'         => (string) $user->_id,
            'name'       => $user->name,
            'email'      => $user->email,
            'phone'      => $user->phone,
            'role'       => $user->role,
            'is_active'  => $user->is_active,
            'avatar'     => $user->avatar,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}
