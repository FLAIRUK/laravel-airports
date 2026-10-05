<?php

namespace FLAIRUK\Airports\Tests;

use FLAIRUK\Airports\Database\AirportsSeeder;
use FLAIRUK\Airports\Facades\Airports;
use FLAIRUK\Airports\Models\Airport;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;

class DatabaseTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    #[Test]
    public function the_seed_command_fills_the_table_and_is_idempotent(): void
    {
        $this->artisan('airports:seed')->assertSuccessful();
        $this->artisan('airports:seed')->assertSuccessful();

        $this->assertSame(Airports::all()->count(), Airport::count());
        $this->assertSame('GB', Airport::code('lhr')->first()->country_code);
        $this->assertTrue(Airport::inCountry('GB')->exists());
    }

    #[Test]
    public function the_seeder_can_be_called_from_an_application_seeder(): void
    {
        $this->seed(AirportsSeeder::class);

        $this->assertSame(Airports::all()->count(), Airport::count());
    }

    #[Test]
    public function prune_removes_rows_that_are_not_in_the_dataset(): void
    {
        Airport::create(['id' => 999999, 'code' => 'ZZ9', 'name' => 'Defunct Airport', 'country_code' => 'GB']);

        $this->artisan('airports:seed', ['--prune' => true])->assertSuccessful();

        $this->assertNull(Airport::find(999999));
        $this->assertSame(Airports::all()->count(), Airport::count());
    }

    #[Test]
    public function the_table_name_is_configurable(): void
    {
        config(['airports.table' => 'iata_airports']);
        (require __DIR__.'/../database/migrations/create_airports_table.php')->up();

        $this->artisan('airports:seed')->assertSuccessful();

        $this->assertTrue(Schema::hasTable('iata_airports'));
        $this->assertSame(Airports::all()->count(), Airport::count());
    }

    #[Test]
    public function install_publishes_the_config_and_a_timestamped_migration(): void
    {
        $migrations = database_path('migrations');
        File::delete(File::glob($migrations.'/*_create_airports_table.php'));
        File::delete(config_path('airports.php'));

        $this->artisan('airports:install')
            ->expectsConfirmation('Run the migration and seed the airports table now?', 'no')
            ->assertSuccessful();

        $this->assertFileExists(config_path('airports.php'));
        $this->assertCount(1, File::glob($migrations.'/*_create_airports_table.php'));

        File::delete(File::glob($migrations.'/*_create_airports_table.php'));
        File::delete(config_path('airports.php'));
    }
}
