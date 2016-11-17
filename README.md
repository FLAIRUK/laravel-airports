# Laravel Airports

[![Latest Stable Version](https://poser.pugx.org/ijeffro/laravel-airports/v/stable)](https://packagist.org/packages/ijeffro/laravel-airports)
[![Total Downloads](https://poser.pugx.org/ijeffro/laravel-airports/downloads)](https://packagist.org/packages/ijeffro/laravel-airports)
[![Latest Unstable Version](https://poser.pugx.org/ijeffro/laravel-airports/v/unstable)](https://packagist.org/packages/ijeffro/laravel-airports)
[![License](https://poser.pugx.org/ijeffro/laravel-airports/license)](https://packagist.org/packages/ijeffro/laravel-airports)

Laravel Airports is a bundle for Laravel, providing Iata Code ISO 3166_3 and country codes for all the airports.

**Please note that the dev-master version is for Laravel 5 only**

## Installation

Run `composer require ijeffro/laravel-airports dev-master` in your Laravel root directory to install the latest version.

Or add `ijeffro/laravel-airports` to `composer.json`.

    "ijeffro/laravel-airports": "dev-master"

Run `composer update` to pull down the latest version of Airport List.

Edit `app/config/app.php` and add the `provider` and `filter`

    'providers' => [
        ijeffro\Airports\AirportsServiceProvider::class,
    ]

Now add the alias.

    'aliases' => [
        'Airports' => ijeffro\Airports\AirportsFacade::class,
    ]


## Model

You can start by publishing the configuration. This is an optional step, it contains the table name and does not need to be altered. If the default name `airports` suits you, leave it. Otherwise run the following command

    $ php artisan vendor:publish

Next generate the migration file:

    $ php artisan airports:migration

It will generate the `<timestamp>_setup_airports_table.php` migration and the `AirportsSeeder.php` seeder. To make sure the data is seeded insert the following code in the `seeds/DatabaseSeeder.php`

    //Seed the airports
    $this->call('AirportsSeeder');
    $this->command->info('Seeded the airports!');

You may now run it with the artisan migrate command:

    $ php artisan migrate --seed

After running this command the filled airports table will be available
