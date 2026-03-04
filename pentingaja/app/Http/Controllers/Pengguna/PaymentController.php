<?php



namespace App\Http\Controllers\Pengguna;
use App\Http\Controllers\Controller;
use App\Models\Booking; use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function index()
    {
        $userId   = (string) Auth::id();
        $bookings = Booking::where('user.user_id', $userId)
                           ->whereIn('status', ['confirmed', 'ongoing', 'completed'])
                           ->orderBy('created_at', 'desc')->paginate(10);
        return view('pengguna.payments.index', compact('bookings'));
    }

    public function show(string $id)
    {
        $booking = Booking::findOrFail($id);
        if ($booking->user['user_id'] !== (string) Auth::id()) abort(403);
        return view('pengguna.payments.show', compact('booking'));
    }
}


