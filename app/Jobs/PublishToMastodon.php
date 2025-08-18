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

class PublishToMastodon implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $postId;
    public int $targetId;

    public function __construct(int $postId, int $targetId)
    {
        $this->postId = $postId;
        $this->targetId = $targetId;
    }

    public function handle(): void
    {
        $post = Post::find($this->postId);
        if (!$post || $post->canceled_at) {
            Log::info('PublishToMastodon: post cancelado o no encontrado', ['post_id' => $this->postId]);
            return;
        }

        $target = PostTarget::find($this->targetId);
        if (!$target || !$target->socialAccount) {
            Log::warning('PublishToMastodon: target/account no encontrado', ['target_id' => $this->targetId]);
            return;
        }

        try {
            /** @var MastodonClient $client */
            $client = app(MastodonClient::class);

            $payload = [
                'status'       => (string) $post->content,
                'visibility'   => 'public',     // ajusta si guardas otra visibilidad
                'scheduled_at' => null,         // aquí publicamos ya; la programación la hace PublishPost con delay()
            ];

            $resp = $client->postStatus($target->socialAccount, $payload);

            $target->update([
                'status'           => 'published',
                'provider_post_id' => $resp['id'] ?? null,
                'published_at'     => now(),
                'error'            => null,
            ]);

            $this->maybeMarkPostAsPublished($post);
            Log::info('PublishToMastodon: OK', ['post_id' => $post->id, 'id' => $resp['id'] ?? null]);

        } catch (\Throwable $e) {
            $target->update([
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ]);
            Log::error('PublishToMastodon: error', ['post_id' => $this->postId, 'error' => $e->getMessage()]);
        }
    }

    protected function maybeMarkPostAsPublished(Post $post): void
    {
        $total     = $post->targets()->count();
        $published = $post->targets()->where('status','published')->count();
        $failed    = $post->targets()->where('status','failed')->count();

        if ($published > 0 && $published + $failed === $total) {
            $post->update([
                'status'       => 'published',
                'published_at' => now(),
            ]);
        }
    }
}