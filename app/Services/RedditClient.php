<?php

namespace App\Services;

use App\Models\SocialAccount;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para interactuar con la API de Reddit.
 */
class RedditClient
{
    /** @var Client Cliente HTTP para realizar solicitudes. */
    private Client $http;

    /** @var string ID del cliente de Reddit. */
    private string $clientId;

    /** @var string Secreto del cliente de Reddit. */
    private string $clientSecret;

    /** @var string User-Agent para las solicitudes. */
    private string $userAgent;

    /**
     * Crea una nueva instancia del cliente.
     */
    public function __construct()
    {
        $this->clientId = config('services.reddit.client_id');
        $this->clientSecret = config('services.reddit.client_secret');
        $this->userAgent = env('REDDIT_USER_AGENT', 'SocialHub/1.0 (Laravel)');
        $this->http = new Client([
            'timeout' => 20,
            'headers' => ['User-Agent' => $this->userAgent],
        ]);
    }

    /**
     * Asegura que el token de acceso sea válido, refrescándolo si es necesario.
     *
     * @param SocialAccount $account Cuenta social de Reddit.
     * @return void
     */
    public function ensureValidToken(SocialAccount $account): void
    {
        if (!$account->expires_at || $account->expires_at->isFuture()) {
            return;
        }

        if (!$account->refresh_token) {
            return;
        }

        $resp = $this->http->post('https://www.reddit.com/api/v1/access_token', [
            'auth' => [$this->clientId, $this->clientSecret],
            'form_params' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $account->refresh_token,
            ],
        ])->getBody()->getContents();

        $data = json_decode($resp, true);

        $account->access_token = $data['access_token'] ?? $account->access_token;
        if (isset($data['expires_in'])) {
            $account->expires_at = Carbon::now()->addSeconds((int) $data['expires_in']);
        }
        $account->save();
    }

    /**
     * Publica un post en Reddit (self o link).
     *
     * @param SocialAccount $account Cuenta social de Reddit.
     * @param array $payload Datos del post a publicar.
     * @return array Respuesta de la API.
     */
    public function submitPost(SocialAccount $account, array $payload): array
    {
        $this->ensureValidToken($account);

        $params = [
            'sr' => Arr::get($payload, 'sr'),
            'title' => Arr::get($payload, 'title'),
            'kind' => Arr::get($payload, 'kind'),
            'sendreplies' => Arr::get($payload, 'sendreplies', true),
            'resubmit' => true,
            'api_type' => 'json',
        ];

        if ($params['kind'] === 'self') {
            $params['text'] = Arr::get($payload, 'text');
        } elseif ($params['kind'] === 'link') {
            $params['url'] = Arr::get($payload, 'url');
        }

        if (Arr::get($payload, 'nsfw')) {
            $params['nsfw'] = 'true';
        }
        if (Arr::get($payload, 'spoiler')) {
            $params['spoiler'] = 'true';
        }

        $resp = $this->http->post('https://oauth.reddit.com/api/submit', [
            'headers' => [
                'Authorization' => 'bearer ' . $account->access_token,
            ],
            'form_params' => $params,
        ])->getBody()->getContents();

        $data = json_decode($resp, true);

        if (!empty($data['json']['errors'])) {
            Log::warning('Errores al publicar post en Reddit.', $data['json']['errors']);
            throw new \RuntimeException('Error al publicar post en Reddit.');
        }

        $thing = $data['json']['data']['name'] ?? null;
        return [
            'id' => $thing,
            'url' => $data['json']['data']['url'] ?? null,
            'full' => $data,
        ];
    }

    /**
     * Sube una imagen a Reddit para usarla en un post.
     *
     * @param SocialAccount $account Cuenta social de Reddit.
     * @param string $mediaUrl URL de la imagen a subir.
     * @return string ID del asset subido.
     */
    public function uploadImage(SocialAccount $account, string $mediaUrl): string
    {
        $this->ensureValidToken($account);

        $resp = $this->http->post('https://oauth.reddit.com/api/v1/media/asset.json', [
            'headers' => [
                'Authorization' => 'bearer ' . $account->access_token,
            ],
            'form_params' => [
                'filepath' => basename($mediaUrl),
                'mimetype' => 'image/jpeg',
            ],
        ])->getBody()->getContents();

        $data = json_decode($resp, true)['args'] ?? [];

        $uploadUrl = $data['action'] ?? null;
        $fields = $data['fields'] ?? [];

        if (!$uploadUrl || empty($fields)) {
            throw new \RuntimeException('Fallo al obtener URL de subida para imagen en Reddit.');
        }

        $binary = file_get_contents($mediaUrl);
        if ($binary === false) {
            throw new \RuntimeException('Fallo al descargar imagen para subida a Reddit.');
        }

        $filename = basename($mediaUrl);
        $multipart = [];

        foreach ($fields as $f) {
            $multipart[] = ['name' => $f['name'], 'contents' => $f['value']];
        }
        $multipart[] = ['name' => 'file', 'contents' => $binary, 'filename' => $filename];

        (new Client(['timeout' => 30]))->post($uploadUrl, ['multipart' => $multipart]);

        return $data['asset_id'] ?? throw new \RuntimeException('No se obtuvo asset_id después de subida a Reddit.');
    }

    /**
     * Publica un self-post con RTJSON en Reddit.
     *
     * @param SocialAccount $account Cuenta social de Reddit.
     * @param string $subreddit Subreddit destino.
     * @param string $title Título del post.
     * @param array $richDocument Documento RTJSON.
     * @param array $opts Opciones adicionales.
     * @return array Respuesta de la API.
     */
    public function submitSelfWithRichtext(SocialAccount $account, string $subreddit, string $title, array $richDocument, array $opts = []): array
    {
        $this->ensureValidToken($account);

        $params = array_merge([
            'sr' => $subreddit,
            'title' => $title,
            'kind' => 'self',
            'submit_type' => 'rtjson',
            'richtext_json' => json_encode(['document' => $richDocument]),
            'sendreplies' => true,
            'resubmit' => true,
            'api_type' => 'json',
        ], $opts);

        if (!empty($opts['nsfw'])) {
            $params['nsfw'] = 'true';
        }
        if (!empty($opts['spoiler'])) {
            $params['spoiler'] = 'true';
        }

        $resp = $this->http->post('https://oauth.reddit.com/api/submit', [
            'headers' => [
                'Authorization' => 'bearer ' . $account->access_token,
                'User-Agent' => $this->userAgent,
            ],
            'form_params' => $params,
        ])->getBody()->getContents();

        $data = json_decode($resp, true);

        if (!empty($data['json']['errors'])) {
            Log::warning('Errores al publicar self-post con RTJSON en Reddit.', $data['json']['errors']);
            throw new \RuntimeException('Error al publicar self-post con RTJSON en Reddit.');
        }

        $thing = $data['json']['data']['name'] ?? null;
        return [
            'id' => $thing,
            'url' => $data['json']['data']['url'] ?? null,
            'full' => $data,
        ];
    }
}