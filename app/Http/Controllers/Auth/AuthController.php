<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('Invalid credentials.'),
            ]);
        }

        $user = Auth::user();
        if (! $user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => __('Account is disabled.')]);
        }

        $user->update(['last_login_at' => now()]);
        $request->session()->regenerate();

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $user = Auth::user();
        $default = $user->hasAnyRole(['CEO', 'CFO', 'Director'])
            ? route('dashboard.ceo')
            : ($user->can('view daily closing') ? route('dashboard.closing') : route('settings.index'));

        return redirect()->intended($default);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
