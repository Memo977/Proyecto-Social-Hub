<?php

namespace App\Http\Controllers;

use App\Jobs\PublishPost;
use App\Models\Post;
use App\Models\PostTarget;
use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PostController extends Controller
{
    public function create()
    {
        $user = auth()->user();

        // Trae cuentas agrupadas por proveedor para pintar el formulario
        $accounts = SocialAccount::query()
            ->where('user_id', $user->id)
            ->get()
            ->groupBy('provider'); // ['mastodon' => [...], 'reddit' => [...]]

        return view('posts.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // 1) Validación base
        $validated = $request->validate([
            'content'   => ['nullable', 'string', 'min:1'],
            'media_url' => ['nullable', 'url'],
            'targets'   => ['required', 'array', 'min:1'],
            'targets.*' => ['integer'],

            'mode'         => ['required', 'in:now,scheduled,queue'],
            'scheduled_at' => ['nullable', 'date', 'after:now', 'required_if:mode,scheduled'],

            // Campos opcionales/condicionados para Reddit
            'reddit_subreddit' => ['nullable', 'string'],
            'reddit_title'     => ['nullable', 'string', 'max:300'],
            'reddit_kind'      => ['nullable', 'in:self,link,image'],
            'reddit_url'       => ['nullable', 'url'],
        ], [
            'targets.required'   => 'Selecciona al menos un destino.',
            'mode.in'            => 'Modo inválido.',
            'scheduled_at.after' => 'La fecha/hora debe ser en el futuro.',
            'scheduled_at.required_if' => 'Debes indicar fecha/hora cuando el modo es Programar.',
        ]);

        // 2) Cargar cuentas seleccionadas y verificar pertenencia al usuario
        $selectedAccounts = SocialAccount::query()
            ->where('user_id', $user->id)
            ->whereIn('id', $validated['targets'])
            ->get();

        if ($selectedAccounts->isEmpty()) {
            return back()->withErrors(['targets' => 'No se seleccionaron destinos válidos.'])->withInput();
        }

        $needsReddit = $selectedAccounts->contains(fn ($acc) => $acc->provider === 'reddit');

        // Si hay Reddit entre los targets, forzamos validaciones específicas
        if ($needsReddit) {
            $request->validate([
                'reddit_subreddit' => ['required', 'string'],
                'reddit_title'     => ['required', 'string', 'max:300'],
                'reddit_kind'      => ['required', 'in:self,link,image'],
                'reddit_url'       => ['nullable', 'url', 'required_if:reddit_kind,link'],
            ], [
                'reddit_subreddit.required' => 'Para Reddit debes indicar el subreddit.',
                'reddit_title.required'     => 'Para Reddit debes indicar el título.',
                'reddit_kind.required'      => 'Selecciona el tipo de post de Reddit.',
                'reddit_url.required_if'    => 'Para tipo link debes indicar la URL.',
            ]);
        }

        // 3) Preparar campos meta de Reddit (si procede)
        $meta = [];
        if ($needsReddit) {
            $meta['reddit'] = [
                'subreddit' => trim((string) $request->input('reddit_subreddit')),
                'title'     => (string) $request->input('reddit_title'),
                'kind'      => (string) $request->input('reddit_kind', 'self'),
                'url'       => (string) $request->input('reddit_url'),
            ];
        }

        // 4) Resolver modo y fecha/hora programada
        $mode = (string) $request->input('mode', 'now');
        $scheduledAt = null;

        if ($mode === 'scheduled') {
            $tz = config('app.timezone', 'UTC');
            $scheduledRaw = $request->input('scheduled_at');
            $scheduledAt = $scheduledRaw ? Carbon::parse($scheduledRaw, $tz) : null;
        }

        // 5) Lógica corregida para determinar qué URL usar
        $redditKind = (string) $request->input('reddit_kind');
        $linkUrl = null;

        if ($needsReddit && $redditKind === 'link') {
            // Si es tipo link, usa reddit_url (no media_url)
            $linkUrl = (string) $request->input('reddit_url');
        }

        // 6) Crear Post + Targets dentro de transacción
        $post = null;
        DB::transaction(function () use ($user, $validated, $needsReddit, $request, $meta, $selectedAccounts, $mode, $scheduledAt, $linkUrl, &$post) {
            $post = Post::create([
                'user_id'      => $user->id,
                'content'      => $validated['content'],
                'title'        => $needsReddit ? (string) $request->input('reddit_title') : null,
                'media_url'    => $validated['media_url'] ?? null,
                'link'         => $linkUrl, // Solo se llena si es tipo link
                'meta'         => $meta,
                'mode'         => $mode,
                'status'       => $mode === 'scheduled' ? 'scheduled' : 'pending',
                'scheduled_at' => $mode === 'scheduled' ? $scheduledAt : null,
            ]);

            foreach ($selectedAccounts as $acc) {
                PostTarget::create([
                    'post_id'           => $post->id,
                    'social_account_id' => $acc->id,
                    'status'            => 'pending',
                ]);
            }
        });

        // 7) Despachar el Job con o sin delay
        if ($mode === 'scheduled' && $scheduledAt && $scheduledAt->isFuture()) {
            PublishPost::dispatch($post->id)->delay($scheduledAt);
            $when = $scheduledAt->timezone(config('app.timezone', 'UTC'))->format('d/m/Y H:i');
            return redirect()->route('dashboard')->with('status', "¡Post programado para el $when!");
        }

        // now (o queue que aún no usamos)
        PublishPost::dispatch($post->id);
        return redirect()->route('dashboard')->with('status', '¡Post enviado a publicar!');
    }
}