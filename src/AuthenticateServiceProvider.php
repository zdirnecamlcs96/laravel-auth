<?php

namespace Zdirnecamlcs96\Auth;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * Service provider
 */
class AuthenticateServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->mergeConfigFrom(__DIR__.'/../config/auth.php', 'auth');
        $this->registerRoutes();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    protected function registerRoutes()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/auth.php');
    }
}