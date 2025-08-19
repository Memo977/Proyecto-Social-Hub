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

/**
 * Job para procesar la publicación de un post en redes sociales.
 */
class PublishPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int ID del post a publicar */
    public int $postId;

    /** @var int|null ID del target específico (opcional) */
    public int|null $targetId;

    /**
     * Crea una nueva instancia del job.
     *
     * @param int $postId ID del post
     * @param int|null $targetId ID del target (opcional)
     */
    public function __construct(int $postId, ?int $targetId = null)
    {
        $this->postId = $postId;
        $this->targetId = $targetId;
    }

    /**
     * Define los middleware para el job, aplicando límites de tasa.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [new RateLimited('social-publish')];
    }

    /**
     * Ejecuta el job, publicando el post en las redes sociales correspondientes.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $post = Post::findOrFail($this->postId);

            if ($post->canceled_at) {
                Log::info('Publicación cancelada, no se procesará.', [
                    'post_id' => $this->postId
                ]);
                return;
            }

            if ($post->scheduled_at && $post->scheduled_at->isFuture()) {
                Log::info('Publicación programada para el futuro, se ignorará hasta la fecha programada.', [
                    'post_id' => $this->postId,
                    'scheduled_at' => $post->scheduled_at
                ]);
                return;
            }

            if ($this->targetId) {
                $target = PostTarget::findOrFail($this->targetId);
                $account = $target->socialAccount;

                if (!$account) {
                    Log::warning('Target sin cuenta social asociada.', [
                        'target_id' => $this->targetId
                    ]);
                    return;
                }

                if ($account->provider === 'mastodon') {
                    dispatch(new \App\Jobs\PublishToMastodon($post->id, $target->id));
                } elseif ($account->provider === 'reddit') {
                    dispatch(new \App\Jobs\PublishToReddit($post->id, $target->id));
                }

                return;
            }

            foreach ($post->targets as $t) {
                $account = $t->socialAccount;
                if (!$account) {
                    continue;
                }

                if ($account->provider === 'mastodon') {
                    dispatch(new \App\Jobs\PublishToMastodon($post->id, $t->id));
                } elseif ($account->provider === 'reddit') {
                    dispatch(new \App\Jobs\PublishToReddit($post->id, $t->id));
                }
            }
        } catch (ModelNotFoundException $e) {
            Log::error('Post o target no encontrado.', [
                'post_id' => $this->postId,
                'target_id' => $this->targetId,
                'error' => $e->getMessage()
            ]);
            $this->fail($e);
        } catch (\Throwable $e) {
            Log::error('Error inesperado al procesar la publicación.', [
                'post_id' => $this->postId,
                'target_id' => $this->targetId,
                'error' => $e->getMessage()
            ]);
            $this->fail($e);
        }
    }
}