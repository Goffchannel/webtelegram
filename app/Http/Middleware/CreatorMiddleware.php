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

        // Admins always have full creator access
        if ($user->is_admin) {
            return $next($request);
        }

        if (!$user->is_creator) {
            return redirect()->route('creator.subscription.show')
                ->with('error', 'Debes activar tu membresia de creador para acceder a esta seccion.');
        }

        // Trust our own DB status first. The Stripe webhook (HandleSuccessfulPayment)
        // keeps this field in sync and sets it to 'inactive' when a subscription lapses.
        // Cashier's subscribed() only works reliably after the webhook fires, so we
        // don't use it to immediately downgrade — only to upgrade if it's ahead of us.
        if ($user->creator_subscription_status === 'active') {
            return $next($request);
        }

        // DB says not active — double-check live via Cashier (handles already-active subs
        // whose DB field is stale for any reason).
        if ($user->subscribed('creator')) {
            $user->update(['creator_subscription_status' => 'active']);
            return $next($request);
        }

        return redirect()->route('creator.subscription.show')
            ->with('error', 'Tu membresia de creador no esta activa.');
    }
}
