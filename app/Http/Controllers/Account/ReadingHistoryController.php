<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadingHistoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $userId = $request->user()->getKey();
        $posts = Post::query()->published()
            ->whereHas('visits', fn ($query) => $query->where('visitor_id', $userId))
            ->with(['primaryCategory', 'featuredMedia'])
            ->withMax(['visits as last_read_at' => fn ($query) => $query->where('visitor_id', $userId)], 'visited_at')
            ->orderByDesc('last_read_at')->paginate(12);

        return view('account.history', compact('posts'));
    }
}
