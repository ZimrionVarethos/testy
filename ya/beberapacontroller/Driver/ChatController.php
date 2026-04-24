<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ChatMessage;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $driverId = (string) Auth::id();
        $filter   = $request->query('filter', 'active');

        // ── On-the-fly status update ──
        Booking::where('driver.driver_id', $driverId)
            ->whereIn('status', ['confirmed', 'ongoing'])
            ->get()
            ->each(function ($b) {
                if (Carbon::parse($b->end_date)->setTimezone('Asia/Jakarta')->isPast()) {
                    $b->update([
                        'status'       => 'completed',
                        'completed_at' => now('Asia/Jakarta'),
                    ]);
                }
            });

        // ── Query setelah update ──
        $query = Booking::where('driver.driver_id', $driverId);

        if ($filter === 'active') {
            $query->whereIn('status', ['confirmed', 'ongoing']);
        } else {
            $query->whereIn('status', ['completed', 'cancelled']);
        }

        $bookings = $query->orderBy('created_at', 'desc')->get();

        $unreadCounts = [];
        foreach ($bookings as $b) {
            $unreadCounts[(string) $b->_id] = ChatMessage::unreadCount((string) $b->_id, 'driver');
        }

        $ratings = [];
        foreach ($bookings->where('status', 'completed') as $b) {
            $ratings[(string) $b->_id] = Rating::forBooking((string) $b->_id);
        }

        return view('driver.chats.index', compact('bookings', 'filter', 'unreadCounts', 'ratings'));
    }
}