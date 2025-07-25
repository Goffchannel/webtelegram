<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of all categories.
     * This will be the new home page.
     */
    public function index()
    {
        $categories = Category::withCount('videos')->orderBy('name')->get();
        return view('categories.index', compact('categories'));
    }

    /**
     * Display all videos for a specific category.
     * Reuses the main video listing view.
     */
    public function show(Category $category)
    {
        $videos = $category->videos()->orderBy('created_at', 'desc')->paginate(12);
        return view('videos.index', compact('videos', 'category'));
    }
}
