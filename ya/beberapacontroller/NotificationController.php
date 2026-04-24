<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all'); // all | unread | read

        $query = Notification::where('user_id', (string) Auth::id())
                             ->orderBy('created_at', 'desc');

        if ($filter === 'unread') {
            $query->where('is_read', false);
        } elseif ($filter === 'read') {
            $query->where('is_read', true);
        }

        $notifications = $query->paginate(20)->withQueryString();

        $counts = [
            'all'    => Notification::where('user_id', (string) Auth::id())->count(),
            'unread' => Notification::where('user_id', (string) Auth::id())->where('is_read', false)->count(),
            'read'   => Notification::where('user_id', (string) Auth::id())->where('is_read', true)->count(),
        ];

        return view('notifications.index', compact('notifications', 'filter', 'counts'));
    }

    public function markRead(string $id)
    {
        Notification::where('_id', $id)
                    ->where('user_id', (string) Auth::id())
                    ->update(['is_read' => true]);
        return back();
    }

    public function markAllRead()
    {
        Notification::where('user_id', (string) Auth::id())->update(['is_read' => true]);
        return back()->with('success', 'Semua notifikasi ditandai dibaca.');
    }

    /** Hapus satu notifikasi */
    public function destroy(string $id)
    {
        Notification::where('_id', $id)
                    ->where('user_id', (string) Auth::id())
                    ->delete();
        return back()->with('success', 'Notifikasi dihapus.');
    }

    /** Hapus semua notifikasi milik user */
    public function destroyAll()
    {
        Notification::where('user_id', (string) Auth::id())->delete();
        return back()->with('success', 'Semua notifikasi dihapus.');
    }

    /** Hapus notifikasi yang dipilih (bulk) */
    public function destroySelected(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'string']);

        Notification::whereIn('_id', $request->ids)
                    ->where('user_id', (string) Auth::id())
                    ->delete();

        return back()->with('success', count($request->ids) . ' notifikasi dihapus.');
    }
}