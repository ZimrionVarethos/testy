<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterRequest;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'password'  => Hash::make($request->password),
            'role'      => 'pengguna',
            'is_active' => true,
            'email_verified_at' => null,
        ]);

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            Log::warning('Registration verification email could not be sent.', [
                'user_id' => (string) $user->getKey(),
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil. Cek email Anda untuk verifikasi akun.',
            'data'    => [
                'user'  => $this->userResource($user),
            ],
        ], 201);
    }

    public function verifyEmail(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        abort_unless(hash_equals($hash, sha1($user->getEmailForVerification())), 403);

        if (!$user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        $frontendUrl = rtrim(env('FRONTEND_URL', config('app.url')), '/');

        return redirect()->away($frontendUrl . '/login?verified=1');
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

        // ── BLOKIR ADMIN LOGIN DI MOBILE ─────────────────────────
        // Admin hanya bisa login lewat web dashboard
        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email belum diverifikasi. Cek inbox email Anda terlebih dahulu.',
            ], 403);
        }

        if ($user->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akun admin tidak dapat login melalui aplikasi mobile. Gunakan web dashboard.',
            ], 403);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda telah dinonaktifkan. Hubungi admin.',
            ], 403);
        }

        // Hapus token lama (single-session per device)
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

    public function logout(Request $request): JsonResponse
    {
        // Hapus FCM token saat logout agar tidak dapat notif
        $request->user()->update(['fcm_token' => null]);
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    public function deleteAccountForWeb(Request $request): void
    {
        $request->user()->delete();
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->userResource($request->user()),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'name'                  => 'sometimes|string|max:100',
            'email'                 => 'sometimes|email|unique:users,email,' . $user->_id . ',_id',
            'phone'                 => 'sometimes|string|max:20',
            'password'              => 'sometimes|string|min:8|confirmed',
            'password_confirmation' => 'sometimes|string',
        ]);

        $data = $request->only(['name', 'email', 'phone']);

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

    public function uploadAvatar(Request $request, CloudinaryService $cloudinary): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->avatar_public_id) {
            $cloudinary->delete($user->avatar_public_id);
        }

        $result = $cloudinary->upload($request->file('avatar'), 'users/' . (string) $user->_id, [
            'transformation' => [['width' => 200, 'height' => 200, 'crop' => 'fill', 'gravity' => 'face', 'quality' => 'auto', 'fetch_format' => 'auto']],
        ]);

        $user->avatar           = $result['url'];
        $user->avatar_public_id = $result['public_id'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil diperbarui.',
            'data'    => $this->userResource($user),
        ]);
    }

    public function deleteAvatar(Request $request, CloudinaryService $cloudinary): JsonResponse
    {
        $user = $request->user();

        if ($user->avatar_public_id) {
            $cloudinary->delete($user->avatar_public_id);
        }

        $user->avatar = null;
        $user->avatar_public_id = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil dihapus.',
            'data'    => $this->userResource($user),
        ]);
    }

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

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return response()->json([
            'success' => $status === Password::PASSWORD_RESET,
            'message' => $status === Password::PASSWORD_RESET
                ? 'Password berhasil direset. Silakan login dengan password baru.'
                : 'Token reset tidak valid atau sudah kedaluwarsa.',
        ], $status === Password::PASSWORD_RESET ? 200 : 422);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak ditemukan.',
            ], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Email sudah terverifikasi. Silakan login.',
            ]);
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            Log::warning('Verification email resend failed.', [
                'user_id' => (string) $user->getKey(),
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim email verifikasi. Coba lagi nanti.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Link verifikasi sudah dikirim ulang. Cek inbox atau folder spam.',
        ]);
    }

    private function userResource(User $user): array
    {
        $data = [
            'id'         => (string) $user->_id,
            'name'       => $user->name,
            'email'      => $user->email,
            'phone'      => $user->phone,
            'role'       => $user->role,
            'is_active'  => $user->is_active,
            'avatar'     => $user->avatar,
            'created_at' => $user->created_at?->toIso8601String(),
        ];

        if ($user->role === 'driver' && $user->driver_profile) {
            $data['license_number'] = $user->driver_profile['license_number'] ?? null;
            $data['license_expiry'] = $user->driver_profile['license_expiry'] ?? null;
            $data['rating'] = $user->driver_profile['rating'] ?? null;
        }

        return $data;
    }
}
