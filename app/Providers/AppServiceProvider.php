<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Reddit\Provider as RedditProvider;

/**
 * Proveedor de servicios principal para la configuración de la aplicación.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra servicios en el contenedor de la aplicación.
     *
     * @return void
     */
    public function register(): void {}

    /**
     * Configura eventos y extensiones durante el arranque de la aplicación.
     *
     * @return void
     */
    public function boot(): void
    {
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('reddit', RedditProvider::class);
        });
    }
}