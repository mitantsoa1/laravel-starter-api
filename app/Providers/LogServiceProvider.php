<?php

namespace App\Providers;

use App\Services\LogService;
use Illuminate\Support\ServiceProvider;

class LogServiceProvider extends ServiceProvider
{
    /**
     * Enregistre le LogService comme singleton dans le conteneur IoC.
     * Une seule instance est partagée pour toute la durée de la requête.
     */
    public function register(): void
    {
        $this->app->singleton(LogService::class, function () {
            return new LogService();
        });
    }

    public function boot(): void
    {
        // Rien à démarrer pour ce service.
    }
}
