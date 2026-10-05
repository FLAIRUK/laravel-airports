<?php

namespace FLAIRUK\Airports;

use Illuminate\Support\ServiceProvider;

class AirportsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/airports.php', 'airports');

        $this->app->singleton(Airports::class);
        $this->app->alias(Airports::class, 'airports');
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/airports.php' => config_path('airports.php'),
        ], 'airports-config');

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'airports-migrations');

        $this->commands([
            Console\InstallCommand::class,
            Console\SeedCommand::class,
        ]);
    }
}
