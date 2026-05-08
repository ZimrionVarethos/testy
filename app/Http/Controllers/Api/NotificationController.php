<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    // ── ForWeb (dipakai web controller langsung) ─────────────────

    /** Untuk web: notifikasi ter-filter + counts */
    public function indexForWeb(Request $request): array
    {
        $userId = (string) Auth::id();
        $filter = $request->get('filter', 'all');
        $query  = Notification::where('user_id', $userId)->orderBy('created_at', 'desc');

        if ($filter === 'unread') $query->where('is_read', false);
        elseif ($filter === 'read') $query->where('is_read', true);

        $notifications = $query->paginate(20)->withQueryString();

        $counts = [
            'all'    => Notification::where('user_id', $userId)->count(),
            'unread' => Notification::where('user_id', $userId)->where('is_read', false)->count(),
            'read'   => Notification::where('user_id', $userId)->where('is_read', true)->count(),
        ];

        return compact('notifications', 'counts');
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

    /**
     * DELETE /api/v1/notifications/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        Notification::where('_id', $id)
                    ->where('user_id', (string) Auth::id())
                    ->delete();

        return response()->json(['success' => true, 'message' => 'Notifikasi dihapus.']);
    }

    /**
     * DELETE /api/v1/notifications
     */
    public function destroyAll(): JsonResponse
    {
        Notification::where('user_id', (string) Auth::id())->delete();

        return response()->json(['success' => true, 'message' => 'Semua notifikasi dihapus.']);
    }

    /**
     * POST /api/v1/notifications/delete-selected
     */
    public function destroySelected(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'string']);

        Notification::whereIn('_id', $request->ids)
                    ->where('user_id', (string) Auth::id())
                    ->delete();

        return response()->json([
            'success' => true,
            'message' => count($request->ids) . ' notifikasi dihapus.',
        ]);
    }
}
