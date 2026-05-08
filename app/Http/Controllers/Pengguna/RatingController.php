<?php

namespace App\Http\Controllers\Pengguna;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BookingController as ApiBooking;
use App\Http\Traits\WebApiProxy;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    use WebApiProxy;

    public function store(Request $request, string $bookingId, ApiBooking $api)
    {
        $req = $this->makeApiRequest([], $request->only(['score', 'comment']));

        $result = $this->tryProxyApi(fn() => $api->storeRating($req, $bookingId));

        if (!($result['success'] ?? false)) {
            return back()->withErrors(['error' => $result['message'] ?? 'Gagal menyimpan rating.']);
        }

        return back()->with('success', 'Terima kasih atas penilaian Anda!');
    }
}
