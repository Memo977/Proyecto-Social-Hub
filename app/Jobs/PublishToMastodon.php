<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\SocialAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishToMastodon implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $postId;
    public array $target; // ej. ['provider' => 'mastodon', 'subreddit' => null, etc.]

    public function __construct(int $postId, array $target)
    {
        $this->postId = $postId;
        $this->target = $target;
    }

    public function handle(): void
    {
        // Commit 16 implementará la publicación real.
        Log::info('PublishToMastodon ejecutado (stub)', [
            'post_id' => $this->postId,
            'target'  => $this->target,
        ]);
    }
}