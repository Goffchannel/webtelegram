<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Setting;
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
        $categories = Category::withCount('videos')->orderBy('name')->get();
        return view('admin.categories.manage', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'image_url' => 'nullable|url',
        ]);

        $category = new Category();
        $category->name = $request->name;

        if ($request->hasFile('image')) {
            $this->uploadImage($request, $category);
        } elseif ($request->filled('image_url')) {
            $category->image_url = $request->image_url;
        }

        $category->save();

        return redirect()->route('admin.categories.manage')->with('success', 'Category created successfully.');
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
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
    public function destroy(Category $category)
    {
        // Do not delete category with ID 1 (General)
        if ($category->id === 1) {
            return response()->json(['success' => false, 'message' => 'Cannot delete the default General category.'], 403);
        }

        // Move videos to the 'General' category before deleting
        $generalCategory = Category::find(1);
        if ($generalCategory) {
            $category->videos()->update(['category_id' => $generalCategory->id]);
        }

        $this->deleteImage($category);
        $category->delete();

        return response()->json(['success' => true, 'message' => 'Category deleted successfully.']);
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
