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
            ->whereNotNull('creator_slug')
            ->where(function ($query) {
                $query->where(function ($inner) {
                    $inner->where('is_creator', true)
                        ->where('creator_subscription_status', 'active');
                })->orWhere(function ($inner) {
                    $inner->where('is_admin', true)
                        ->where('is_creator', true);
                });
            })
            ->whereHas('creatorVideos', function ($query) {
                $query->whereHas('category', function ($categoryQuery) {
                    $categoryQuery->where('is_hidden', false);
                });
            })
            ->withCount(['creatorVideos as videos_count' => function ($query) {
                $query->whereHas('category', function ($categoryQuery) {
                    $categoryQuery->where('is_hidden', false);
                });
            }])
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
