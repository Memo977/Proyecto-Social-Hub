<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostHistoryController extends Controller
{
    /**
     * Histórico de publicaciones (enviadas).
     * Soporta filtros: q (texto), provider (mastodon|reddit), status (published|failed),
     * rango de fechas (from, to) sobre created_at.
     */
    public function history(Request $request)
    {
        // Policy general: ver posts del usuario
        $this->authorize('viewAny', \App\Models\Post::class);

        $user = $request->user();

        $q = Post::with(['targets.socialAccount'])
            ->where('user_id', $user->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        // Búsqueda texto
        if ($search = trim((string) $request->input('q'))) {
            $q->where(function ($qq) use ($search) {
                $qq->where('content', 'like', "%{$search}%")
                   ->orWhere('title', 'like', "%{$search}%")
                   ->orWhere('link', 'like', "%{$search}%");
            });
        }

        // Filtrar por proveedor
        if ($provider = $request->input('provider')) {
            $q->whereHas('targets.socialAccount', function ($qq) use ($provider) {
                $qq->where('provider', $provider);
            });
        }

        // Rango de fechas (sobre created_at)
        if ($from = $request->input('from')) {
            $q->where('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $q->where('created_at', '<=', $to);
        }

        // Estado (por defecto excluimos cola: queued/scheduled)
        if ($status = $request->input('status')) {
            if ($status === 'published') {
                $q->where('status', 'published');
            } elseif ($status === 'failed') {
                $q->where('status', 'failed');
            }
        } else {
            $q->whereNotIn('status', ['queued', 'scheduled']);
        }

        $posts = $q->paginate(10)->withQueryString();

        // Resumen por post (publicadas/fallidas/pendientes)
        $summaries = [];
        foreach ($posts as $post) {
            $summaries[$post->id] = $this->summarize($post);
        }

        return view('posts.history', compact('posts', 'summaries'));
    }

    /**
     * Pendientes en cola (queued|scheduled), ordenados por scheduled_at/created_at asc.
     * Filtros: q, provider.
     */
    public function queue(Request $request)
    {
        // Policy general: ver posts del usuario
        $this->authorize('viewAny', \App\Models\Post::class);

        $user = $request->user();

        $q = Post::with(['targets.socialAccount'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['queued', 'scheduled'])
            ->orderByRaw('COALESCE(scheduled_at, created_at) asc')
            ->orderBy('id', 'asc');

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

        $posts = $q->paginate(10)->withQueryString();

        $summaries = [];
        foreach ($posts as $post) {
            $summaries[$post->id] = $this->summarize($post);
        }

        return view('posts.queue', compact('posts', 'summaries'));
    }

    /**
     * Calcula un resumen por post a partir de sus targets.
     */
    private function summarize(Post $post): array
    {
        $targets   = $post->targets ?? collect();
        $total     = $targets->count();
        $published = $targets->where('status', 'published')->count();
        $failed    = $targets->where('status', 'failed')->count();
        $pending   = max(0, $total - $published - $failed);

        // overall
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