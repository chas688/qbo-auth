<?php

namespace CharlesHarmon\QboAuth;

use Illuminate\Support\ServiceProvider;

class QboAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/qbo-auth.php', 'qbo-auth');
    }

    public function boot(): void
    {
        if (config('qbo-auth.register_routes', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'qbo-auth');

        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
        }
    }

    /**
     * Published migrations keep their package file name, so the migrator treats them as the same migration as the loaded one.
     */
    private function registerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/../config/qbo-auth.php' => config_path('qbo-auth.php'),
        ], 'qbo-auth-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/qbo-auth'),
        ], 'qbo-auth-views');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'qbo-auth-migrations');
    }
}
