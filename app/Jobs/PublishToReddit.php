<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\PostTarget;
use App\Models\SocialAccount;
use App\Services\RedditClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishToReddit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public $postId;

    /** @var int */
    public $targetId;

    public function __construct(int $postId, int $targetId)
    {
        $this->postId  = $postId;
        $this->targetId = $targetId;
    }

    public function handle(): void
    {
        $post = Post::find($this->postId);
        if (!$post) {
            Log::error('PublishToReddit: Post no encontrado', ['post_id' => $this->postId]);
            return;
        }

        $target = PostTarget::find($this->targetId);
        if (!$target) {
            Log::error('PublishToReddit: Target no encontrado', ['target_id' => $this->targetId]);
            return;
        }

        $account = SocialAccount::find($target->social_account_id);
        if (!$account || $account->provider !== 'reddit') {
            Log::error('PublishToReddit: Cuenta de Reddit no válida', ['account_id' => $target->social_account_id]);
            return;
        }

        // Verificar que el token no haya expirado
        if ($account->expires_at && $account->expires_at->isPast()) {
            $target->status = 'failed';
            $target->error  = 'Token de acceso expirado';
            $target->save();
            Log::error('PublishToReddit: Token expirado');
            return;
        }

        // ----- Datos obligatorios para Reddit -----
        $meta  = $post->meta['reddit'] ?? [];
        $sr    = trim($meta['subreddit'] ?? '');
        $title = trim((string)($meta['title'] ?? $post->title ?? ''));
        $kind  = (string)($meta['kind'] ?? 'self');     // 'self' | 'link' (o 'image' si detectamos)
        $url   = trim((string)($meta['url'] ?? $post->link ?? ''));

        // Si viene 'link' pero la URL es imagen directa, publicamos como 'image'
        $isDirectImage = $url !== '' && preg_match(
            '/\.(jpe?g|png|gif|webp)$/i',
            parse_url($url, PHP_URL_PATH) ?? ''
        );
        if ($kind === 'link' && $isDirectImage) {
            $kind = 'image';
        }

        // Validación
        if ($sr === '' || $title === '' || !in_array($kind, ['self', 'link', 'image'], true)) {
            $msg = 'Faltan datos obligatorios para Reddit: subreddit, title o kind.';
            Log::warning('PublishToReddit: ' . $msg, compact('sr', 'title', 'kind', 'url'));
            $target->status = 'failed';
            $target->error  = $msg;
            $target->save();
            return;
        }

        if ($kind === 'link' && $url === '') {
            $msg = 'URL es obligatoria para posts de tipo link.';
            Log::warning('PublishToReddit: ' . $msg);
            $target->status = 'failed';
            $target->error  = $msg;
            $target->save();
            return;
        }

        if ($kind === 'image' && $url === '') {
            $msg = 'URL es obligatoria para posts de tipo image.';
            Log::warning('PublishToReddit: ' . $msg);
            $target->status = 'failed';
            $target->error  = $msg;
            $target->save();
            return;
        }

        try {
            // Payload según tipo
            $payload = [
                'sr'       => $sr,
                'title'    => $title,
                'kind'     => $kind,       // 'self' | 'link' | 'image'
                'api_type' => 'json',      // respuesta JSON
            ];

            if ($kind === 'self') {
                $payload['text'] = $post->content ?? '';
            } else {
                // Para 'link' y 'image' enviamos URL
                $payload['url'] = $url;
            }

            Log::info('PublishToReddit: Enviando payload', [
                'post_id'   => $post->id,
                'target_id' => $target->id,
                'payload'   => $payload
            ]);

            // Cliente HTTP
            $client = new \GuzzleHttp\Client([
                'base_uri' => 'https://oauth.reddit.com',
                'headers'  => [
                    'Authorization' => 'Bearer ' . $account->access_token,
                    'User-Agent'    => config('services.reddit.user_agent', 'SocialHub/1.0'),
                    'Accept'        => 'application/json',
                ],
                'http_errors' => false,
                'timeout'     => 30,
            ]);

            // Submit
            $response   = $client->post('/api/submit', ['form_params' => $payload]);
            $statusCode = $response->getStatusCode();
            $body       = (string) $response->getBody();

            Log::info('PublishToReddit: Respuesta de Reddit', [
                'status_code' => $statusCode,
                'body'        => $body
            ]);

            if ($statusCode !== 200) {
                throw new \Exception("Error HTTP {$statusCode}: {$body}");
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Respuesta JSON inválida de Reddit: ' . json_last_error_msg());
            }

            // Errores de Reddit
            if (isset($data['json']['errors']) && !empty($data['json']['errors'])) {
                $errors   = $data['json']['errors'];
                $errorMsg = 'Errores de Reddit: ' . json_encode($errors);
                throw new \Exception($errorMsg);
            }

            // Confirmar creación
            if (!isset($data['json']['data']['id'])) {
                throw new \Exception('Reddit no devolvió ID del post creado');
            }

            $redditId  = $data['json']['data']['id'];
            $permalink = $data['json']['data']['url'] ?? null;

            // Marcar como publicado
            $target->status           = 'published';
            $target->provider_post_id = $redditId;
            $target->published_at     = now();
            $target->error            = null;
            $target->save();

            // Si todos los targets están publicados, marcar Post
            $remaining = $post->targets()->where('status', '!=', 'published')->count();
            if ($remaining === 0) {
                $post->status       = 'published';
                $post->published_at = now();
                $post->save();
            }

            Log::info('PublishToReddit: Publicado exitosamente', [
                'post_id'   => $post->id,
                'reddit_id' => $redditId,
                'subreddit' => $sr,
                'permalink' => $permalink
            ]);
        } catch (\Throwable $e) {
            Log::error('PublishToReddit: Error al publicar', [
                'post_id'   => $post->id ?? null,
                'target_id' => $target->id ?? null,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString()
            ]);

            $target->status = 'failed';
            $target->error  = $e->getMessage();
            $target->save();

            throw $e;
        }
    }
}