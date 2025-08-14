<?php

namespace App\Services;

use App\Models\SocialAccount;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class MastodonClient
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'timeout' => 15,
        ]);
    }

    /**
     * Asegura que el access_token sea válido. Si está vencido y
     * existe refresh_token, lo renueva contra la instancia del usuario.
     */
    public function ensureValidToken(SocialAccount $account): void
    {
        if ($account->provider !== 'mastodon') {
            return;
        }

        if ($account->expires_at && $account->expires_at->isFuture()) {
            return; // token aún válido
        }

        if (!$account->refresh_token) {
            // No hay refresh; nada que hacer
            return;
        }

        $instance = rtrim($account->instance_domain ?? config('services.mastodon.domain', ''), '/');
        if (empty($instance)) {
            Log::warning('MastodonClient: instance_domain vacío para refresh', ['account_id' => $account->id]);
            return;
        }

        $clientId = config('services.mastodon.client_id');
        $clientSecret = config('services.mastodon.client_secret');

        if (!$clientId || !$clientSecret) {
            Log::warning('MastodonClient: faltan client_id/secret en config para refresh');
            return;
        }

        try {
            $resp = $this->http->post($instance . '/oauth/token', [
                'form_params' => [
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $account->refresh_token,
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode((string) $resp->getBody(), true) ?? [];

            $account->access_token = $data['access_token'] ?? $account->access_token;
            if (!empty($data['refresh_token'])) {
                $account->refresh_token = $data['refresh_token'];
            }
            if (!empty($data['expires_in'])) {
                $account->expires_at = Carbon::now()->addSeconds((int) $data['expires_in']);
            }
            $account->save();
        } catch (\Throwable $e) {
            Log::warning('MastodonClient: fallo al refrescar token', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function postStatus(SocialAccount $account, array $payload): array
    {
        $this->ensureValidToken($account);

        $instance = rtrim($account->instance_domain ?? config('services.mastodon.domain', ''), '/');
        if (empty($instance)) {
            throw new \RuntimeException('Dominio de instancia Mastodon vacío');
        }

        $resp = $this->http->post($instance . '/api/v1/statuses', [
            'headers' => [
                'Authorization' => 'Bearer ' . $account->access_token,
                'Accept'        => 'application/json',
            ],
            'form_params' => [
                'status'       => $payload['status'] ?? '',
                'visibility'   => $payload['visibility'] ?? 'public',
                'scheduled_at' => $payload['scheduled_at'] ?? null,
            ],
        ]);

        return json_decode((string) $resp->getBody(), true) ?? [];
    }
}