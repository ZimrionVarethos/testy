<?php namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User; use App\Models\Booking;

class UserController extends Controller
{
    public function index()
    {
        $users = User::where('role', 'pengguna')->orderBy('created_at', 'desc')->paginate(15);
        return view('admin.users.index', compact('users'));
    }

    public function show(string $id)
    {
        $user     = User::findOrFail($id);
        $bookings = Booking::where('user.user_id', $id)->orderBy('created_at', 'desc')->limit(10)->get();
        return view('admin.users.show', compact('user', 'bookings'));
    }

    public function toggle(string $id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => !$user->is_active]);
        return back()->with('success', 'Status pengguna diperbarui.');
    }
}