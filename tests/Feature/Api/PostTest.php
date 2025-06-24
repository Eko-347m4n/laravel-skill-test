<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the posts.index route returns a paginated list of active posts,
     * includes user data, and excludes drafts or future-scheduled posts.
     */
    public function test_returns_paginated_active_posts_with_user_data(): void
    {
        // 1. Arrange
        $user = User::factory()->create();

        // Create 22 active posts with random recent published dates.
        $activePosts = Post::factory()->count(22)->for($user)->create([
            'is_draft' => false,
            'published_at' => fn () => now()->subMinutes(rand(1, 10000)),
        ]);

        // Determine which post should be first in the response (the most recently published).
        // This is used to assert correct ordering and content.
        $expectedFirstPost = $activePosts->sortByDesc('published_at')->first();

        // Create a draft post that should NOT appear.
        $draftPost = Post::factory()->for($user)->create([
            'is_draft' => true,
            'published_at' => now()->subDay(),
        ]);

        // Create a scheduled post for the future that should NOT appear.
        $scheduledPost = Post::factory()->for($user)->create([
            'is_draft' => false,
            'published_at' => now()->addDay(),
        ]);

        // 2. Act
        $response = $this->getJson('/api/posts');

        // 3. Assert
        $response->assertStatus(200);

        // Assert the main pagination structure.
        // If this fails on the 'body' key, ensure your PostFactory generates 'body' data,
        // and that your Post model does not have a `$hidden` property or an API Resource
        // that excludes the 'body' attribute.
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'body',
                    'is_draft',
                    'published_at',
                    // Assert the user relation is included
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                ],
            ],
            'links',
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'path',
                'per_page',
                'to',
                'total',
            ],
        ]);

        // Assert pagination counts (20 per page from a total of 22 active posts)
        $response->assertJsonCount(20, 'data');
        $response->assertJsonPath('meta.total', 22);

        // Assert that the posts are ordered correctly by `published_at` descending
        // and that the body content is present and correct for the first item.
        $response->assertJsonPath('data.0.id', $expectedFirstPost->id);
        $response->assertJsonPath('data.0.body', $expectedFirstPost->body);

        // Assert that draft and scheduled posts are not in the response
        $response->assertJsonMissing(['id' => $draftPost->id]);
        $response->assertJsonMissing(['id' => $scheduledPost->id]);
    }
}
