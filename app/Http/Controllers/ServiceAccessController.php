<?php

namespace App\Http\Controllers;

use App\Models\PurchaseServiceAccess;

class ServiceAccessController extends Controller
{
    public function show(string $token)
    {
        $access = PurchaseServiceAccess::with(['purchase', 'video', 'line'])
            ->where('access_token', $token)
            ->firstOrFail();

        if ($access->isExpired()) {
            if ($access->status !== 'expired') {
                $access->update(['status' => 'expired']);
            }

            return view('access.expired', compact('access'));
        }

        if ($access->status !== 'active') {
            $access->update(['status' => 'active']);
        }

        $access->update(['last_viewed_at' => now()]);

        return view('access.show', compact('access'));
    }
}
