<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DestroyPostRequest;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        // Request sudah divalidasi dan diotorisasi oleh StorePostRequest.
        // `user()` diambil dari user yang sedang login (terotentikasi).
        $post = $request->user()->posts()->create($request->validated());

        // Muat relasi user agar datanya ikut tampil di response.
        $post->load('user');

        // Kembalikan post yang baru dibuat dengan status 201 Created.
        return response()->json($post, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post): JsonResponse
    {
        // Return 404 if the post is a draft or scheduled for the future.
        if ($post->is_draft || ! $post->published_at || now()->lt($post->published_at)) {
            abort(404);
        }

        // Eager load the user relationship to include it in the response.
        $post->load('user');

        return response()->json($post);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        // Otorisasi dan validasi sudah ditangani oleh UpdatePostRequest.
        // Jika gagal, Laravel akan otomatis mengembalikan response 403 (Forbidden) atau 422 (Unprocessable).
        $post->update($request->validated());

        // Muat kembali relasi user untuk memastikan data ter-update ada di response.
        $post->load('user');

        // Kembalikan post yang sudah di-update.
        return response()->json($post);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DestroyPostRequest $request, Post $post): JsonResponse
    {
        // Otorisasi sudah ditangani oleh DestroyPostRequest.
        // Jika gagal, Laravel akan otomatis mengembalikan response 403 (Forbidden).
        $post->delete();

        return response()->json(null, 204); // 204 No Content - Indikasi sukses tanpa mengembalikan konten.
    }
}
