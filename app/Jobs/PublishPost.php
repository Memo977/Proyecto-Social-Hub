<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\PostTarget;
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
    public ?int $targetId;

    public function __construct(int $postId, ?int $targetId = null)
    {
        $this->postId = $postId;
        $this->targetId = $targetId;
    }

    public function middleware(): array
    {
        // Si usas rate limit por usuario, puedes personalizar aquí
        return [new RateLimited('social-publish')];
    }

    public function handle(): void
    {
        try {
            /** @var Post $post */
            $post = Post::findOrFail($this->postId);

            // SALIR si cancelado
            if ($post->canceled_at) {
                Log::info('PublishPost: post cancelado, no se publicará', ['post_id' => $this->postId]);
                return;
            }

            // Opcional: si quieres respetar scheduled_at > now
            if ($post->scheduled_at && $post->scheduled_at->isFuture()) {
                Log::info('PublishPost: scheduled en futuro, se ignorará hasta su tiempo', [
                    'post_id' => $this->postId, 'scheduled_at' => $post->scheduled_at
                ]);
                return;
            }

            // Si tienes targets por red
            if ($this->targetId) {
                /** @var PostTarget $target */
                $target = PostTarget::findOrFail($this->targetId);

                // Aquí normalmente despachas PublishToMastodon o PublishToReddit según cuenta
                $account = $target->socialAccount;
                if (!$account) {
                    Log::warning('PublishPost: target sin social account', ['target_id' => $this->targetId]);
                    return;
                }

                // Ejemplo: delegar según provider
                if ($account->provider === 'mastodon') {
                    dispatch(new \App\Jobs\PublishToMastodon($post->id, $target->id));
                } elseif ($account->provider === 'reddit') {
                    dispatch(new \App\Jobs\PublishToReddit($post->id, $target->id));
                }

                return;
            }

            // Si NO se pasa targetId, publica a todos los targets asociados
            foreach ($post->targets as $t) {
                $account = $t->socialAccount;
                if (!$account) continue;

                if ($account->provider === 'mastodon') {
                    dispatch(new \App\Jobs\PublishToMastodon($post->id, $t->id));
                } elseif ($account->provider === 'reddit') {
                    dispatch(new \App\Jobs\PublishToReddit($post->id, $t->id));
                }
            }

        } catch (ModelNotFoundException $e) {
            Log::error('PublishPost: Post/Target no encontrado', [
                'post_id' => $this->postId,
                'target_id' => $this->targetId,
                'error' => $e->getMessage()
            ]);
            $this->fail($e);
        } catch (\Throwable $e) {
            Log::error('PublishPost: Error inesperado', [
                'post_id' => $this->postId,
                'target_id' => $this->targetId,
                'error' => $e->getMessage(),
            ]);
            $this->fail($e);
        }
    }
}