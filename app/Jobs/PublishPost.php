<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\SocialAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $postId;

    /**
     * @param  int  $postId
     */
    public function __construct(int $postId)
    {
        $this->postId = $postId;
        // puedes ajustar prioridad/cola si quieres:
        // $this->onQueue('default')->onConnection('database');
    }

    /**
     * Opcional: aplicar middlewares (ej. rate limit por proveedor).
     */
    public function middleware(): array
    {
        return [
            // RateLimited::class, // si más adelante usas limitadores con nombre
        ];
    }

    public function handle(): void
    {
        try {
            /** @var Post $post */
            $post = Post::query()->with('targets')->findOrFail($this->postId);

            // Aquí solo registramos y delegamos por proveedor.
            // En el commit 16 implementaremos la publicación real.
            foreach ($post->targets as $target) {
                $provider = $target->provider; // 'mastodon' | 'reddit' | etc.

                if ($provider === 'reddit') {
                    PublishToReddit::dispatch($post->id, $target->toArray());
                }

                if ($provider === 'mastodon') {
                    PublishToMastodon::dispatch($post->id, $target->toArray());
                }
            }

            Log::info('PublishPost encoló sub-jobs por proveedor', [
                'post_id' => $post->id,
                'targets' => $post->targets->pluck('provider'),
            ]);
        } catch (ModelNotFoundException $e) {
            $this->fail($e);
        }
    }
}