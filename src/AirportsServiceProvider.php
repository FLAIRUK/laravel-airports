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

        // Adds the 1.1 columns to a table created by the 1.0 migration.
        $this->publishesMigrations([
            __DIR__.'/../database/migrations/add_location_columns_to_airports_table.php' => database_path('migrations/add_location_columns_to_airports_table.php'),
        ], 'airports-upgrade-migration');

        $this->commands([
            Console\InstallCommand::class,
            Console\SeedCommand::class,
        ]);
    }
}
