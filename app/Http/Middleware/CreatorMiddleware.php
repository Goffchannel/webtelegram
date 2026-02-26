<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CreatorMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->is_creator) {
            return redirect()->route('creator.subscription.show')
                ->with('error', 'Debes activar tu membresia de creador para acceder a esta seccion.');
        }

        if (!$user->subscribed('creator')) {
            $user->update([
                'creator_subscription_status' => 'inactive',
                'is_creator' => false,
            ]);

            return redirect()->route('creator.subscription.show')
                ->with('error', 'Tu membresia de creador no esta activa.');
        }

        if ($user->creator_subscription_status !== 'active') {
            $user->update(['creator_subscription_status' => 'active']);
        }

        return $next($request);
    }
}
