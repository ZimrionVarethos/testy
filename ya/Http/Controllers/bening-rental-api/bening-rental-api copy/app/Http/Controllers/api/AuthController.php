<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use App\Models\PersonalAccessToken;

class AuthController extends Controller
{
    /**
     * Register pengguna baru.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'password'  => Hash::make($request->password),
            'role'      => 'customer',
            'is_active' => true,
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil.',
            'data'    => [
                'user'  => $this->userResource($user),
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * Login dan dapatkan token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda telah dinonaktifkan. Hubungi admin.',
            ], 403);
        }

        // Hapus token lama (opsional: single-session)
        PersonalAccessToken::where('tokenable_id', (string) $user->getKey())
                           ->where('tokenable_type', User::class)
                           ->delete();

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data'    => [
                'user'  => $this->userResource($user),
                'token' => $token,
            ],
        ]);
    }

    /**
     * Logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    /**
     * Profil pengguna saat ini.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->userResource($request->user()),
        ]);
    }

    /**
     * Update profil.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->only(['name', 'phone']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data'    => $this->userResource($user->fresh()),
        ]);
    }

    /**
     * Forgot password.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return response()->json([
            'success' => $status === Password::RESET_LINK_SENT,
            'message' => $status === Password::RESET_LINK_SENT
                ? 'Link reset password telah dikirim ke email Anda.'
                : 'Email tidak ditemukan.',
        ], $status === Password::RESET_LINK_SENT ? 200 : 404);
    }

    // ── Helper ───────────────────────────────────────────────────────────

    private function userResource(User $user): array
    {
        return [
            'id'         => (string) $user->_id,
            'name'       => $user->name,
            'email'      => $user->email,
            'phone'      => $user->phone,
            'role'       => $user->role,
            'is_active'  => $user->is_active,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}
