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

class PublishToReddit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $postId;
    public int $targetId;

    public function __construct(int $postId, int $targetId)
    {
        $this->postId   = $postId;
        $this->targetId = $targetId;
    }

    public function handle(): void
    {
        $post = Post::find($this->postId);
        if (!$post || $post->canceled_at) {
            Log::info('PublishToReddit: post cancelado o no encontrado', ['post_id' => $this->postId]);
            return;
        }

        $target = PostTarget::find($this->targetId);
        if (!$target || !$target->socialAccount) {
            Log::warning('PublishToReddit: target/account no encontrado', ['target_id' => $this->targetId]);
            return;
        }

        // ✅ 1) Evita reprocesar si ya cambió de estado (seguro y no rompe nada)
        if ($target->status !== 'pending') {
            Log::info('PublishToReddit: skip; target no está pending', [
                'target_id' => $target->id,
                'status'    => $target->status,
            ]);
            return;
        }

        $account = $target->socialAccount;

        // ✅ 2) Lee primero del meta del post; si falta, cae al meta de la cuenta (compatible con lo anterior)
        $subreddit = data_get($post->meta, 'reddit.subreddit')
            ?? data_get($account->meta, 'subreddit')
            ?? data_get($account->meta, 'sr')
            ?? null;

        if (!$subreddit) {
            $target->update([
                'status' => 'failed',
                'error'  => 'Falta subreddit (ni en el post.meta ni en la cuenta).',
            ]);
            Log::warning('PublishToReddit: falta subreddit', [
                'post_id'    => $post->id,
                'account_id' => $account->id,
            ]);
            return;
        }

        try {
            /** @var \App\Services\RedditClient $client */
            $client = app(\App\Services\RedditClient::class);

            // Mantén tu lógica: si hay link => 'link', si no => 'self'
            $title   = $post->title ?: \Illuminate\Support\Str::limit((string) $post->content, 250);
            $hasLink = !empty($post->link);

            $payload = [
                'sr'           => $subreddit,
                'title'        => $title,
                'kind'         => $hasLink ? 'link' : 'self',
                'text'         => $hasLink ? null : (string) $post->content,
                'url'          => $hasLink ? (string) $post->link : null,
                'sendreplies'  => true,
                'nsfw'         => data_get($post->meta, 'nsfw', false),
                'spoiler'      => data_get($post->meta, 'spoiler', false),
            ];

            $resp = $client->submitPost($account, $payload);

            $target->update([
                'status'           => 'published',
                'provider_post_id' => $resp['id'] ?? null,
                'published_at'     => now(),
                'error'            => null,
            ]);

            $this->maybeMarkPostAsPublished($post);
            Log::info('PublishToReddit: OK', ['post_id' => $post->id, 'id' => $resp['id'] ?? null]);
        } catch (\Throwable $e) {
            $target->update([
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ]);
            Log::error('PublishToReddit: error', ['post_id' => $this->postId, 'error' => $e->getMessage()]);
        }
    }

    protected function maybeMarkPostAsPublished(Post $post): void
    {
        $total     = $post->targets()->count();
        $published = $post->targets()->where('status', 'published')->count();
        $failed    = $post->targets()->where('status', 'failed')->count();

        if ($published > 0 && $published + $failed === $total) {
            $post->update([
                'status'       => 'published',
                'published_at' => now(),
            ]);
        }
    }

    /** Normaliza:
     *  "r/test", "/r/test", "https://reddit.com/r/test" => "test"
     *  "u/usuario" o "https://reddit.com/u/usuario"     => "u_usuario"
     */
    private function normalizeSr(?string $sr): ?string
    {
        if (!$sr) return null;
        $sr = trim($sr);

        // Quitar dominio si viene completo
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