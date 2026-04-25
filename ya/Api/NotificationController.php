<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * GET /api/v1/notifications
     */
    public function index(): JsonResponse
    {
        $notifications = Notification::where('user_id', (string) Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $notifications->map(fn($n) => [
                'id'         => (string) $n->_id,
                'title'      => $n->title,
                'message'    => $n->message,
                'type'       => $n->type,
                'is_read'    => (bool) $n->is_read,
                'related_id' => $n->related_id,
                'url'        => $n->url ?? null,
                'created_at' => $n->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'total'        => $notifications->total(),
                'unread_count' => Notification::where('user_id', (string) Auth::id())
                    ->where('is_read', false)->count(),
            ],
        ]);
    }

    /**
     * POST /api/v1/notifications/{id}/read
     */
    public function markRead(string $id): JsonResponse
    {
        $notif = Notification::where('user_id', (string) Auth::id())->findOrFail($id);
        $notif->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'Notifikasi ditandai dibaca.']);
    }

    /**
     * POST /api/v1/notifications/read-all
     */
    public function readAll(): JsonResponse
    {
        Notification::where('user_id', (string) Auth::id())
                    ->where('is_read', false)
                    ->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'Semua notifikasi ditandai dibaca.']);
    }
}
