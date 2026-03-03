<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MyStoreController extends Controller
{
    public function index(Request $request)
    {
        $creator = $request->user();

        $stats = [
            'videos'   => $creator->creatorVideos()->count(),
            'pending'  => $creator->creatorPurchases()->where('verification_status', 'pending')->count(),
            'approved' => $creator->creatorPurchases()->where('verification_status', 'verified')->count(),
            'total'    => $creator->creatorPurchases()->count(),
        ];

        $recentPurchases = $creator->creatorPurchases()
            ->with(['video', 'serviceAccess'])
            ->latest()
            ->paginate(15);

        $categories = Category::where('creator_id', $creator->id)
            ->withCount('videos')
            ->orderBy('name')
            ->get();

        $avatarSrc = null;
        if ($creator->creator_avatar) {
            $avatarSrc = str_starts_with($creator->creator_avatar, 'http')
                ? $creator->creator_avatar
                : asset('storage/' . $creator->creator_avatar);
        }

        $paymentMethods = is_array($creator->creator_payment_methods)
            ? $creator->creator_payment_methods
            : [];

        return view('admin.my-store.index', compact(
            'creator', 'stats', 'recentPurchases', 'categories', 'avatarSrc', 'paymentMethods'
        ));
    }

    public function updateProfile(Request $request)
    {
        $creator = $request->user();

        $validated = $request->validate([
            'creator_store_name'    => 'required|string|max:120',
            'creator_slug'          => 'required|alpha_dash|min:3|max:80|unique:users,creator_slug,' . $creator->id,
            'creator_bio'           => 'nullable|string|max:1200',
            'telegram_user_id'      => 'nullable|digits_between:4,20',
            'paypal_url'            => 'nullable|url|max:500',
            'payment_button_html'   => 'nullable|string|max:8000',
            'other_payment_notes'   => 'nullable|string|max:2000',
            'creator_avatar_url'    => 'nullable|url|max:500',
            'creator_avatar'        => 'nullable|image|max:2048',
        ]);

        $sanitizedButtonHtml = isset($validated['payment_button_html'])
            ? preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $validated['payment_button_html'])
            : null;

        $paymentMethods = [
            'paypal_url'           => $validated['paypal_url'] ?? null,
            'payment_button_html'  => $sanitizedButtonHtml,
            'other_payment_notes'  => $validated['other_payment_notes'] ?? null,
        ];

        $avatarValue = $creator->creator_avatar;
        if ($request->hasFile('creator_avatar')) {
            $path = $request->file('creator_avatar')->store('avatars', 'public');
            $avatarValue = $path;
        } elseif (!empty($validated['creator_avatar_url'])) {
            $avatarValue = $validated['creator_avatar_url'];
        }

        $creator->update([
            'creator_store_name'      => $validated['creator_store_name'],
            'creator_slug'            => Str::slug($validated['creator_slug']),
            'creator_bio'             => $validated['creator_bio'] ?? null,
            'telegram_user_id'        => $validated['telegram_user_id'] ?? null,
            'creator_payment_methods' => $paymentMethods,
            'creator_avatar'          => $avatarValue,
        ]);

        return back()->with('success', 'Perfil de tienda actualizado.');
    }
}
