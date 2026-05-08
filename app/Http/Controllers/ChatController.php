<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\ChatController as ApiChat;
use App\Http\Traits\WebApiProxy;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    use WebApiProxy;

    /** GET /bookings/{id}/messages — polling endpoint untuk pengguna */
    public function index(string $id, ApiChat $api)
    {
        $req    = $this->makeApiRequest();
        $result = $this->tryProxyApi(fn() => $api->index($req, $id));

        $messages = collect($result['data'] ?? [])->map(fn($m) => $this->formatMessage($m));

        return response()->json(['messages' => $messages, 'unread' => 0]);
    }

    /** POST /bookings/{id}/messages — kirim pesan dari pengguna */
    public function store(Request $request, string $id, ApiChat $api)
    {
        $req    = $this->makeApiRequest([], ['message' => $request->message]);
        $result = $this->tryProxyApi(fn() => $api->store($req, $id));

        if (!($result['success'] ?? false)) {
            return response()->json(['error' => $result['message'] ?? 'Gagal mengirim pesan.'], 422);
        }

        return response()->json(['message' => $this->formatMessage($result['data'])]);
    }

    /** GET /driver/bookings/{id}/messages — polling endpoint untuk driver */
    public function driverIndex(string $id, ApiChat $api)
    {
        $req    = $this->makeApiRequest();
        $result = $this->tryProxyApi(fn() => $api->index($req, $id));

        $messages = collect($result['data'] ?? [])->map(fn($m) => $this->formatMessage($m));

        return response()->json(['messages' => $messages, 'unread' => 0]);
    }

    /** POST /driver/bookings/{id}/messages — kirim pesan dari driver */
    public function driverStore(Request $request, string $id, ApiChat $api)
    {
        $req    = $this->makeApiRequest([], ['message' => $request->message]);
        $result = $this->tryProxyApi(fn() => $api->store($req, $id));

        if (!($result['success'] ?? false)) {
            return response()->json(['error' => $result['message'] ?? 'Gagal mengirim pesan.'], 422);
        }

        return response()->json(['message' => $this->formatMessage($result['data'])]);
    }

    // ── Helper ────────────────────────────────────────────────

    private function formatMessage(array $m): array
    {
        $createdAt = \Carbon\Carbon::parse($m['created_at']);
        return array_merge($m, [
            'time' => $createdAt->format('H:i'),
            'date' => $createdAt->format('d M Y'),
        ]);
    }
}
