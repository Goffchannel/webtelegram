<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use VercelBlobPhp\Client as BlobClient;
use VercelBlobPhp\CommonCreateBlobOptions;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $creators = User::query()
            ->whereNotNull('creator_slug')
            ->where(function ($query) {
                $query->where('is_creator', true)->orWhere('is_admin', true);
            })
            ->withCount(['creatorCategories as categories_count'])
            ->orderByRaw('COALESCE(creator_store_name, name) asc')
            ->get();

        return view('admin.categories.manage', compact('creators'));
    }

    public function showCreator(User $creator)
    {
        $categories = Category::where('creator_id', $creator->id)
            ->withCount('videos')
            ->orderBy('name')
            ->get();

        return view('admin.categories.creator', compact('creator', 'categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, User $creator)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,NULL,id,creator_id,' . $creator->id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'image_url' => 'nullable|url',
        ]);

        $category = new Category();
        $category->creator_id = $creator->id;
        $category->name = $request->name;

        if ($request->hasFile('image')) {
            $this->uploadImage($request, $category);
        } elseif ($request->filled('image_url')) {
            $category->image_url = $request->image_url;
        }

        $category->save();

        return redirect()->route('admin.categories.creator', $creator)->with('success', 'Category created successfully.');
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $creator, Category $category)
    {
        if ((int) $category->creator_id !== (int) $creator->id) {
            return response()->json(['success' => false, 'message' => 'Category does not belong to selected creator.'], 422);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id . ',id,creator_id,' . $creator->id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'image_url' => 'nullable|url',
        ]);

        $category->name = $request->name;

        if ($request->hasFile('image')) {
            $this->deleteImage($category);
            $this->uploadImage($request, $category);
        } elseif ($request->filled('image_url')) {
            $this->deleteImage($category);
            $category->image_url = $request->image_url;
            $category->image_path = null;
            $category->image_blob_url = null;
        }

        $category->save();

        return response()->json(['success' => true, 'message' => 'Category updated successfully.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $creator, Category $category)
    {
        if ((int) $category->creator_id !== (int) $creator->id) {
            return response()->json(['success' => false, 'message' => 'Category does not belong to selected creator.'], 422);
        }

        // Move videos to creator's "General" category if it exists, otherwise null.
        $generalCategory = Category::where('creator_id', $creator->id)->where('name', 'General')->first();
        $newCategoryId = $generalCategory && $generalCategory->id !== $category->id ? $generalCategory->id : null;
        $category->videos()->update(['category_id' => $newCategoryId]);

        $this->deleteImage($category);
        $category->delete();

        return response()->json(['success' => true, 'message' => 'Category deleted successfully.']);
    }

    public function toggleHide(User $creator, Category $category)
    {
        if ((int) $category->creator_id !== (int) $creator->id) {
            return response()->json(['success' => false, 'message' => 'Category does not belong to selected creator.'], 422);
        }

        $category->is_hidden = !$category->is_hidden;
        $category->save();

        return response()->json([
            'success' => true,
            'is_hidden' => $category->is_hidden,
            'message' => $category->is_hidden
                ? 'Categoria marcada como HIDE. No se mostrara en la tienda publica.'
                : 'Categoria visible en la tienda publica.',
        ]);
    }

    private function uploadImage(Request $request, Category $category)
    {
        try {
            $imageFile = $request->file('image');
            $imageName = "categories/" . time() . '_' . $category->id . '.' . $imageFile->getClientOriginalExtension();

            $blobToken = Setting::get('vercel_blob_token') ?: env('test_READ_WRITE_TOKEN');
            if (empty($blobToken)) {
                throw new \Exception('Vercel Blob token not configured in admin settings');
            }

            if (!class_exists('VercelBlobPhp\Client') || !class_exists('VercelBlobPhp\CommonCreateBlobOptions')) {
                throw new \Exception('Vercel Blob package not available. Please run composer install.');
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
                throw new \Exception('Vercel Blob upload failed - no URL returned');
            }

            $category->image_path = $imageName;
            $category->image_blob_url = $result->url;
            $category->image_url = null; // Clear external url

        } catch (\Exception $e) {
            Log::error('Category image upload error', ['error' => $e->getMessage()]);
            // Propagate exception to be caught by the caller
            throw $e;
        }
    }

    private function deleteImage(Category $category)
    {
        if ($category->image_blob_url) {
            try {
                if (class_exists('VercelBlobPhp\Client')) {
                    $blobToken = Setting::get('vercel_blob_token') ?: env('test_READ_WRITE_TOKEN');
                    $blobClient = new BlobClient($blobToken);
                    $blobClient->del([$category->image_blob_url]);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to delete category image from Vercel Blob', ['error' => $e->getMessage()]);
            }
        }
    }
}
