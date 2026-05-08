<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\PersonalAccessToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Buat Sanctum token dan simpan di session agar web controllers bisa proxy ke API
        $user = Auth::user();
        PersonalAccessToken::where('tokenable_id', (string) $user->getKey())
                           ->where('tokenable_type', get_class($user))
                           ->where('name', 'web-session')
                           ->delete();
        $token = $user->createToken('web-session')->plainTextToken;
        $request->session()->put('api_token', $token);

        return redirect()->route('dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Hapus Sanctum token sesi web
        if ($user = Auth::user()) {
            PersonalAccessToken::where('tokenable_id', (string) $user->getKey())
                               ->where('tokenable_type', get_class($user))
                               ->where('name', 'web-session')
                               ->delete();
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
