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
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'qbo-auth');

        $this->publishes([
            __DIR__.'/../config/qbo-auth.php' => config_path('qbo-auth.php'),
        ], 'qbo-auth-config');
    }
}
