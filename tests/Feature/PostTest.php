<?php

use App\Jobs\PublishPost;
use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

function makeUserVerified(): User {
    return User::factory()->create(['email_verified_at' => now()]);
}

it('creates an immediate post and dispatches PublishPost', function () {
    $user = makeUserVerified();
    $account = SocialAccount::create([
        'user_id' => $user->id,
        'provider' => 'mastodon',
        'provider_user_id' => '123',
        'username' => 'tester',
        'instance_domain' => 'https://mastodon.social',
        'access_token' => 'fake',
    ]);

    Bus::fake();
    $resp = $this->actingAs($user)->post(route('posts.store'), [
        'content' => 'Hola mundo',
        'mode' => 'now',
        'targets' => [$account->id],
    ]);

    $resp->assertRedirect(route('dashboard'));
    Bus::assertDispatched(PublishPost::class);
    $this->assertDatabaseHas('posts', [
        'user_id' => $user->id,
        'status' => 'pending',
    ]);
});

it('validates reddit fields when selecting a reddit account', function () {
    $user = makeUserVerified();
    $reddit = SocialAccount::create([
        'user_id' => $user->id,
        'provider' => 'reddit',
        'provider_user_id' => 'u_1',
        'username' => 'u_tester',
        'instance_domain' => null,
        'access_token' => 'fake',
    ]);

    $resp = $this->actingAs($user)->from(route('posts.create'))->post(route('posts.store'), [
        'content' => 'Probando Reddit',
        'mode' => 'now',
        'targets' => [$reddit->id],
        // missing reddit_subreddit/title/kind
    ]);

    $resp->assertRedirect(route('posts.create'));
    $resp->assertSessionHasErrors(['reddit_subreddit','title','reddit_kind']);
});