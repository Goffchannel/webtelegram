<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreatorController extends Controller
{
    public function storefront(User $creator)
    {
        if (!$creator->is_creator || !$creator->subscribed('creator')) {
            abort(404);
        }

        $videos = Video::where('creator_id', $creator->id)
            ->with('category')
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('creator.storefront', compact('creator', 'videos'));
    }

    public function dashboard(Request $request)
    {
        $creator = $request->user();

        $stats = [
            'videos' => $creator->creatorVideos()->count(),
            'pending' => $creator->creatorPurchases()->where('verification_status', 'pending')->count(),
            'approved' => $creator->creatorPurchases()->where('verification_status', 'verified')->count(),
        ];

        $recentPurchases = $creator->creatorPurchases()
            ->with('video')
            ->latest()
            ->limit(10)
            ->get();

        return view('creator.dashboard', compact('creator', 'stats', 'recentPurchases'));
    }

    public function updateProfile(Request $request)
    {
        $creator = $request->user();

        $validated = $request->validate([
            'creator_store_name' => 'required|string|max:120',
            'creator_slug' => 'required|alpha_dash|min:3|max:80|unique:users,creator_slug,' . $creator->id,
            'creator_bio' => 'nullable|string|max:1200',
            'telegram_user_id' => 'nullable|digits_between:4,20',
            'paypal_url' => 'nullable|url|max:500',
            'payment_button_html' => 'nullable|string|max:8000',
            'other_payment_notes' => 'nullable|string|max:2000',
        ]);

        $sanitizedButtonHtml = isset($validated['payment_button_html'])
            ? preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $validated['payment_button_html'])
            : null;

        $paymentMethods = [
            'paypal_url' => $validated['paypal_url'] ?? null,
            'payment_button_html' => $sanitizedButtonHtml,
            'other_payment_notes' => $validated['other_payment_notes'] ?? null,
        ];

        $creator->update([
            'creator_store_name' => $validated['creator_store_name'],
            'creator_slug' => Str::slug($validated['creator_slug']),
            'creator_bio' => $validated['creator_bio'] ?? null,
            'telegram_user_id' => $validated['telegram_user_id'] ?? null,
            'creator_payment_methods' => $paymentMethods,
        ]);

        return back()->with('success', 'Perfil de creador actualizado.');
    }

    public function videos()
    {
        $videos = Auth::user()->creatorVideos()->with('category')->latest()->paginate(15);

        return view('creator.videos', compact('videos'));
    }

    public function updateVideo(Request $request, Video $video)
    {
        $creator = $request->user();

        if ($video->creator_id !== $creator->id && !$creator->is_admin) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0|max:9999.99',
            'category_id' => 'required|exists:categories,id',
        ]);

        $video->update($validated);

        return back()->with('success', 'Video actualizado correctamente.');
    }

    public function deleteVideo(Request $request, Video $video)
    {
        $creator = $request->user();

        if ($video->creator_id !== $creator->id && !$creator->is_admin) {
            abort(403);
        }

        $video->delete();

        return back()->with('success', 'Video eliminado correctamente.');
    }

    public function purchases()
    {
        $purchases = Auth::user()->creatorPurchases()->with('video')->latest()->paginate(20);

        return view('creator.purchases', compact('purchases'));
    }

    public function approvePurchase(Request $request, Purchase $purchase)
    {
        if ($purchase->creator_id !== $request->user()->id) {
            abort(403);
        }

        $purchase->update([
            'verification_status' => 'verified',
            'purchase_status' => 'completed',
            'delivery_status' => 'pending',
        ]);

        return back()->with('success', 'Compra aprobada. El comprador ya puede usar /getvideo.');
    }

    public function rejectPurchase(Request $request, Purchase $purchase)
    {
        if ($purchase->creator_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'delivery_notes' => 'nullable|string|max:1000',
        ]);

        $purchase->update([
            'verification_status' => 'invalid',
            'purchase_status' => 'disputed',
            'delivery_notes' => $validated['delivery_notes'] ?? 'Pago rechazado por el creador',
        ]);

        return back()->with('success', 'Compra rechazada.');
    }
}
