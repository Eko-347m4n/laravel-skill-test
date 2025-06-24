<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'body' => $this->faker->paragraph(), // Pastikan ini ada untuk menghasilkan konten body
            'is_draft' => $this->faker->boolean(),
            'published_at' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'user_id' => \App\Models\User::factory(),
        ];
    }
}
