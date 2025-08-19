<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\PostTarget;
use App\Services\MastodonClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para publicar un post en Mastodon.
 */
class PublishToMastodon implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int ID del post */
    public int $postId;

    /** @var int ID del target */
    public int $targetId;

    /**
     * Crea una nueva instancia del job.
     *
     * @param int $postId ID del post
     * @param int $targetId ID del target
     */
    public function __construct(int $postId, int $targetId)
    {
        $this->postId = $postId;
        $this->targetId = $targetId;
    }

    /**
     * Ejecuta el job, publicando el post en Mastodon.
     *
     * @return void
     */
    public function handle(): void
    {
        $post = Post::find($this->postId);
        if (!$post || $post->canceled_at) {
            Log::info('Post cancelado o no encontrado.', ['post_id' => $this->postId]);
            return;
        }

        $target = PostTarget::find($this->targetId);
        if (!$target || !$target->socialAccount) {
            Log::warning('Target o cuenta social no encontrada.', ['target_id' => $this->targetId]);
            return;
        }

        try {
            $client = app(MastodonClient::class);

            $payload = [
                'status' => (string) $post->content,
                'visibility' => 'public',
                'scheduled_at' => null,
            ];

            $resp = $client->postStatus($target->socialAccount, $payload);

            $target->update([
                'status' => 'published',
                'provider_post_id' => $resp['id'] ?? null,
                'published_at' => now(),
                'error' => null,
            ]);

            $this->maybeMarkPostAsPublished($post);
            Log::info('Publicación en Mastodon exitosa.', [
                'post_id' => $post->id,
                'id' => $resp['id'] ?? null
            ]);
        } catch (\Throwable $e) {
            $target->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
            Log::error('Error al publicar en Mastodon.', [
                'post_id' => $this->postId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Marca el post como publicado si todos los targets están completos.
     *
     * @param Post $post
     * @return void
     */
    protected function maybeMarkPostAsPublished(Post $post): void
    {
        $total = $post->targets()->count();
        $published = $post->targets()->where('status', 'published')->count();
        $failed = $post->targets()->where('status', 'failed')->count();

        if ($published > 0 && $published + $failed === $total) {
            $post->update([
                'status' => 'published',
                'published_at' => now(),
            ]);
        }
    }
}