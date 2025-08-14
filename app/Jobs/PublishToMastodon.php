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
use App\Services\MastodonClient;

class PublishToMastodon implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $postId;
    public array $target; // ej. ['id' => ..., 'provider' => 'mastodon', 'social_account_id' => ...]

    public function __construct(int $postId, array $target)
    {
        $this->postId = $postId;
        $this->target = $target;
    }

    public function handle(): void
    {
        $post = Post::find($this->postId);
        if (!$post) {
            Log::warning('PublishToMastodon: Post no encontrado', ['post_id' => $this->postId]);
            return;
        }

        $targetId = $this->target['id'] ?? null;
        $target   = $targetId ? \App\Models\PostTarget::find($targetId) : null;
        if (!$target) {
            Log::warning('PublishToMastodon: PostTarget no encontrado', ['target_id' => $targetId]);
            return;
        }

        // Idempotencia básica: si ya se publicó, no lo repitamos
        if ($target->status === 'published' && $target->provider_post_id) {
            Log::info('PublishToMastodon: target ya publicado, omitiendo', [
                'post_id' => $post->id,
                'target_id' => $target->id,
            ]);
            return;
        }

        $account = SocialAccount::find($this->target['social_account_id'] ?? null);
        if (!$account || $account->provider !== 'mastodon') {
            Log::warning('PublishToMastodon: SocialAccount inválido o no es mastodon', [
                'social_account_id' => $this->target['social_account_id'] ?? null,
            ]);
            return;
        }

        if (empty($account->instance_domain)) {
            Log::warning('PublishToMastodon: instance_domain vacío.');
            return;
        }

        $client = new MastodonClient();

        try {
            // Publica (el MastodonClient refresca el token si ya venció)
            $data = $client->postStatus($account, [
                'status' => $post->content,
                // 'visibility' => 'public',
                // 'scheduled_at' => null,
                // 'sensitive' => false,
            ]);

            // Actualiza el target como publicado
            $target->status = 'published';
            $target->provider_post_id = $data['id'] ?? null;
            $target->published_at = now();
            $target->error = null;
            $target->save();

            // Si ya no quedan pendientes, marca el Post como publicado
            $remaining = $post->targets()->where('status', '!=', 'published')->count();
            if ($remaining === 0) {
                $post->status = 'published';
                $post->published_at = now();
                $post->save();
            }

            Log::info('PublishToMastodon: publicado', [
                'post_id'   => $post->id,
                'target_id' => $target->id,
                'status_id' => $data['id'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $target->status = 'failed';
            $target->error  = $e->getMessage();
            $target->save();

            Log::error('PublishToMastodon: error al publicar', [
                'post_id'   => $post->id,
                'target_id' => $target->id,
                'error'     => $e->getMessage(),
            ]);

            throw $e; // re-lanzamos para que el worker gestione reintentos si están configurados
        }
    }
}