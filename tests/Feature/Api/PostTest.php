<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
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
        $response->assertJsonPath('data.0.user.id', $expectedFirstPost->user->id); // Ensure user data is present and correct

        // Assert that draft and scheduled posts are not in the response
        $response->assertJsonMissing(['id' => $draftPost->id]);
        $response->assertJsonMissing(['id' => $scheduledPost->id]);
    }

    /**
     * Test that guests can access the index route and get active posts.
     */
    public function test_guests_can_access_index_and_see_active_posts(): void
    {
        $user = User::factory()->create();

        $activePost = Post::factory()->for($user)->create([
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        $draftPost = Post::factory()->for($user)->create([
            'is_draft' => true,
            'published_at' => now()->subDay(),
        ]);

        $scheduledPost = Post::factory()->for($user)->create([
            'is_draft' => false,
            'published_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/posts');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $activePost->id]);
        $response->assertJsonMissing(['id' => $draftPost->id]);
        $response->assertJsonMissing(['id' => $scheduledPost->id]);
    }

    // =========================================================================
    // ==> SHOW Tests
    // =========================================================================

    public function test_show_returns_active_post_and_404_for_inactive_or_missing_posts(): void
    {
        $user = User::factory()->create();
        $activePost = Post::factory()->for($user)->create(['is_draft' => false, 'published_at' => now()->subDay()]);
        $draftPost = Post::factory()->for($user)->create(['is_draft' => true]);
        $scheduledPost = Post::factory()->for($user)->create(['is_draft' => false, 'published_at' => now()->addDay()]);

        // Success case: Active post is returned
        $this->getJson("/api/posts/{$activePost->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $activePost->id);

        // Failure cases: Inactive posts return 404
        $this->getJson("/api/posts/{$draftPost->id}")->assertNotFound();
        $this->getJson("/api/posts/{$scheduledPost->id}")->assertNotFound();
        $this->getJson('/api/posts/999')->assertNotFound(); // Non-existent post
    }

    /**
     * Test that guests can access show route for active posts but not for drafts or scheduled.
     */
    public function test_guests_can_access_show_active_post_but_not_draft_or_scheduled(): void
    {
        $user = User::factory()->create();
        $activePost = Post::factory()->for($user)->create(['is_draft' => false, 'published_at' => now()->subDay()]);
        $draftPost = Post::factory()->for($user)->create(['is_draft' => true]);
        $scheduledPost = Post::factory()->for($user)->create(['is_draft' => false, 'published_at' => now()->addDay()]);

        $this->getJson("/api/posts/{$activePost->id}")->assertOk();
        $this->getJson("/api/posts/{$draftPost->id}")->assertNotFound();
        $this->getJson("/api/posts/{$scheduledPost->id}")->assertNotFound();
    }

    // =========================================================================
    // ==> STORE Tests
    // =========================================================================

    public function test_guests_cannot_create_posts(): void
    {
        $this->postJson('/api/posts', ['title' => 'Fail', 'body' => '...'])->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_a_post(): void
    {
        $user = User::factory()->create();
        $postData = [
            'title' => 'My First Post',
            'body' => 'This is the body of my first post.',
            'is_draft' => false,
            'published_at' => now()->toDateTimeString(),
        ];

        Sanctum::actingAs($user);
        $this->postJson('/api/posts', $postData)
            ->assertStatus(201)
            ->assertJsonPath('data.title', 'My First Post')
            ->assertJsonPath('data.user.id', $user->id);

        $this->assertDatabaseHas('posts', ['title' => 'My First Post', 'user_id' => $user->id]);
    }

    /**
     * Test authenticated user can create draft and scheduled posts.
     */
    public function test_authenticated_user_can_create_draft_and_scheduled_posts(): void
    {
        $user = User::factory()->create();

        $draftPostData = [
            'title' => 'Draft Post',
            'body' => 'Draft body',
            'is_draft' => true,
            'published_at' => null,
        ];

        $scheduledPostData = [
            'title' => 'Scheduled Post',
            'body' => 'Scheduled body',
            'is_draft' => false,
            'published_at' => now()->addDay()->toIso8601String(),
        ];

        Sanctum::actingAs($user);
        $this->postJson('/api/posts', $draftPostData)
            ->assertStatus(201)
            ->assertJsonPath('data.is_draft', true);

        Sanctum::actingAs($user);
        $this->postJson('/api/posts', $scheduledPostData)
            ->assertStatus(201)
            ->assertJsonPath('data.published_at', $scheduledPostData['published_at']);
    }

    /**
     * @dataProvider invalidPostDataProvider
     */
    public function test_post_creation_fails_with_invalid_data(array $invalidData, string|array $expectedErrorKeys): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $this->postJson('/api/posts', $invalidData)
            ->assertStatus(422)
            ->assertJsonValidationErrors($expectedErrorKeys);
    }

    // =========================================================================
    // ==> UPDATE Tests
    // =========================================================================

    public function test_author_can_update_their_post(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->for($author)->create();
        $updateData = ['title' => 'Updated Title'];

        Sanctum::actingAs($author);
        $this->putJson("/api/posts/{$post->id}", $updateData)
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Updated Title']);
    }

    /**
     * Test author can update draft and scheduled posts.
     */
    public function test_author_can_update_draft_and_scheduled_posts(): void
    {
        $author = User::factory()->create();

        $draftPost = Post::factory()->for($author)->create([
            'is_draft' => true,
            'published_at' => null,
        ]);

        $scheduledPost = Post::factory()->for($author)->create([
            'is_draft' => false,
            'published_at' => now()->addDay(),
        ]);

        $updateData = ['title' => 'Updated Title'];

        Sanctum::actingAs($author);
        $this->putJson("/api/posts/{$draftPost->id}", $updateData)
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated Title');

        Sanctum::actingAs($author);
        $this->putJson("/api/posts/{$scheduledPost->id}", $updateData)
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated Title');
    }

    public function test_non_author_cannot_update_a_post(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->for($author)->create();
        $nonAuthor = User::factory()->create();

        Sanctum::actingAs($nonAuthor);
        $this->putJson("/api/posts/{$post->id}", ['title' => '...'])
            ->assertForbidden();
    }

    public function test_guest_cannot_update_a_post(): void
    {
        $post = Post::factory()->create();
        $this->putJson("/api/posts/{$post->id}", ['title' => '...'])->assertUnauthorized();
    }

    /**
     * @dataProvider invalidPostUpdateDataProvider
     */
    public function test_post_update_fails_with_invalid_data(array $invalidData, string|array $expectedErrorKeys): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->for($author)->create();

        Sanctum::actingAs($author);
        $this->putJson("/api/posts/{$post->id}", $invalidData)
            ->assertStatus(422)
            ->assertJsonValidationErrors($expectedErrorKeys);
    }

    // =========================================================================
    // ==> DESTROY Tests
    // =========================================================================

    public function test_author_can_delete_their_post(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->for($author)->create();

        $this->actingAs($author)->deleteJson("/api/posts/{$post->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    /**
     * Test author can delete draft and scheduled posts.
     */
    public function test_author_can_delete_draft_and_scheduled_posts(): void
    {
        $author = User::factory()->create();

        $draftPost = Post::factory()->for($author)->create([
            'is_draft' => true,
            'published_at' => null,
        ]);

        $scheduledPost = Post::factory()->for($author)->create([
            'is_draft' => false,
            'published_at' => now()->addDay(),
        ]);

        $this->actingAs($author)->deleteJson("/api/posts/{$draftPost->id}")
            ->assertNoContent();

        $this->actingAs($author)->deleteJson("/api/posts/{$scheduledPost->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('posts', ['id' => $draftPost->id]);
        $this->assertDatabaseMissing('posts', ['id' => $scheduledPost->id]);
    }

    public function test_non_author_cannot_delete_a_post(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->for($author)->create();
        $nonAuthor = User::factory()->create();

        $this->actingAs($nonAuthor)->deleteJson("/api/posts/{$post->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_guest_cannot_delete_a_post(): void
    {
        $post = Post::factory()->create();
        $this->deleteJson("/api/posts/{$post->id}")->assertUnauthorized();
    }

    // =========================================================================
    // ==> Data Providers
    // =========================================================================

    public static function invalidPostDataProvider(): array
    {
        return [
            'missing title' => [
                ['body' => 'some body', 'is_draft' => true],
                'title',
            ],
            'missing body' => [
                ['title' => 'some title', 'is_draft' => true],
                'body',
            ],
            'is_draft is not a boolean' => [
                ['title' => 'some title', 'body' => 'some body', 'is_draft' => 'not-a-bool'],
                'is_draft',
            ],
            'published_at is missing for a non-draft post' => [
                ['title' => 'some title', 'body' => 'some body', 'is_draft' => false],
                'published_at',
            ],
            'title is too long' => [
                ['title' => str_repeat('a', 256), 'body' => 'some body', 'is_draft' => true],
                'title',
            ],
        ];
    }

    public static function invalidPostUpdateDataProvider(): array
    {
        return [
            'title is empty' => [
                ['title' => ''],
                'title',
            ],
            'title is too long' => [
                ['title' => str_repeat('a', 256)],
                'title',
            ],
            'body is empty' => [
                ['body' => ''],
                'body',
            ],
            'is_draft is not a boolean' => [
                ['is_draft' => 'not-a-bool'],
                'is_draft',
            ],
            'published_at is not a valid date' => [
                ['published_at' => 'not-a-date'],
                'published_at',
            ],
            'published_at is required when setting is_draft to false' => [
                ['is_draft' => false, 'published_at' => null],
                'published_at',
            ],
        ];
    }
}
