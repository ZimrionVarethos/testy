<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\UserController as ApiUser;
use App\Http\Traits\WebApiProxy;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use WebApiProxy;

    public function index(Request $request, ApiUser $api)
    {
        $req   = $this->makeApiRequest(['per_page' => 15]);
        $users = $api->indexForWeb($req);

        return view('admin.users.index', compact('users'));
    }

    public function show(string $id, ApiUser $api)
    {
        ['user' => $user, 'bookings' => $bookings] = $api->showForWeb($id);

        return view('admin.users.show', compact('user', 'bookings'));
    }

    public function toggle(string $id, ApiUser $api)
    {
        $result = $this->tryProxyApi(fn() => $api->toggle($id));

        if (!($result['success'] ?? false)) {
            return back()->withErrors(['error' => $result['message'] ?? 'Gagal mengubah status pengguna.']);
        }

        return back()->with('success', 'Status pengguna diperbarui.');
    }
}
