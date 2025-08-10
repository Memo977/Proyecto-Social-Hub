<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        // Valores coherentes con tu migración/modelo:
        // status: draft | queued | scheduled | published
        // mode:   now   | queue  | at
        $status = $this->faker->randomElement(['draft', 'queued', 'scheduled', 'published']);

        $mode = match ($status) {
            'scheduled' => 'at',
            'queued'    => 'queue',
            default     => 'now',
        };

        return [
            'user_id'      => User::factory(), // crea un usuario si no existe
            'content'      => $this->faker->paragraphs(2, true),
            'media_url'    => $this->faker->optional(0.5)->imageUrl(800, 450, 'cats'),
            'status'       => $status,
            'mode'         => $mode,
            'scheduled_at' => $mode === 'at'
                ? $this->faker->dateTimeBetween('+1 hour', '+7 days')
                : null,
            'published_at' => $status === 'published'
                ? $this->faker->dateTimeBetween('-7 days', 'now')
                : null,
        ];
    }
}