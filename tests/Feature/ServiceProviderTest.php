<?php

namespace CharlesHarmon\QboAuth\Tests\Feature;

use CharlesHarmon\QboAuth\QboAuthServiceProvider;
use CharlesHarmon\QboAuth\Tests\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Orchestra\Testbench\Attributes\DefineEnvironment;

class ServiceProviderTest extends TestCase
{
    public function test_routes_use_the_default_prefix(): void
    {
        $this->assertSame('/oauth/quickbooks/connect', route('qbo-auth.connect', [], false));
    }

    #[DefineEnvironment('useCustomPrefix')]
    public function test_routes_use_the_configured_prefix(): void
    {
        $this->assertSame('/integrations/qbo/connect', route('qbo-auth.connect', [], false));
    }

    #[DefineEnvironment('disableRoutes')]
    public function test_routes_can_be_disabled(): void
    {
        $this->assertFalse(Route::has('qbo-auth.connect'));
    }

    public function test_views_and_migrations_are_publishable(): void
    {
        $views = ServiceProvider::pathsToPublish(QboAuthServiceProvider::class, 'qbo-auth-views');
        $migrations = ServiceProvider::pathsToPublish(QboAuthServiceProvider::class, 'qbo-auth-migrations');

        $this->assertSame([resource_path('views/vendor/qbo-auth')], array_values($views));
        $this->assertSame([database_path('migrations')], array_values($migrations));
    }

    protected function useCustomPrefix($app): void
    {
        $app['config']->set('qbo-auth.route_prefix', 'integrations/qbo');
    }

    protected function disableRoutes($app): void
    {
        $app['config']->set('qbo-auth.register_routes', false);
    }
}
