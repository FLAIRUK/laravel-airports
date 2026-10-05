<?php

namespace FLAIRUK\Airports\Tests;

use FLAIRUK\Airports\AirportsServiceProvider;
use FLAIRUK\Airports\Facades\Airports;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [AirportsServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['Airports' => Airports::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
    }
}
