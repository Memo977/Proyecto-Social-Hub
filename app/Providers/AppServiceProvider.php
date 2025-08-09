<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event; // ✅ importa el Facade correcto
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Reddit\Provider as RedditProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('reddit', RedditProvider::class);
        });
    }
}
