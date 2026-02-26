<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display creator login view.
     */
    public function createCreator(): View
    {
        return view('auth.login', [
            'loginType' => 'creator',
            'heading' => 'Login de Creador',
            'actionRoute' => route('logincreator.store'),
        ]);
    }

    /**
     * Display admin login view.
     */
    public function createAdmin(): View
    {
        return view('auth.login', [
            'loginType' => 'admin',
            'heading' => 'Login Admin',
            'actionRoute' => route('loginadmin.store'),
        ]);
    }

    /**
     * Handle creator authentication.
     */
    public function storeCreator(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        if (Auth::user()?->is_admin) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Este login es solo para creadores. El acceso de admin usa una URL privada.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Handle admin authentication.
     */
    public function storeAdmin(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        if (!Auth::user()?->is_admin) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Acceso denegado. Esta URL es solo para administradores.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.videos.manage', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
