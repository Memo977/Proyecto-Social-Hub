<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\PostTarget;
use App\Services\RedditClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Job para publicar un post en Reddit.
 */
class PublishToReddit implements ShouldQueue
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
     * Ejecuta el job, publicando el post en Reddit.
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

        if ($target->status !== 'pending') {
            Log::info('Target no está en estado pendiente, se omitirá.', [
                'target_id' => $target->id,
                'status' => $target->status
            ]);
            return;
        }

        $account = $target->socialAccount;

        $subreddit = data_get($post->meta, 'reddit.subreddit')
            ?? data_get($account->meta, 'subreddit')
            ?? data_get($account->meta, 'sr')
            ?? null;

        if (!$subreddit) {
            $target->update([
                'status' => 'failed',
                'error' => 'No se especificó un subreddit en los metadatos del post o de la cuenta.'
            ]);
            Log::warning('No se especificó un subreddit.', [
                'post_id' => $post->id,
                'account_id' => $account->id
            ]);
            return;
        }

        try {
            $client = app(\App\Services\RedditClient::class);

            $title = $post->title ?: Str::limit((string) $post->content, 250);
            $hasLink = !empty($post->link);

            $payload = [
                'sr' => $subreddit,
                'title' => $title,
                'kind' => $hasLink ? 'link' : 'self',
                'text' => $hasLink ? null : (string) $post->content,
                'url' => $hasLink ? (string) $post->link : null,
                'sendreplies' => true,
                'nsfw' => data_get($post->meta, 'nsfw', false),
                'spoiler' => data_get($post->meta, 'spoiler', false),
            ];

            $resp = $client->submitPost($account, $payload);

            $target->update([
                'status' => 'published',
                'provider_post_id' => $resp['id'] ?? null,
                'published_at' => now(),
                'error' => null,
            ]);

            $this->maybeMarkPostAsPublished($post);
            Log::info('Publicación en Reddit exitosa.', [
                'post_id' => $post->id,
                'id' => $resp['id'] ?? null
            ]);
        } catch (\Throwable $e) {
            $target->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
            Log::error('Error al publicar en Reddit.', [
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

    /**
     * Normaliza el formato del subreddit.
     * Ejemplo: "r/test", "/r/test", "https://reddit.com/r/test" => "test"
     *          "u/usuario" o "https://reddit.com/u/usuario" => "u_usuario"
     *
     * @param string|null $sr
     * @return string|null
     */
    private function normalizeSr(?string $sr): ?string
    {
        if (!$sr) {
            return null;
        }

        $sr = trim($sr);
        $sr = preg_replace('#^https?://(www\.)?reddit\.com/#i', '', $sr);
        $sr = ltrim($sr, '/');

        if (str_starts_with($sr, 'r/')) {
            $sr = substr($sr, 2);
        } elseif (str_starts_with($sr, '/r/')) {
            $sr = substr($sr, 3);
        }

        if (str_starts_with($sr, 'u/')) {
            $user = substr($sr, 2);
            $user = ltrim($user, '/');
            return $user ? 'u_' . $user : null;
        }

        return $sr !== '' ? $sr : null;
    }
}