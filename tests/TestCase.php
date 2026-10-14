<?php

namespace CharlesHarmon\QboAuth\Tests;

use CharlesHarmon\QboAuth\QboAuthServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /** @return array<int, class-string> */
    protected function getPackageProviders($app): array
    {
        return [QboAuthServiceProvider::class];
    }
}
