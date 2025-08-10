<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Post;
use App\Models\User;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        // Aseguramos que haya al menos un usuario
        $user = User::first() ?? User::factory()->create();

        // Crear 20 posts para ese usuario
        Post::factory()->count(20)->create([
            'user_id' => $user->id,
        ]);
    }
}