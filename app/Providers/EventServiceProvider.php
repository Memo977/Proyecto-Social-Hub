<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Reddit\RedditExtendSocialite;

/**
 * Proveedor de servicios para gestionar eventos y listeners en la aplicación.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * Mapeo de eventos a sus respectivos listeners.
     *
     * @var array
     */
    protected $listen = [
        SocialiteWasCalled::class => [
            RedditExtendSocialite::class . '@handle',
        ],
    ];
}