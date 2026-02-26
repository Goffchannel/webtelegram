<?php

namespace App\Http\Controllers;

use App\Models\User;

class CategoryController extends Controller
{
    /**
     * Home page: list active creators.
     */
    public function index()
    {
        $creators = User::query()
            ->where('is_creator', true)
            ->where('creator_subscription_status', 'active')
            ->whereNotNull('creator_slug')
            ->withCount('creatorVideos as videos_count')
            ->with('latestCreatorVideo')
            ->orderByRaw('COALESCE(creator_store_name, name) asc')
            ->get();

        return view('categories.index', compact('creators'));
    }

    /**
     * Legacy category route no longer used.
     */
    public function show($category)
    {
        return redirect()->route('categories.index');
    }
}
