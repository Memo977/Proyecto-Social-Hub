<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class MastodonAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $scopes = config('services.mastodon.scope', ['read','write']); // <— ESTA LÍNEA ES LA QUE FALTA
        $domain = $request->query('domain');

        $driver = Socialite::driver('mastodon')->setScopes($scopes);

        if ($domain) {
            $driver = $driver->with(['domain' => $domain]); // opcional, para instancias dinámicas
        }

        return $driver->redirect();
    }

    public function callback(Request $request)
    {
        // Obtiene el usuario remoto + tokens
        // El paquete community devuelve access_token/refresh_token/expiresIn, etc.
        try {
            $oauthUser = Socialite::driver('mastodon')->user();
        } catch (\Throwable $e) {
            return redirect()->route('dashboard')->with('status', 'No se pudo conectar Mastodon.');
        }

        // Datos útiles
        $provider           = 'mastodon';
        $providerUserId     = (string) ($oauthUser->getId() ?? $oauthUser->id);
        $username           = $oauthUser->getNickname() ?? ($oauthUser->nickname ?? null);
        $name               = $oauthUser->getName() ?? ($oauthUser->name ?? null);
        $email              = $oauthUser->getEmail() ?? null;
        $avatar             = $oauthUser->getAvatar() ?? null;

        // Tokens (puede variar el nombre de propiedades; Socialite suele exponerlos en ->token, ->refreshToken, ->expiresIn)
        $accessToken        = $oauthUser->token ?? $oauthUser->accessToken ?? null;
        $refreshToken       = $oauthUser->refreshToken ?? null;
        $expiresIn          = $oauthUser->expiresIn ?? null;

        // Dominio de instancia (si el provider lo entrega en 'user' raw)
        $raw                = method_exists($oauthUser, 'getRaw') ? $oauthUser->getRaw() : (array) $oauthUser;
        $instanceDomain     = config('services.mastodon.domain'); // fijo; si usas múltiples, extrae de sesión o $raw

        // Persiste/actualiza la cuenta social
        $account = SocialAccount::updateOrCreate(
            [
                'provider'         => $provider,
                'provider_user_id' => $providerUserId,
            ],
            [
                'user_id'          => Auth::id(),
                'username'         => $username,
                'instance_domain'  => $instanceDomain,
                'access_token'     => $accessToken,
                'refresh_token'    => $refreshToken,
                'expires_at'       => $expiresIn ? now()->addSeconds((int) $expiresIn) : null,
                'meta'             => [
                    'name'   => $name,
                    'email'  => $email,
                    'avatar' => $avatar,
                    'raw'    => $raw,
                ],
            ]
        );

        // (Opcional) Actualiza nombre/avatar del usuario local si vienen vacíos
        $user = Auth::user();
        if (!$user->name && $name) {
            $user->name = $name;
            $user->save();
        }

        // Redirige a tu dashboard con mensaje
        return redirect()->intended('/dashboard')->with('status', 'Mastodon conectado correctamente.');
    }
}
