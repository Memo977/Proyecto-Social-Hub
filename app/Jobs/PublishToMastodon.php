<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\SocialAccount;
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
    public array $target; // ej. ['provider' => 'mastodon', 'subreddit' => null, etc.]

    public function __construct(int $postId, array $target)
    {
        $this->postId = $postId;
        $this->target = $target;
    }

    public function handle(): void
    {
        $post = \App\Models\Post::find($this->postId);
        if (!$post) return;

        $targetId = $this->target['id'] ?? null;
        $target   = $targetId ? \App\Models\PostTarget::find($targetId) : null;
        if (!$target) return;

        $account = \App\Models\SocialAccount::find($this->target['social_account_id'] ?? null);
        if (!$account || $account->provider !== 'mastodon') return;

        $base = rtrim((string)$account->instance_domain, '/');
        if ($base === '') {
            Log::warning('PublishToMastodon: instance_domain vacío.');
            return;
        }

        $client = new \GuzzleHttp\Client([
            'headers'     => [
                'Authorization' => 'Bearer ' . $account->access_token,
                'Accept'        => 'application/json',
            ],
            'http_errors' => true,
            'timeout'     => 30,
        ]);

        try {
            $res = $client->post($base . '/api/v1/statuses', [
                'form_params' => [
                    'status' => $post->content,
                    // 'visibility' => 'public',
                    // 'sensitive'  => false,
                ],
            ]);
            $data = json_decode((string)$res->getBody(), true);

            // Actualiza el target como publicado
            $target->status = 'published';
            $target->provider_post_id = $data['id'] ?? null;
            $target->published_at = now();
            $target->error = null;
            $target->save();

            // Si ya no quedan pendientes, marca el Post
            $remaining = $post->targets()->where('status', '!=', 'published')->count();
            if ($remaining === 0) {
                $post->status = 'published';
                $post->published_at = now();
                $post->save();
            }

            Log::info('PublishToMastodon: publicado', [
                'post_id' => $post->id,
                'status_id' => $data['id'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $target->status = 'failed';
            $target->error  = $e->getMessage();
            $target->save();
            throw $e;
        }
    }
}