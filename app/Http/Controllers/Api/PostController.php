<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Response;

class PostController extends Controller
{
    use AuthorizesRequests;

    /**
     * Menampilkan daftar post yang aktif.
     */
    public function index()
    {
        $posts = Post::with('user')
            ->active()
            ->latest('published_at')
            ->paginate(20);

        return PostResource::collection($posts);
    }

    /**
     * Menyimpan post baru.
     */
    public function store(StorePostRequest $request)
    {
        $post = $request->user()->posts()->create($request->validated());

        return (new PostResource($post->load('user')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Menampilkan post aktif yang spesifik.
     */
    public function show(Post $post)
    {
        // Batalkan dengan 404 jika post adalah draf atau dijadwalkan,
        // sesuai dengan kebutuhan test.
        if ($post->is_draft || ($post->published_at && $post->published_at->isFuture())) {
            abort(404);
        }

        return new PostResource($post->load('user'));
    }

    /**
     * Memperbarui post yang spesifik.
     */
    public function update(UpdatePostRequest $request, Post $post)
    {
        $this->authorize('update', $post);

        $post->update($request->validated());

        return new PostResource($post->load('user'));
    }

    /**
     * Menghapus post yang spesifik.
     */
    public function destroy(Post $post)
    {
        $this->authorize('destroy', $post);

        $post->delete();

        return response()->noContent();
    }
}
