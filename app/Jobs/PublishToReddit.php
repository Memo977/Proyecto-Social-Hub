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
        $this->postId   = $postId;
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

        // Verificar expiración (el cliente también refresca si aplica)
        if ($account->expires_at && $account->expires_at->isPast() && !$account->refresh_token) {
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
        $kind  = (string)($meta['kind'] ?? 'self');     // 'self' | 'link' (image ya no)

        // Compatibilidad: si viene 'image', lo mapeamos a 'self'
        if ($kind === 'image') {
            $kind = 'self';
        }

        $url = trim((string)($meta['url'] ?? $post->link ?? ''));

        // Validación
        if ($sr === '' || $title === '' || !in_array($kind, ['self', 'link'], true)) {
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

        try {
            if ($kind === 'self') {
                $this->handleSelfPost($post, $target, $account, $sr, $title);
            } else {
                $this->handleLinkPost($post, $target, $account, $sr, $title, $url);
            }
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

    /**
     * Publica self-post. Si hay media_url (imagen), la embebe con RTJSON.
     */
    private function handleSelfPost(Post $post, PostTarget $target, SocialAccount $account, string $sr, string $title): void
    {
        /** @var RedditClient $reddit */
        $reddit = app(RedditClient::class);

        $text = trim((string)($post->content ?? ''));
        $mediaUrl = trim((string)($post->media_url ?? ''));

        $imageData = null;
        $mimeType  = null;

        if ($mediaUrl !== '') {
            Log::info('PublishToReddit: intentando usar media_url para self con imagen', [
                'post_id' => $post->id,
                'url'     => $mediaUrl,
            ]);

            $imageData = $this->downloadImage($mediaUrl);
            if ($imageData) {
                $finfo    = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($imageData) ?: null;
            }
        }

        // Si logramos descargar y el MIME es imagen -> asset + RTJSON
        if ($imageData && $mimeType && str_starts_with($mimeType, 'image/')) {
            $extension = match ($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
                default      => 'jpg'
            };
            $filename = 'reddit_image_' . time() . '.' . $extension;

            Log::info('PublishToReddit: subiendo media asset (self rtjson)', [
                'filename'  => $filename,
                'mime_type' => $mimeType,
                'size'      => strlen($imageData),
            ]);

            $assetId = $reddit->uploadMediaAsset($account, $filename, $mimeType, $imageData);

            // Pequeña espera para evitar que el asset recién subido aún no esté listo
            usleep(400_000); // 0.4s

            // Construye el documento RTJSON: texto (si hay) + imagen
            $document = [];
            if ($text !== '') {
                $document[] = [
                    'e' => 'par',
                    'c' => [['e' => 'text', 't' => $text]],
                ];
            }
            $document[] = [
                'e' => 'par',
                'c' => [['e' => 'media', 'id' => $assetId]],
            ];

            $data = $reddit->submitSelfWithRichtext($account, $sr, $title, $document);

            $this->markAsPublished($post, $target, $sr, $data);
            return;
        }

        // Self sin imagen (o media_url no era imagen) -> submit clásico con 'text'
        $payload = [
            'sr'          => $sr,
            'title'       => $title,
            'kind'        => 'self',
            'text'        => $text,
            'sendreplies' => true,
        ];

        Log::info('PublishToReddit: self simple (texto)', [
            'post_id' => $post->id,
            'len'     => strlen($text),
            'media_url' => $mediaUrl,
            'mime'    => $mimeType,
        ]);

        $data = $reddit->submitPost($account, $payload);

        $this->markAsPublished($post, $target, $sr, $data);
    }

    private function handleLinkPost(Post $post, PostTarget $target, SocialAccount $account, string $sr, string $title, string $url): void
    {
        /** @var RedditClient $reddit */
        $reddit = app(RedditClient::class);

        $payload = [
            'sr'          => $sr,
            'title'       => $title,
            'kind'        => 'link',
            'url'         => $url,
            'sendreplies' => true,
        ];

        Log::info('PublishToReddit: link', [
            'post_id' => $post->id,
            'url'     => $url,
        ]);

        $data = $reddit->submitPost($account, $payload);

        $this->markAsPublished($post, $target, $sr, $data);
    }

    private function markAsPublished(Post $post, PostTarget $target, string $sr, array $data): void
    {
        $redditId  = $data['id'] ?? null;
        $permalink = $data['url'] ?? null;

        if (!$redditId) {
            throw new \Exception('Reddit no devolvió ID del post creado');
        }

        // Marcar como publicado
        $target->status = 'published';
        $target->provider_post_id = $redditId;
        $target->published_at = now();
        $target->error = null;
        $target->save();

        // Si todos los targets están publicados, marcar Post
        $remaining = $post->targets()->where('status', '!=', 'published')->count();
        if ($remaining === 0) {
            $post->status = 'published';
            $post->published_at = now();
            $post->save();
        }

        Log::info('PublishToReddit: Publicado exitosamente', [
            'post_id'   => $post->id,
            'reddit_id' => $redditId,
            'subreddit' => $sr,
            'permalink' => $permalink
        ]);
    }

    private function downloadImage(string $url): ?string
    {
        try {
            $client = new \GuzzleHttp\Client(['timeout' => 30, 'headers' => ['User-Agent' => 'SocialHub/1.0 (image-fetch)']]);
            $response = $client->get($url);

            if ($response->getStatusCode() === 200) {
                return (string) $response->getBody();
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('PublishToReddit: Error descargando imagen', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function isImageUrl(?string $url): bool
    {
        if (!$url) return false;
        $clean = explode('?', $url)[0];
        $ext = strtolower(pathinfo($clean, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }
}