<?php

// ─────────────────────────────────────────────────────────────

namespace App\Http\Controllers;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::where('user_id', (string) Auth::id())
                                     ->orderBy('created_at', 'desc')->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    public function markRead(string $id)
    {
        Notification::where('_id', $id)->update(['is_read' => true]);
        return back();
    }

    public function markAllRead()
    {
        Notification::where('user_id', (string) Auth::id())->update(['is_read' => true]);
        return back()->with('success', 'Semua notifikasi ditandai dibaca.');
    }
}