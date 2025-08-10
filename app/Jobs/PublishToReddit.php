<?php

namespace App\Jobs;

use App\Services\RedditClient;
use App\Models\SocialAccount;

class PublishToReddit
{
    public $post;

    public function __construct($post)
    {
        $this->post = $post;
    }

    public function handle(RedditClient $client): void
    {
        $account = SocialAccount::where('user_id', $this->post->user_id)
            ->where('provider', 'reddit')->first();

        if (!$account) return;

        $client->submitPost($account, [
            'sr'    => $this->post->reddit_subreddit,
            'title' => $this->post->title,
            'kind'  => $this->post->link ? 'link' : 'self',
            'text'  => $this->post->content,
            'url'   => $this->post->link,
            'nsfw'  => (bool)$this->post->nsfw,
            'spoiler' => (bool)$this->post->spoiler,
        ]);
    }
}