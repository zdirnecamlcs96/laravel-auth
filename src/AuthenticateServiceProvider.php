<?php

namespace Zdirnecamlcs96\Auth;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;

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
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/authentication.php' => config_path('authentication.php'),
            ]);
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'authentication');
        $this->registerRoutes();

        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            return url(route(config('authentication.reset_password.password_reset_route'), [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/authentication.php', 'authentication');
    }

    protected function registerRoutes()
    {
        Route::domain(config('authentication.endpoint'))
        ->group(function() {
            Route::group([
                "as" => "authentication.",
                "middleware" => "api"
            ], function() {
                $this->loadRoutesFrom(__DIR__.'/../routes/authentication.php');
            });
        });
    }
}