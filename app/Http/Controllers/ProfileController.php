<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\AuthController as ApiAuth;
use App\Http\Traits\WebApiProxy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use WebApiProxy;

    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request, ApiAuth $api): RedirectResponse
    {
        $req = $this->makeApiRequest([], $request->only(['name', 'email', 'phone']));
        $this->proxyApi(fn() => $api->updateProfile($req));

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function uploadAvatar(Request $request, ApiAuth $api): RedirectResponse
    {
        $req = $this->makeApiRequest([], [], $request->file('avatar') ? ['avatar' => $request->file('avatar')] : []);
        $this->proxyApi(fn() => app()->call([$api, 'uploadAvatar'], ['request' => $req]));

        return Redirect::route('profile.edit')->with('status', 'avatar-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
