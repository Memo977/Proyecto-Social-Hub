<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Services\RedditClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class RedditOAuthController extends Controller
{
    // Scopes mínimos para identificar y publicar
    private array $scopes = ['identity', 'submit'];

    public function redirect()
    {
        return Socialite::driver('reddit')
            ->scopes($this->scopes)
            ->with(['duration' => 'permanent']) // para obtener refresh_token
            ->redirect();
    }

    public function callback(Request $request)
    {
        $redditUser = Socialite::driver('reddit')->stateless()->user();

        $account = SocialAccount::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'provider' => 'reddit',
                'provider_user_id' => (string) $redditUser->getId(),
            ],
            [
                'username'      => $redditUser->getNickname() ?: $redditUser->getName(),
                'access_token'  => $redditUser->token,
                'refresh_token' => $redditUser->refreshToken ?? null,
                'expires_at'    => isset($redditUser->expiresIn)
                    ? Carbon::now()->addSeconds($redditUser->expiresIn)
                    : null,
                'meta'          => [
                    'avatar' => $redditUser->getAvatar(),
                    'email'  => $redditUser->getEmail(),
                    'raw'    => $redditUser->user,
                ],
            ]
        );

        return redirect()
            ->route('dashboard') // ajusta a tu ruta
            ->with('status', 'Cuenta Reddit conectada como u/'.$account->username);
    }

    // (Opcional) endpoint para probar un submit inmediato
    public function testSubmit(Request $request, RedditClient $client)
    {
        $request->validate([
            'subreddit' => 'required|string',     // p.ej. "test"
            'title'     => 'required|string|max:300',
            'kind'      => 'required|in:self,link',
            'text'      => 'nullable|string',
            'url'       => 'nullable|url',
            'nsfw'      => 'sometimes|boolean',
            'spoiler'   => 'sometimes|boolean',
        ]);

        $account = SocialAccount::where('user_id', Auth::id())
            ->where('provider', 'reddit')
            ->firstOrFail();

        $response = $client->submitPost($account, [
            'sr'         => $request->string('subreddit'),
            'title'      => $request->string('title'),
            'kind'       => $request->string('kind'), // 'self' o 'link'
            'text'       => $request->input('text'),
            'url'        => $request->input('url'),
            'nsfw'       => $request->boolean('nsfw', false),
            'spoiler'    => $request->boolean('spoiler', false),
            'sendreplies'=> true,
        ]);

        return back()->with('status', 'Publicado en r/'.$request->subreddit.' (id: '.$response['id'].')');
    }
}