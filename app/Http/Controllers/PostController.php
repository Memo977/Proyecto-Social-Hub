<?php

namespace App\Http\Controllers;

use App\Jobs\PublishPost;
use App\Models\Post;
use App\Models\PostTarget;
use App\Models\SocialAccount;
use App\Models\PublicationSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Services\NextRunService;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    public function create()
    {
        $user = Auth::user();

        $accounts   = SocialAccount::where('user_id', $user->id)->get();
        $byProvider = $accounts->groupBy('provider');

        $mastodonAccount = optional($byProvider->get('mastodon'))->first();
        $redditAccount   = optional($byProvider->get('reddit'))->first();

        $schedules = PublicationSchedule::where('user_id', $user->id)
            ->orderBy('day_of_week')
            ->orderBy('time')
            ->get();

        return view('posts.create', [
            'accounts'        => $byProvider,
            'mastodonAccount' => $mastodonAccount,
            'redditAccount'   => $redditAccount,
            'hasMastodon'     => (bool) $mastodonAccount,
            'hasReddit'       => (bool) $redditAccount,
            'socialAccounts'  => $accounts,
            'schedules'       => $schedules,
            'hasSchedules'    => $schedules->isNotEmpty(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'content'          => ['required', 'string', 'max:1000'],
            'title'            => ['nullable', 'string', 'max:255'],
            'media_url'        => ['nullable', 'url'],
            'link'             => ['nullable', 'url'],
            'mode'             => ['required', Rule::in(['now', 'queue', 'schedule'])],
            'scheduled_at'     => ['nullable', 'date'],
            'schedule_option'  => ['nullable', 'integer'],
            'targets'          => ['nullable', 'array'],
            'reddit_subreddit' => ['nullable', 'string', 'max:100'],
            'reddit_kind'      => ['nullable', Rule::in(['self', 'link'])],
        ]);

        // ✅ Validación condicional para Reddit si el usuario selecciona una cuenta de Reddit
        $selectedIds = $validated['targets'] ?? [];
        if (!empty($selectedIds)) {
            $selectedAccounts = SocialAccount::whereIn('id', $selectedIds)->get();
            $wantsReddit = $selectedAccounts->contains(fn($a) => $a->provider === 'reddit');
            if ($wantsReddit) {
                $request->validate([
                    'reddit_subreddit' => ['required', 'string', 'max:100'],
                    'title'            => ['required', 'string', 'max:300'],
                    'reddit_kind'      => ['required', Rule::in(['self', 'link'])],
                ]);
            }
        }

        $post = Post::create([
            'user_id'      => Auth::id(),
            'content'      => $validated['content'],
            'title'        => $validated['title'] ?? null,
            'media_url'    => $validated['media_url'] ?? null,
            'link'         => $validated['link'] ?? null,
            'mode'         => $validated['mode'],

            // ✅ Estados iniciales coherentes:
            //   schedule -> scheduled | now -> pending | queue -> queued
            'status'       => $validated['mode'] === 'schedule'
                ? 'scheduled'
                : ($validated['mode'] === 'now' ? 'pending' : 'queued'),

            'scheduled_at' => null,
        ]);

        // Guardar meta.reddit.* (subreddit, kind y opcionalmente title)
        $sr   = $this->normalizeSr($request->input('reddit_subreddit'));
        $kind = $request->input('reddit_kind', 'self');

        $meta = $post->meta ?? [];
        if ($request->filled('title')) {
            data_set($meta, 'reddit.title', $request->input('title'));
        }
        $meta['reddit'] = array_merge($meta['reddit'] ?? [], [
            'subreddit' => $sr,      // p.ej. "programming" o "u_tuUsuario"
            'kind'      => $kind,    // informativo; el Job usa link/self según $post->link
        ]);
        $post->meta = $meta;
        $post->save();

        // Crear targets (sólo seleccionados; si no, todas las cuentas del usuario)
        $rawTargets = $request->input('targets', []);
        if (!is_array($rawTargets)) {
            $rawTargets = [$rawTargets]; // fuerza array cuando viene solo un valor
        }

        $targetIds = collect($rawTargets)
            ->map(fn($v) => (int) $v)
            ->unique()
            ->values();

        $accountsQuery = SocialAccount::where('user_id', Auth::id());
        $accounts = $targetIds->isNotEmpty()
            ? $accountsQuery->whereIn('id', $targetIds)->get()
            : $accountsQuery->get();

        foreach ($accounts as $acc) {
            PostTarget::create([
                'post_id'           => $post->id,
                'social_account_id' => $acc->id,
                'status'            => 'pending',
            ]);
        }

        // Programar exacto
        if ($validated['mode'] === 'schedule') {
            $when = null;

            if (!empty($validated['scheduled_at'])) {
                $when = Carbon::parse($validated['scheduled_at']);
            } elseif (!empty($validated['schedule_option'])) {
                $optId = (int) $validated['schedule_option'];

                $opt = PublicationSchedule::where('user_id', Auth::id())
                    ->where('id', $optId)
                    ->first();

                if ($opt) {
                    /** @var NextRunService $nextRun */
                    $nextRun = app(NextRunService::class);
                    // requiere que NextRunService tenga nextFromSchedule()
                    $when = $nextRun->nextFromSchedule($opt) ?? now()->addMinute();
                }
            }

            if (!$when) {
                return back()->withErrors([
                    'schedule_option' => 'Selecciona un horario válido o proporciona una fecha/hora.'
                ])->withInput();
            }

            $post->status       = 'scheduled';
            $post->scheduled_at = $when;
            $post->save();

            foreach ($post->targets as $t) {
                PublishPost::dispatch($post->id, $t->id)->delay($when);
            }

            $whenTxt = $when->timezone(config('app.timezone', 'UTC'))->format('d/m/Y H:i');
            return redirect()->route('dashboard')->with('status', "¡Post programado para el $whenTxt!");
        }

        // A la cola
        if ($validated['mode'] === 'queue') {
            /** @var NextRunService $nextRun */
            $nextRun = app(NextRunService::class);
            $when = $nextRun->nextForUser(Auth::id()) ?? now()->addMinute();

            $post->scheduled_at = $when;
            $post->save();

            foreach ($post->targets as $t) {
                PublishPost::dispatch($post->id, $t->id)->delay($when);
            }

            $whenTxt = $when->timezone(config('app.timezone', 'UTC'))->format('d/m/Y H:i');
            return redirect()->route('dashboard')->with('status', "¡Post enviado a la cola para el $whenTxt!");
        }

        // Publicar ahora
        foreach ($post->targets as $t) {
            PublishPost::dispatch($post->id, $t->id);
        }

        return redirect()->route('dashboard')->with('status', '¡Post enviado a publicar!');
    }

    public function edit(Post $post)
    {
        $this->authorize('update', $post);
        if (!$post->isEditable()) {
            return redirect()->route('posts.queue')->with('error', 'Esta publicación ya no se puede editar.');
        }

        $user = Auth::user();

        $accounts   = SocialAccount::where('user_id', $user->id)->get();
        $byProvider = $accounts->groupBy('provider');

        $mastodonAccount = optional($byProvider->get('mastodon'))->first();
        $redditAccount   = optional($byProvider->get('reddit'))->first();

        $schedules = PublicationSchedule::where('user_id', $user->id)
            ->orderBy('day_of_week')
            ->orderBy('time')
            ->get();

        return view('posts.edit', [
            'post'            => $post,
            'accounts'        => $byProvider,
            'mastodonAccount' => $mastodonAccount,
            'redditAccount'   => $redditAccount,
            'hasMastodon'     => (bool) $mastodonAccount,
            'hasReddit'       => (bool) $redditAccount,
            'socialAccounts'  => $accounts,
            'schedules'       => $schedules,
            'hasSchedules'    => $schedules->isNotEmpty(),
        ]);
    }

    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        if (!$post->isEditable()) {
            return redirect()->route('posts.queue')->with('error', 'Esta publicación ya no se puede editar.');
        }

        $validated = $request->validate([
            'content'          => ['required', 'string', 'max:1000'],
            'title'            => ['nullable', 'string', 'max:255'],
            'media_url'        => ['nullable', 'url'],
            'link'             => ['nullable', 'url'],
            'mode'             => ['required', Rule::in(['now', 'queue', 'schedule'])],
            'scheduled_at'     => ['nullable', 'date'],
            'schedule_option'  => ['nullable', 'integer'],
            'targets'          => ['nullable', 'array'],
            'reddit_subreddit' => ['nullable', 'string', 'max:100'],
            'reddit_kind'      => ['nullable', Rule::in(['self', 'link'])],
        ]);

        $post->fill([
            'content'   => $validated['content'],
            'title'     => $validated['title'] ?? null,
            'media_url' => $validated['media_url'] ?? null,
            'link'      => $validated['link'] ?? null,
        ])->save();

        // Actualizar meta.reddit si viene en la edición (incluye title)
        $meta = $post->meta ?? [];
        $changedMeta = false;

        if ($request->filled('title')) {
            data_set($meta, 'reddit.title', $request->input('title'));
            $changedMeta = true;
        }
        if ($request->filled('reddit_subreddit')) {
            data_set($meta, 'reddit.subreddit', $this->normalizeSr($request->input('reddit_subreddit')));
            $changedMeta = true;
        }
        if ($request->filled('reddit_kind')) {
            data_set($meta, 'reddit.kind', $request->input('reddit_kind'));
            $changedMeta = true;
        }

        if ($changedMeta) {
            $post->meta = $meta;
            $post->save();
        }

        // (Opcional) actualizar targets si se reenvían en la edición
        $targetsChanged = false;
        if ($request->has('targets')) {
            $ids = collect($request->input('targets', []))->map('intval');
            $post->targets()->delete();
            $accounts = SocialAccount::where('user_id', Auth::id())
                ->when($ids->isNotEmpty(), fn($q) => $q->whereIn('id', $ids))
                ->get();
            foreach ($accounts as $acc) {
                PostTarget::create([
                    'post_id'           => $post->id,
                    'social_account_id' => $acc->id,
                    'status'            => 'pending',
                ]);
            }
            $targetsChanged = true;
        }

        if ($validated['mode'] === 'schedule') {
            $oldWhen = $post->scheduled_at ? Carbon::parse($post->scheduled_at) : null;
            $when = null;

            if (!empty($validated['scheduled_at'])) {
                $when = Carbon::parse($validated['scheduled_at']);
            } elseif ($request->filled('schedule_option')) {
                $slot = PublicationSchedule::where('user_id', Auth::id())
                    ->where('id', (int) $request->input('schedule_option'))
                    ->first();
                if ($slot) {
                    $nrs  = app(NextRunService::class);
                    $when = $nrs->nextOccurrenceFromDowAndTime((int)$slot->day_of_week, (string)$slot->time);
                }
            } else {
                // 🚩 Sin cambios explícitos: conservar la hora previa si existe
                $when = $oldWhen;
            }

            if (!$when) {
                return back()->withErrors([
                    'schedule_option' => 'Selecciona un horario válido, proporciona una fecha/hora o deja la existente.'
                ])->withInput();
            }

            $post->status       = 'scheduled';
            $post->scheduled_at = $when;
            $post->save();

            // Solo re-despacha si cambió la hora o cambiaron los targets
            $shouldRedispatch = !$oldWhen || !$oldWhen->equalTo($when) || $targetsChanged;
            if ($shouldRedispatch) {
                foreach ($post->targets as $t) {
                    PublishPost::dispatch($post->id, $t->id)->delay($when);
                }
            }

            $whenTxt = $when->timezone(config('app.timezone', 'UTC'))->format('d/m/Y H:i');
            return redirect()->route('posts.queue')->with('status', "Publicación reprogramada para el $whenTxt.");
        }

        if ($validated['mode'] === 'queue') {
            $nrs = app(NextRunService::class);
            $oldWhen = $post->scheduled_at ? Carbon::parse($post->scheduled_at) : null;

            // 🚩 Mantener la hora si ya existe y es futura; si no, calcular la siguiente
            if ($oldWhen && $oldWhen->isFuture()) {
                $when = $oldWhen;
            } else {
                $when = $nrs->nextForUser(Auth::id()) ?? now()->addMinute();
            }

            $post->status       = 'queued';
            $post->scheduled_at = $when;
            $post->save();

            // Solo re-despacha si la hora cambió o cambiaron los targets
            $shouldRedispatch = !$oldWhen || !$oldWhen->equalTo($when) || $targetsChanged;
            if ($shouldRedispatch) {
                foreach ($post->targets as $t) {
                    PublishPost::dispatch($post->id, $t->id)->delay($when);
                }
            }

            $whenTxt = $when->timezone(config('app.timezone', 'UTC'))->format('d/m/Y H:i');
            return redirect()->route('posts.queue')->with('status', "Publicación actualizada y en cola para el $whenTxt.");
        }

        // now
        $post->status       = 'queued';
        $post->scheduled_at = null;
        $post->save();

        foreach ($post->targets as $t) {
            PublishPost::dispatch($post->id, $t->id);
        }

        return redirect()->route('posts.history')->with('status', 'Publicación actualizada y enviada inmediatamente.');
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        if (!$post->isDeletable()) {
            return redirect()->back()->with('error', 'Esta publicación ya no se puede eliminar.');
        }

        $post->canceled_at = now();
        $post->status      = 'canceled'; // opcional
        $post->save();

        return redirect()->route('posts.queue')->with('status', 'Publicación cancelada/eliminada correctamente.');
    }

    /** Normaliza formatos admitidos:
     *  "r/test", "/r/test", "https://reddit.com/r/test" => "test"
     *  "u/usuario" o "https://reddit.com/u/usuario"     => "u_usuario"
     */
    private function normalizeSr(?string $sr): ?string
    {
        if (!$sr) return null;
        $sr = trim($sr);

        // Quitar dominio si lo trae
        $sr = preg_replace('#^https?://(www\.)?reddit\.com/#i', '', $sr);
        $sr = ltrim($sr, '/');

        if (str_starts_with($sr, 'r/')) {
            $sr = substr($sr, 2);
        } elseif (str_starts_with($sr, '/r/')) {
            $sr = substr($sr, 3);
        }

        if (str_starts_with($sr, 'u/')) {
            $user = substr($sr, 2);
            $user = ltrim($user, '/');
            return $user ? 'u_' . $user : null;
        }

        // Si viene vacío tras limpiar, null
        return $sr !== '' ? $sr : null;
    }
}