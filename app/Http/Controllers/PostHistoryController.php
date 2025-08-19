<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PostHistoryController extends Controller
{
    /**
     * Muestra el historial de publicaciones enviadas.
     * Soporta filtros: texto (q), proveedor (mastodon|reddit), estado (published|failed),
     * y rango de fechas (from, to) sobre created_at.
     * Vista: posts.history (Historial de Publicaciones)
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function history(Request $request)
    {
        $this->authorize('viewAny', Post::class);

        $user = $request->user();

        $q = Post::with(['targets.socialAccount'])
            ->where('user_id', $user->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if ($search = trim((string) $request->input('q'))) {
            $q->where(function ($qq) use ($search) {
                $qq->where('content', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('link', 'like', "%{$search}%");
            });
        }

        if ($provider = $request->input('provider')) {
            $q->whereHas('targets.socialAccount', function ($qq) use ($provider) {
                $qq->where('provider', $provider);
            });
        }

        if ($from = $request->input('from')) {
            $q->where('created_at', '>=', Carbon::parse($from)->startOfDay());
        }
        if ($to = $request->input('to')) {
            $q->where('created_at', '<=', Carbon::parse($to)->endOfDay());
        }

        if ($status = $request->input('status')) {
            if ($status === 'failed') {
                $q->whereHas('targets', fn($qq) => $qq->where('status', 'failed'))
                    ->whereDoesntHave('targets', fn($qq) => $qq->where('status', 'pending'));
            } elseif ($status === 'published') {
                $q->whereHas('targets')
                    ->whereDoesntHave('targets', fn($qq) => $qq->whereIn('status', ['failed', 'pending']));
            }
        } else {
            // Sin filtro de estado: excluye los que no te interesan a nivel Post
            $q->whereNotIn('status', ['queued', 'scheduled']);
        }

        $posts = $q->paginate(10)->withQueryString();

        $summaries = [];
        foreach ($posts as $post) {
            $summaries[$post->id] = $this->summarize($post);
        }

        return view('posts.history', compact('posts', 'summaries'));
    }

    /**
     * Muestra las publicaciones pendientes en cola (queued|scheduled).
     * Soporta filtros: texto (q), proveedor, y rango de fechas (from, to) sobre scheduled_at.
     * Vista: posts.queue (Cola de Publicaciones)
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function queue(Request $request)
    {
        $this->authorize('viewAny', Post::class);

        $user = $request->user();

        $q = Post::with(['targets.socialAccount'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['queued', 'scheduled']);

        if ($search = trim((string) $request->input('q'))) {
            $q->where(function ($qq) use ($search) {
                $qq->where('content', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('link', 'like', "%{$search}%");
            });
        }

        if ($provider = $request->input('provider')) {
            $q->whereHas('targets.socialAccount', function ($qq) use ($provider) {
                $qq->where('provider', $provider);
            });
        }

        $from = $request->input('from');
        $to   = $request->input('to');
        if ($from || $to) {
            $q->whereNotNull('scheduled_at');
            if ($from) {
                $q->where('scheduled_at', '>=', Carbon::parse($from)->startOfDay());
            }
            if ($to) {
                $q->where('scheduled_at', '<=', Carbon::parse($to)->endOfDay());
            }
        }

        $q->orderByRaw('scheduled_at IS NULL')
            ->orderBy('scheduled_at', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');

        $posts = $q->paginate(10)->withQueryString();

        $summaries = [];
        foreach ($posts as $post) {
            $summaries[$post->id] = $this->summarize($post);
        }

        return view('posts.queue', compact('posts', 'summaries'));
    }

    /**
     * Calcula un resumen de los estados de los targets de una publicación.
     *
     * @param Post $post
     * @return array
     */
    private function summarize(Post $post): array
    {
        $targets   = $post->targets ?? collect();
        $total     = $targets->count();
        $published = $targets->where('status', 'published')->count();
        $failed    = $targets->where('status', 'failed')->count();
        $pending   = max(0, $total - $published - $failed);

        $overall = 'pending';
        if ($total > 0 && $published === $total) {
            $overall = 'published';
        } elseif ($failed > 0 && $pending === 0) {
            $overall = 'failed';
        } elseif ($published > 0 || $pending > 0) {
            $overall = 'partial';
        }

        return compact('total', 'published', 'failed', 'pending', 'overall');
    }
}