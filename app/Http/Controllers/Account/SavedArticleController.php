<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostBookmark;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SavedArticleController extends Controller
{
    public function index(Request $request): View
    {
        $bookmarks = $request->user()->bookmarks()
            ->whereHas('post', fn ($query) => $query->published())
            ->with(['post' => fn ($query) => $query->with(['primaryCategory', 'featuredMedia'])])
            ->latest()->paginate(12);

        return view('account.saved', compact('bookmarks'));
    }

    public function store(Request $request, Post $post): RedirectResponse
    {
        Post::query()->published()->findOrFail($post->getKey());
        $request->user()->bookmarks()->firstOrCreate(['post_id' => $post->getKey()]);

        return back()->with('success', 'Article saved.');
    }

    public function destroy(Request $request, PostBookmark $bookmark): RedirectResponse
    {
        Gate::authorize('delete', $bookmark);
        $bookmark->delete();

        return back()->with('success', 'Article removed from saved articles.');
    }
}
