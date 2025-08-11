<?php

namespace App\Http\Controllers;

use App\Jobs\PublishPost;
use App\Models\Post;
use App\Models\PostTarget;
use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    public function create()
    {
        $user = auth()->user();

        $accounts = SocialAccount::query()
            ->where('user_id', $user->id)
            ->orderBy('provider')
            ->get()
            ->groupBy('provider'); // ['mastodon' => [...], 'reddit' => [...]]

        return view('posts.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        // Validación base
        $validated = $request->validate([
            'content'   => ['required', 'string', 'max:2000'],
            'media_url' => ['nullable', 'url'],
            'targets'   => ['required', 'array', 'min:1'],
            'targets.*' => ['integer', 'exists:social_accounts,id'],
        ]);

        // Cuentas seleccionadas (para saber si incluye Reddit)
        $selectedAccounts = \App\Models\SocialAccount::query()
            ->whereIn('id', $validated['targets'])
            ->where('user_id', $user->id)
            ->get();

        $needsReddit = $selectedAccounts->contains(fn($a) => $a->provider === 'reddit');

        // Si hay Reddit, validar campos obligatorios
        if ($needsReddit) {
            $extra = $request->validate([
                'reddit_subreddit' => ['required', 'string', 'max:100'],   // sin r/
                'reddit_title'     => ['required', 'string', 'max:300'],
                'reddit_kind'      => ['required', 'in:self,link'],
                'reddit_url'       => ['nullable', 'url'],                // requerido si kind=link (lo validamos abajo)
            ]);
            if (($request->input('reddit_kind') === 'link') && !$request->filled('reddit_url')) {
                return back()->withErrors(['reddit_url' => 'La URL es obligatoria cuando el tipo es link.'])
                    ->withInput();
            }
        }

        $post = null;

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $validated, $selectedAccounts, $needsReddit, $request, &$post) {
            $meta = [];
            if ($needsReddit) {
                $meta['reddit'] = [
                    'subreddit' => trim($request->string('reddit_subreddit')),
                    'title'     => $request->string('reddit_title'),
                    'kind'      => $request->string('reddit_kind', 'self'),
                    'url'       => $request->string('reddit_url'),
                ];
            }

            $post = \App\Models\Post::create([
                'user_id'    => $user->id,
                'content'    => $validated['content'],
                'title'      => $needsReddit ? $request->string('reddit_title') : null, // útil para Reddit
                'media_url'  => $validated['media_url'] ?? null,
                'link'       => ($needsReddit && $request->input('reddit_kind') === 'link') ? $request->string('reddit_url') : null,
                'meta'       => !empty($meta) ? $meta : null,
                'status'     => 'publishing',
                'mode'       => 'now',
            ]);

            foreach ($selectedAccounts as $acc) {
                \App\Models\PostTarget::create([
                    'post_id'           => $post->id,
                    'social_account_id' => $acc->id,
                    'status'            => 'pending',
                ]);
            }
        });

        \App\Jobs\PublishPost::dispatch($post->id);

        return redirect()->route('dashboard')
            ->with('status', '¡Post enviado a publicar! (Mastodon/Reddit)');
    }
}