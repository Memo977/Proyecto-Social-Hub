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
     * Envía una publicación a Reddit.
     * Campos esperados en $payload:
     * - sr (subreddit), title, kind ('self' o 'link'), text/url (según kind), nsfw, spoiler, sendreplies
     */
    public function submitPost(SocialAccount $account, array $payload): array
    {
        $this->ensureValidToken($account);

        // Construir parámetros para /api/submit
        $params = [
            'sr'         => Arr::get($payload, 'sr'),
            'title'      => Arr::get($payload, 'title'),
            'kind'       => Arr::get($payload, 'kind', 'self'),
            'api_type'   => 'json',
            'sendreplies'=> Arr::get($payload, 'sendreplies', true) ? 'true' : 'false',
        ];

        if ($params['kind'] === 'self') {
            $params['text'] = Arr::get($payload, 'text', '');
        } else { // link
            $params['url']  = Arr::get($payload, 'url', '');
        }

        if (Arr::get($payload, 'nsfw', false))   $params['nsfw'] = 'true';
        if (Arr::get($payload, 'spoiler', false))$params['spoiler'] = 'true';

        $resp = $this->http->post('https://oauth.reddit.com/api/submit', [
            'headers' => [
                'Authorization' => 'bearer '.$account->access_token,
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
}
