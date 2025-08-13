<?php

namespace App\Services;

use App\Models\SocialAccount;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class RedditClient
{
    private Client $http;
    private string $clientId;
    private string $clientSecret;
    private string $userAgent;

    public function __construct()
    {
        $this->clientId     = config('services.reddit.client_id');
        $this->clientSecret = config('services.reddit.client_secret');
        $this->userAgent    = env('REDDIT_USER_AGENT', 'SocialHub/1.0 (Laravel)');
        $this->http = new Client([
            'timeout' => 20,
            'headers' => ['User-Agent' => $this->userAgent],
        ]);
    }

    public function ensureValidToken(SocialAccount $account): void
    {
        // Si no hay expires_at o aún es válido, no hacemos nada
        if (!$account->expires_at || $account->expires_at->isFuture()) {
            return;
        }
        if (!$account->refresh_token) {
            return; // no podemos refrescar, el usuario deberá reconectar
        }

        $resp = $this->http->post('https://www.reddit.com/api/v1/access_token', [
            'auth' => [$this->clientId, $this->clientSecret],
            'form_params' => [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $account->refresh_token,
            ],
        ])->getBody()->getContents();

        $data = json_decode($resp, true);

        $account->access_token = $data['access_token'] ?? $account->access_token;
        if (isset($data['expires_in'])) {
            $account->expires_at = Carbon::now()->addSeconds((int) $data['expires_in']);
        }
        // Reddit no devuelve refresh_token de nuevo; se mantiene el existente
        $account->save();
    }

    /**
     * Envía un post 'self' o 'link' simple (sin RTJSON).
     * - self: usa 'text'
     * - link: usa 'url'
     */
    public function submitPost(SocialAccount $account, array $payload): array
    {
        $this->ensureValidToken($account);

        // Construir parámetros para /api/submit
        $params = [
            'sr'          => Arr::get($payload, 'sr'),
            'title'       => Arr::get($payload, 'title'),
            'kind'        => Arr::get($payload, 'kind', 'self'), // self|link
            'api_type'    => 'json',
            'sendreplies' => Arr::get($payload, 'sendreplies', true) ? 'true' : 'false',
        ];

        if ($params['kind'] === 'self') {
            $params['text'] = Arr::get($payload, 'text', '');
        } else { // link
            $params['url']  = Arr::get($payload, 'url', '');
        }

        if (Arr::get($payload, 'nsfw', false))    $params['nsfw']    = 'true';
        if (Arr::get($payload, 'spoiler', false)) $params['spoiler'] = 'true';

        $resp = $this->http->post('https://oauth.reddit.com/api/submit', [
            'headers' => [
                'Authorization' => 'bearer '.$account->access_token,
                'User-Agent'    => $this->userAgent,
            ],
            'form_params' => $params,
        ])->getBody()->getContents();

        $data = json_decode($resp, true);

        if (!empty($data['json']['errors'])) {
            Log::warning('Reddit submit errors', $data['json']['errors']);
            throw new \RuntimeException('Error al publicar en Reddit');
        }

        // Extraer ID de la publicación
        $thing = $data['json']['data']['name'] ?? null; // p.ej. "t3_xxxxxx"
        return [
            'id'   => $thing,
            'url'  => $data['json']['data']['url'] ?? null,
            'full' => $data,
        ];
    }

    /**
     * Sube una imagen como "media asset" y retorna el asset_id.
     */
    public function uploadMediaAsset(SocialAccount $account, string $filename, string $mime, string $binary): string
    {
        $this->ensureValidToken($account);

        // 1) Pedir a Reddit los datos de subida
        $resp = $this->http->post('https://oauth.reddit.com/api/media/asset.json', [
            'headers' => [
                'Authorization' => 'bearer '.$account->access_token,
                'User-Agent'    => $this->userAgent,
            ],
            'form_params' => [
                'filepath' => $filename,
                'mimetype' => $mime,
            ],
        ])->getBody()->getContents();

        $data = json_decode($resp, true);
        if (empty($data['args']['action']) || empty($data['args']['fields']) || empty($data['asset']['asset_id'])) {
            Log::error('Reddit asset: respuesta inesperada', ['resp' => $data]);
            throw new \RuntimeException('No se pudo iniciar la subida de media asset en Reddit');
        }

        $uploadUrl = $data['args']['action'];
        $fields    = $data['args']['fields'];
        $assetId   = $data['asset']['asset_id'];

        // 2) Subir el binario a S3 con los campos firmados
        $multipart = [];
        foreach ($fields as $f) {
            $multipart[] = ['name' => $f['name'], 'contents' => $f['value']];
        }
        $multipart[] = ['name' => 'file', 'contents' => $binary, 'filename' => $filename];

        // Sube directo a S3 (sin auth de Reddit)
        (new Client(['timeout' => 30]))->post($uploadUrl, ['multipart' => $multipart]);

        // 3) Reddit normalmente procesa el asset de inmediato
        return $assetId;
    }

    /**
     * Publica un self-post con RTJSON (permite imagen embebida).
     * $richDocument es el arreglo completo para 'richtext_json'.
     */
    public function submitSelfWithRichtext(SocialAccount $account, string $subreddit, string $title, array $richDocument, array $opts = []): array
    {
        $this->ensureValidToken($account);

        $params = array_merge([
            'sr'            => $subreddit,
            'title'         => $title,
            'kind'          => 'self',
            'submit_type'   => 'rtjson',                   // fuerza interpretación RTJSON
            'richtext_json' => json_encode(['document' => $richDocument]),
            'sendreplies'   => true,
            'resubmit'      => true,
            'api_type'      => 'json',
        ], $opts);

        if (!empty($opts['nsfw']))    $params['nsfw']    = 'true';
        if (!empty($opts['spoiler'])) $params['spoiler'] = 'true';

        $resp = $this->http->post('https://oauth.reddit.com/api/submit', [
            'headers' => [
                'Authorization' => 'bearer '.$account->access_token,
                'User-Agent'    => $this->userAgent,
            ],
            'form_params' => $params,
        ])->getBody()->getContents();

        $data = json_decode($resp, true);

        if (!empty($data['json']['errors'])) {
            Log::warning('Reddit submit (self rtjson) errors', $data['json']['errors']);
            throw new \RuntimeException('Error al publicar self con RTJSON en Reddit');
        }

        $thing = $data['json']['data']['name'] ?? null; // p.ej. "t3_xxxxxx"
        return [
            'id'   => $thing,
            'url'  => $data['json']['data']['url'] ?? null,
            'full' => $data,
        ];
    }
}