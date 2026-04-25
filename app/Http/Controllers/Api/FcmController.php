<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FcmController extends Controller
{
    /**
     * POST /api/v1/fcm/token
     * Simpan FCM token device setelah login atau token refresh.
     * Android memanggil ini saat:
     * - User pertama login
     * - FirebaseMessaging.getInstance().getToken() refresh
     */
    public function storeToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $request->user()->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'FCM token tersimpan.',
        ]);
    }

    /**
     * DELETE /api/v1/fcm/token
     * Hapus FCM token saat logout atau user revoke permission notifikasi.
     */
    public function deleteToken(Request $request): JsonResponse
    {
        $request->user()->update([
            'fcm_token' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'FCM token dihapus.',
        ]);
    }
}