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

        // Laravel can automatically format a Paginator instance into the correct JSON structure.
        return response()->json($posts);
    }

    public function create()
    {
        // For an API, it's better to return JSON.
        // This endpoint could provide metadata needed for a frontend form.
        return response()->json([
            'message' => 'This endpoint can be used to provide data for a post creation form.',
        ]);
    }
}
