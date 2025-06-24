<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class PostController extends Controller
{
    /**
     * Display a listing of the active posts.
     */
    public function index(): JsonResponse
    {
        $posts = Post::with('user')
            ->where('is_draft', false)
            ->where('published_at', '<=', Carbon::now())
            ->latest('published_at')
            ->paginate(20);

        return response()->json($posts);
    }
}
