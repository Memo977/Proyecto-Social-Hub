<?php

namespace App\Http\Controllers;

use App\Jobs\PublishPost;
use App\Models\Post;
use App\Models\PostTarget;
use App\Models\SocialAccount;
use App\Models\PublicationSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class PostController extends Controller
{
    public function __construct()
    {
        // Aplica PostPolicy a las acciones estándar (create/store ya quedan cubiertas)
        $this->authorizeResource(\App\Models\Post::class, 'post');
    }

    public function create()
    {
        $user = auth()->user();

        // Trae cuentas agrupadas por proveedor para pintar el formulario
        $accounts = SocialAccount::query()
            ->where('user_id', $user->id)
            ->get()
            ->groupBy('provider'); // ['mastodon' => [...], 'reddit' => [...]]

        // Horarios del usuario para programar (ordenados)
        $schedules = PublicationSchedule::query()
            ->where('user_id', $user->id)
            ->orderBy('day_of_week')
            ->orderBy('time')
            ->get();

        return view('posts.create', compact('accounts', 'schedules'));
    }

    public function store(Request $request)
    {
        // Policy: create() se valida automáticamente via authorizeResource
        $user = $request->user();

        // 1) Validación base
        $validated = $request->validate([
            'content'   => ['nullable', 'string', 'min:1'],
            'media_url' => ['nullable', 'url'],
            'targets'   => ['required', 'array', 'min:1'],
            'targets.*' => ['integer'],

            'mode'             => 'required|in:now,scheduled,queue',
            // Para "Programar", ahora exigimos escoger un horario existente del usuario
            'schedule_option'  => ['nullable', 'integer', 'required_if:mode,scheduled'],

            // Campos opcionales/condicionados para Reddit
            'reddit_subreddit' => ['nullable', 'string'],
            'reddit_title'     => ['nullable', 'string', 'max:300'],
            'reddit_kind'      => ['nullable', 'in:self,link,image'],
            'reddit_url'       => ['nullable', 'url'],
        ], [
            'targets.required'   => 'Selecciona al menos un destino.',
            'mode.in'            => 'Modo inválido.',
            'schedule_option.required_if' => 'Selecciona uno de tus horarios para programar.',
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

        // 3) Preparar meta de Reddit (si procede)
        $meta = [];
        if ($needsReddit) {
            $meta['reddit'] = [
                'subreddit' => trim((string) $request->input('reddit_subreddit')),
                'title'     => (string) $request->input('reddit_title'),
                'kind'      => (string) $request->input('reddit_kind', 'self'),
                'url'       => (string) $request->input('reddit_url'),
            ];
        }

        // 4) Resolver modo y fecha/hora
        $mode = (string) $request->input('mode', 'now');
        $scheduledAt = null;

        if ($mode === 'scheduled') {
            // Debe venir schedule_option (ID del horario) y pertenecer al usuario
            $scheduleId = (int) $request->input('schedule_option');
            $slot = PublicationSchedule::query()
                ->where('id', $scheduleId)
                ->where('user_id', $user->id)
                ->first();

            if (!$slot) {
                return back()
                    ->withErrors(['schedule_option' => 'El horario seleccionado no existe o no te pertenece.'])
                    ->withInput();
            }

            // Convertir el (day_of_week + time) del horario a la próxima ocurrencia (Carbon)
            $scheduledAt = $this->nextOccurrenceFromDowAndTime((int) $slot->day_of_week, (string) $slot->time);
            if (!$scheduledAt) {
                return back()
                    ->withErrors(['schedule_option' => 'No fue posible calcular la próxima ocurrencia del horario seleccionado.'])
                    ->withInput();
            }
        }
        elseif ($mode === 'queue') {
            // Calcular el próximo slot; si no hay horarios, NO creamos el post
            $scheduledAt = $this->nextRunForUser((int) $user->id);
            if (!$scheduledAt) {
                return back()
                    ->withErrors(['mode' => 'No tienes horarios de publicación. Crea al menos uno en Horarios para usar la cola.'])
                    ->withInput();
            }
        }

        // 5) Lógica para determinar qué URL usar (caso Reddit tipo link)
        $redditKind = (string) $request->input('reddit_kind');
        $linkUrl = null;
        if ($needsReddit && $redditKind === 'link') {
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
                'link'         => $linkUrl,
                'meta'         => $meta,
                'mode'         => $mode,
                'status'       => $mode === 'scheduled' ? 'scheduled' : ($mode === 'queue' ? 'queued' : 'pending'),
                'scheduled_at' => in_array($mode, ['scheduled','queue'], true) ? $scheduledAt : null,
            ]);

            foreach ($selectedAccounts as $acc) {
                if ((int)$acc->user_id !== (int)$user->id) continue;

                PostTarget::create([
                    'post_id'           => $post->id,
                    'social_account_id' => $acc->id,
                    'status'            => 'pending',
                ]);
            }
        });

        // 7) Despachar el Job con o sin delay
        if ($mode === 'scheduled') {
            PublishPost::dispatch($post->id)->delay($scheduledAt);
            $when = $scheduledAt->timezone(config('app.timezone', 'UTC'))->format('d/m/Y H:i');
            return redirect()->route('dashboard')->with('status', "¡Post programado para el $when!");
        }

        if ($mode === 'queue') {
            PublishPost::dispatch($post->id)->delay($scheduledAt);
            $when = $scheduledAt->timezone(config('app.timezone', 'UTC'))->format('d/m/Y H:i');
            return redirect()->route('dashboard')->with('status', "¡Post enviado a la cola para el $when!");
        }

        // Modo now
        PublishPost::dispatch($post->id);
        return redirect()->route('dashboard')->with('status', '¡Post enviado a publicar!');
    }

    /**
     * Calcula la próxima DateTime (Carbon) del usuario según sus PublicationSchedule.
     * Busca el primer slot > ahora (hoy o próximos 14 días); si no, primer slot de la semana entrante.
     */
    private function nextRunForUser(int $userId): ?Carbon
    {
        $tz  = config('app.timezone', 'UTC');
        $now = Carbon::now($tz);

        /** @var Collection<int, PublicationSchedule> $slots */
        $slots = PublicationSchedule::query()
            ->where('user_id', $userId)
            ->orderBy('day_of_week')
            ->orderBy('time')
            ->get();

        if ($slots->isEmpty()) return null;

        // Probar hoy + 13 días (2 semanas)
        for ($addDays = 0; $addDays < 14; $addDays++) {
            $candidateDay = $now->copy()->addDays($addDays);
            $dow = (int) $candidateDay->dayOfWeek; // 0..6 (0=Dom)
            foreach ($slots as $slot) {
                if ((int)$slot->day_of_week !== $dow) continue;
                [$h, $m, $s] = array_pad(explode(':', $slot->time), 3, 0);
                $candidate = $candidateDay->copy()->setTime((int)$h,(int)$m,(int)$s);
                if ($candidate->greaterThan($now)) return $candidate;
            }
        }

        // Fallback: primer slot de la lista la semana entrante
        $first = $slots->first();
        return $this->nextOccurrenceFromDowAndTime((int)$first->day_of_week, (string)$first->time, addWeek: true);
    }

    /**
     * Desde un (día_semana, hora) retorna la próxima ocurrencia como Carbon.
     * $dayOfWeek: 0=Dom,1=Lun,...6=Sáb. $time: 'HH:MM:SS'
     */
    private function nextOccurrenceFromDowAndTime(int $dayOfWeek, string $time, bool $addWeek = false): ?Carbon
    {
        $tz  = config('app.timezone', 'UTC');
        $now = Carbon::now($tz);

        $base = $addWeek ? $now->copy()->addWeek() : $now->copy();

        // Mover base al próximo $dayOfWeek dentro de esta/ próxima semana
        $daysToAdd = ($dayOfWeek - (int)$base->dayOfWeek + 7) % 7;
        $candidateDay = $base->copy()->addDays($daysToAdd);

        [$h,$m,$s] = array_pad(explode(':', $time), 3, 0);
        $candidate = $candidateDay->setTime((int)$h,(int)$m,(int)$s);

        // Si cayó en el pasado (mismo día pero hora pasada), empuja una semana
        if ($candidate->lessThanOrEqualTo($now)) {
            $candidate = $candidate->addWeek();
        }
        return $candidate;
    }
}