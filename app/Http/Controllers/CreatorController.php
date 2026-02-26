<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use VercelBlobPhp\Client as BlobClient;
use VercelBlobPhp\CommonCreateBlobOptions;

class CreatorController extends Controller
{
    public function storefront(User $creator)
    {
        if (!$creator->is_creator || !$creator->subscribed('creator')) {
            abort(404);
        }

        $categories = Category::where('creator_id', $creator->id)
            ->withCount(['videos' => function ($query) use ($creator) {
                $query->where('creator_id', $creator->id);
            }])
            ->orderBy('name')
            ->get();

        return view('creator.storefront', compact('creator', 'categories'));
    }

    public function storefrontCategory(User $creator, Category $category)
    {
        if (!$creator->is_creator || !$creator->subscribed('creator')) {
            abort(404);
        }

        if ((int) $category->creator_id !== (int) $creator->id) {
            abort(404);
        }

        $videos = Video::where('creator_id', $creator->id)
            ->where('category_id', $category->id)
            ->with('category')
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('creator.store-category', compact('creator', 'category', 'videos'));
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

        $videos = $creator->creatorVideos()
            ->with('category')
            ->latest()
            ->paginate(10);

        $categories = Category::where('creator_id', $creator->id)
            ->withCount('videos')
            ->orderBy('name')
            ->get();

        return view('creator.dashboard', compact('creator', 'stats', 'recentPurchases', 'videos', 'categories'));
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
        $user = Auth::user();
        $videos = $user->creatorVideos()->with('category')->latest()->paginate(15);
        $defaultCategoryId = Category::where('creator_id', $user->id)->value('id');

        return view('creator.videos', compact('videos', 'defaultCategoryId'));
    }

    public function updateVideo(Request $request, Video $video)
    {
        $creator = $request->user();

        if ($video->creator_id !== $creator->id && !$creator->is_admin) {
            abort(403);
        }

        $hasThumbnailUrlColumn = Schema::hasColumn('videos', 'thumbnail_url');

        $categoryRule = $creator->is_admin
            ? 'required|exists:categories,id'
            : [
                'required',
                Rule::exists('categories', 'id')->where(function ($query) use ($creator) {
                    $query->where('creator_id', $creator->id);
                }),
            ];

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0|max:9999.99',
            'category_id' => $categoryRule,
            'blur_intensity' => 'nullable|integer|min:1|max:20',
            'show_blurred' => 'nullable|boolean',
            'allow_preview' => 'nullable|boolean',
        ];

        if ($hasThumbnailUrlColumn) {
            $rules['thumbnail_url'] = 'nullable|url|max:500';
        } else {
            // Keep compatibility for environments where external URL is saved in thumbnail_path.
            $rules['thumbnail_url'] = 'nullable|string|max:500';
        }

        $validated = $request->validate($rules);

        $updateData = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'price' => (float) $validated['price'],
            'category_id' => (int) $validated['category_id'],
            'blur_intensity' => (int) ($validated['blur_intensity'] ?? $video->blur_intensity ?? 10),
            'show_blurred_thumbnail' => $request->boolean('show_blurred'),
            'allow_preview' => $request->boolean('allow_preview'),
        ];

        if (!empty($validated['thumbnail_url'])) {
            $thumbnailUrl = trim((string) $validated['thumbnail_url']);

            if (filter_var($thumbnailUrl, FILTER_VALIDATE_URL)) {
                if ($hasThumbnailUrlColumn) {
                    $updateData['thumbnail_url'] = $thumbnailUrl;
                    $updateData['thumbnail_path'] = null;
                } else {
                    $updateData['thumbnail_path'] = $thumbnailUrl;
                }
                $updateData['thumbnail_blob_url'] = null;
            }
        } elseif ($request->has('thumbnail_url') && trim((string) $request->input('thumbnail_url')) === '') {
            // User explicitly cleared the field.
            if ($hasThumbnailUrlColumn) {
                $updateData['thumbnail_url'] = null;
            }
            $updateData['thumbnail_path'] = null;
            $updateData['thumbnail_blob_url'] = null;
        }

        $video->update($updateData);

        return back()->with('success', 'Video actualizado correctamente.');
    }

    public function storeCategory(Request $request)
    {
        $creator = $request->user();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where(function ($query) use ($creator) {
                    $query->where('creator_id', $creator->id);
                }),
            ],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'image_url' => 'nullable|url|max:1000',
        ]);

        $category = new Category();
        $category->creator_id = $creator->id;
        $category->name = $validated['name'];

        try {
            if ($request->hasFile('image')) {
                $this->uploadCategoryImage($request, $category);
            } elseif (!empty($validated['image_url'])) {
                $category->image_url = $validated['image_url'];
                $category->image_path = null;
                $category->image_blob_url = null;
            }
        } catch (\Exception $e) {
            return back()->with('error', 'No se pudo subir la imagen de categoria: ' . $e->getMessage());
        }

        $category->save();

        return back()->with('success', 'Categoria creada correctamente.');
    }

    public function updateCategory(Request $request, Category $category)
    {
        $creator = $request->user();

        if ((int) $category->creator_id !== (int) $creator->id && !$creator->is_admin) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')
                    ->ignore($category->id)
                    ->where(function ($query) use ($category) {
                        $query->where('creator_id', $category->creator_id);
                    }),
            ],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'image_url' => 'nullable|url|max:1000',
        ]);

        $category->name = $validated['name'];

        try {
            if ($request->hasFile('image')) {
                $this->deleteCategoryImage($category);
                $this->uploadCategoryImage($request, $category);
            } elseif ($request->filled('image_url')) {
                $this->deleteCategoryImage($category);
                $category->image_url = $validated['image_url'];
                $category->image_path = null;
                $category->image_blob_url = null;
            }
        } catch (\Exception $e) {
            return back()->with('error', 'No se pudo actualizar la imagen de categoria: ' . $e->getMessage());
        }

        $category->save();

        return back()->with('success', 'Categoria actualizada.');
    }

    public function deleteCategory(Request $request, Category $category)
    {
        $creator = $request->user();

        if ((int) $category->creator_id !== (int) $creator->id && !$creator->is_admin) {
            abort(403);
        }

        Video::where('creator_id', $creator->id)
            ->where('category_id', $category->id)
            ->update(['category_id' => null]);

        $this->deleteCategoryImage($category);
        $category->delete();

        return back()->with('success', 'Categoria eliminada.');
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

    private function uploadCategoryImage(Request $request, Category $category): void
    {
        $imageFile = $request->file('image');
        if (!$imageFile) {
            return;
        }

        try {
            $imageName = 'categories/' . time() . '_' . Str::random(6) . '.' . $imageFile->getClientOriginalExtension();

            $blobToken = Setting::get('vercel_blob_token') ?: env('test_READ_WRITE_TOKEN');
            if (empty($blobToken)) {
                throw new \Exception('Vercel Blob token no configurado');
            }

            if (!class_exists('VercelBlobPhp\Client') || !class_exists('VercelBlobPhp\CommonCreateBlobOptions')) {
                throw new \Exception('Paquete Vercel Blob no disponible');
            }

            $blobClient = new BlobClient($blobToken);
            $fileContent = file_get_contents($imageFile->getRealPath());

            $options = new CommonCreateBlobOptions(
                access: 'public',
                addRandomSuffix: false,
                contentType: $imageFile->getMimeType() ?: 'image/jpeg',
            );

            $result = $blobClient->put($imageName, $fileContent, $options);

            if (!$result || !isset($result->url)) {
                throw new \Exception('Upload de imagen fallido');
            }

            $category->image_path = $imageName;
            $category->image_blob_url = $result->url;
            $category->image_url = null;
        } catch (\Exception $e) {
            Log::warning('Creator category image upload failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function deleteCategoryImage(Category $category): void
    {
        if (!$category->image_blob_url) {
            return;
        }

        try {
            if (class_exists('VercelBlobPhp\Client')) {
                $blobToken = Setting::get('vercel_blob_token') ?: env('test_READ_WRITE_TOKEN');
                if (!empty($blobToken)) {
                    $blobClient = new BlobClient($blobToken);
                    $blobClient->del([$category->image_blob_url]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to delete creator category image', ['error' => $e->getMessage()]);
        }
    }
}
