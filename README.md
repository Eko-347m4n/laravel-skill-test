# Laravel Skill Test - Takeya Consulting

RESTful API for Post model with support for drafts, scheduling, and authentication, built with Laravel 12.

## Setup Instructions

1.  Clone this repository:

    ```bash
    git clone https://github.com/Eko-347m4n/laravel-skill-test.git
    cd laravel-skill-test
    ```

2.  Install dependencies:

    ```bash
    composer install
    ```

3.  Copy `.env` and generate key:

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4.  Create the database file (the project uses SQLite by default):

    ```bash
    touch database/database.sqlite
    ```

5.  Migrate database:

    ```bash
    php artisan migrate
    ```

6.  Start server:

    ```bash
    php artisan serve
    ```

7.  Run tests:
    ```bash
    php artisan test
    ```

## Implemented Features

- ✅ `posts.index`: Paginated list of published posts (excludes draft/scheduled)
- ✅ `posts.store`: Authenticated users can create posts with validation
- ✅ `posts.show`: Show only active posts, return 404 for draft/scheduled
- ✅ `posts.update`: Only post author can update post
- ✅ `posts.destroy`: Only post author can delete post
- ✅ Scheduled Publishing: Command to auto-publish scheduled posts
- ✅ Feature Testing: Comprehensive test cases covering all routes
- ✅ Redirect `/` to `/login` if unauthenticated

## Notes

- Used session-based authentication as required (no token or API auth).
- `posts.create` and `posts.edit` are implemented with static string return, as instructed.
- Auto-publishing is handled via custom Artisan command and Laravel scheduler.
- No front-end views implemented. All responses are JSON.
