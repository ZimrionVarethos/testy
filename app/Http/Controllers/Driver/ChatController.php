<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BookingController as ApiBooking;
use App\Http\Traits\WebApiProxy;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    use WebApiProxy;

    public function index(Request $request, ApiBooking $api)
    {
        $filter = $request->query('filter', 'active');
        $req    = $this->makeApiRequest(['filter' => $filter]);

        ['bookings' => $bookings, 'unreadCounts' => $unreadCounts, 'ratings' => $ratings]
            = $api->chatListForWeb($req);

        return view('driver.chats.index', compact('bookings', 'filter', 'unreadCounts', 'ratings'));
    }
}
