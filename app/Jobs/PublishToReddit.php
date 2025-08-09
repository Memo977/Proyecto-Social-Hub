<?php

public function handle(\App\Services\RedditClient $client): void
{
    $account = SocialAccount::where('user_id', $this->post->user_id)
        ->where('provider', 'reddit')->first();

    if (!$account) return;

    $client->submitPost($account, [
        'sr'    => $this->post->reddit_subreddit, // campo en tu formulario/config
        'title' => $this->post->title,
        'kind'  => $this->post->link ? 'link' : 'self',
        'text'  => $this->post->content,
        'url'   => $this->post->link,
        'nsfw'  => (bool)$this->post->nsfw,
        'spoiler' => (bool)$this->post->spoiler,
    ]);
}
