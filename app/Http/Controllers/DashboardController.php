<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\PublicationSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Métricas rápidas
        $totalPosts = Post::where('user_id', $user->id)->count();
        $queued     = Post::where('user_id', $user->id)->where('status', 'queued')->count();
        $scheduled  = Post::where('user_id', $user->id)->where('status', 'scheduled')->count();
        $published  = Post::where('user_id', $user->id)->whereNotNull('published_at')->count();

        $accounts   = SocialAccount::where('user_id', $user->id)->get();
        $schedulesC = PublicationSchedule::where('user_id', $user->id)->count();

        // Últimas 5 publicaciones (de cualquier estado)
        $recentPosts = Post::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id','title','status','scheduled_at','published_at','created_at']);

        // Próximas 5 programadas
        $now = Carbon::now();
        $upcoming = Post::where('user_id', $user->id)
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', $now)
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get(['id','title','scheduled_at']);

        return view('dashboard', [
            'totalPosts' => $totalPosts,
            'queued'     => $queued,
            'scheduled'  => $scheduled,
            'published'  => $published,
            'accounts'   => $accounts,
            'schedulesC' => $schedulesC,
            'recentPosts'=> $recentPosts,
            'upcoming'   => $upcoming,
        ]);
    }
}