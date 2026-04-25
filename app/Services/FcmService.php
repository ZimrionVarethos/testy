<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class FcmService
{
    private ?string $accessToken  = null;
    private ?string $projectId    = null;

    // ── Setup ─────────────────────────────────────────────────────

    private function getProjectId(): string
    {
        if ($this->projectId) return $this->projectId;

        $this->projectId = config('services.firebase.project_id');

        if (empty($this->projectId)) {
            throw new \Exception('FIREBASE_PROJECT_ID belum di-set di .env');
        }

        return $this->projectId;
    }

    /**
     * Ambil OAuth2 access token dari Service Account JSON.
     * JSON disimpan sebagai base64 di env FIREBASE_CREDENTIALS_BASE64.
     *
     * Token di-cache selama request (tidak persist antar request — fine untuk Laravel).
     */
    private function getAccessToken(): string
    {
        if ($this->accessToken) return $this->accessToken;

        $base64 = env('FIREBASE_CREDENTIALS_BASE64');

        if (empty($base64)) {
            throw new \Exception('FIREBASE_CREDENTIALS_BASE64 belum di-set di .env');
        }

        $json        = base64_decode($base64);
        $credentials = json_decode($json, true);

        if (!$credentials || !isset($credentials['private_key'])) {
            throw new \Exception('FIREBASE_CREDENTIALS_BASE64 tidak valid — cek format JSON.');
        }

        // Buat JWT untuk minta access token dari Google OAuth2
        $now    = time();
        $expiry = $now + 3600;

        $header  = base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = base64url_encode(json_encode([
            'iss'   => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $expiry,
        ]));

        $signingInput = $header . '.' . $payload;

        // Sign dengan private key dari service account
        $privateKey = openssl_pkey_get_private($credentials['private_key']);
        if (!$privateKey) {
            throw new \Exception('Gagal load private key dari service account JSON.');
        }

        openssl_sign($signingInput, $signature, $privateKey, 'SHA256');
        $jwt = $signingInput . '.' . base64url_encode($signature);

        // Tukar JWT dengan access token
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Gagal dapat access token dari Google: ' . $response->body());
        }

        $this->accessToken = $response->json('access_token');
        return $this->accessToken;
    }

    // ── Public API ────────────────────────────────────────────────

    /**
     * Kirim push notification ke satu user berdasarkan user ID.
     */
    public function sendToUser(
        string $userId,
        string $title,
        string $body,
        array  $data = []
    ): bool {
        $user = User::find($userId);

        if (!$user || empty($user->fcm_token)) {
            return false;
        }

        return $this->send($user->fcm_token, $title, $body, $data);
    }

    /**
     * Kirim ke banyak user sekaligus.
     */
    public function sendToMany(
        array  $userIds,
        string $title,
        string $body,
        array  $data = []
    ): void {
        $tokens = User::whereIn('_id', $userIds)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token', '_id')  // [user_id => token]
            ->toArray();

        foreach ($tokens as $token) {
            $this->send($token, $title, $body, $data);
        }
    }

    // ── Private ───────────────────────────────────────────────────

    private function send(
        string $token,
        string $title,
        string $body,
        array  $data = []
    ): bool {
        try {
            $projectId   = $this->getProjectId();
            $accessToken = $this->getAccessToken();

            // Semua value di data harus string (FCM requirement)
            $dataPayload = array_map('strval', array_merge($data, [
                'title' => $title,
                'body'  => $body,
            ]));

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type'  => 'application/json',
            ])->post(
                "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                [
                    'message' => [
                        'token'        => $token,
                        'notification' => [
                            'title' => $title,
                            'body'  => $body,
                        ],
                        'data'         => $dataPayload,
                        'android'      => [
                            'priority'     => 'high',
                            'notification' => ['sound' => 'default'],
                        ],
                    ],
                ]
            );

            if ($response->successful()) {
                return true;
            }

            $error     = $response->json('error.details.0.errorCode') ?? $response->json('error.message');
            $errorCode = $response->json('error.code');

            // Token tidak valid — hapus dari DB
            if (in_array($error, ['UNREGISTERED', 'INVALID_ARGUMENT']) || $errorCode === 404) {
                User::where('fcm_token', $token)->update(['fcm_token' => null]);
                Log::info('FCM: token invalid dihapus.', ['prefix' => substr($token, 0, 20)]);
            } else {
                Log::warning('FCM send failed', ['error' => $error, 'code' => $errorCode]);
            }

            return false;

        } catch (\Throwable $e) {
            Log::error('FCM exception', ['message' => $e->getMessage()]);
            return false;
        }
    }
}

// ── Helper function ───────────────────────────────────────────────
// Base64 URL encode tanpa padding (standar JWT)
if (!function_exists('base64url_encode')) {
    function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}