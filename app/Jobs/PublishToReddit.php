<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\SocialAccount;
use App\Services\RedditClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishToReddit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public $postId;

    public function __construct(int $postId)
    {
        $this->postId = $postId;
    }

    public function handle(RedditClient $client): void
    {
        $post = Post::find($this->postId);
        if (!$post) {
            Log::warning('PublishToReddit: post no encontrado.');
            return;
        }

        $account = SocialAccount::where('user_id', $post->user_id)
            ->where('provider', 'reddit')
            ->first();

        if (!$account) {
            Log::info('PublishToReddit: el usuario no tiene cuenta de Reddit conectada.', [
                'user_id' => $post->user_id,
                'post_id' => $post->id,
            ]);
            return;
        }

        $payload = [
            'sr'      => $post->reddit_subreddit,
            'title'   => $post->title,
            'kind'    => $post->link ? 'link' : 'self',
            'text'    => $post->link ? null : $post->content,
            'url'     => $post->link ?: null,
            'nsfw'    => (bool)$post->nsfw,
            'spoiler' => (bool)$post->spoiler,
        ];

        $payload = array_filter($payload, fn($v) => !is_null($v));

        $client->submitPost($account, $payload);

        Log::info('PublishToReddit: publicación enviada.', [
            'post_id'   => $post->id,
            'subreddit' => $payload['sr'] ?? '(none)',
        ]);
    }
}