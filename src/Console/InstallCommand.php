<?php

namespace FLAIRUK\Airports\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'airports:install')]
class InstallCommand extends Command
{
    protected $signature = 'airports:install
                            {--migrate : Run the migration and seed the table without prompting}';

    protected $description = 'Publish the airports config and migration, then optionally migrate and seed';

    public function handle(Filesystem $files): int
    {
        $this->call('vendor:publish', ['--tag' => 'airports-config']);

        if (! $this->publishMigration($files, 'create_airports_table')) {
            $this->publishMigration($files, 'add_location_columns_to_airports_table');
        }

        if ($this->option('migrate') || $this->confirm('Run the migration and seed the airports table now?', true)) {
            $this->call('migrate');
            $this->call('airports:seed');
        }

        $this->components->info('Laravel Airports installed.');

        return self::SUCCESS;
    }

    /**
     * Copy a migration unless one with the same name is already published. Returns whether it copied.
     *
     * The upgrade migration is only needed by tables created from the 1.0 migration,
     * so it is published only when the create migration is already there.
     */
    protected function publishMigration(Filesystem $files, string $name): bool
    {
        $directory = $this->laravel->databasePath('migrations');

        if ($files->glob($directory.'/*_'.$name.'.php')) {
            $this->components->info("Migration [{$name}] already published.");

            return false;
        }

        $files->ensureDirectoryExists($directory);
        $files->copy(
            __DIR__.'/../../database/migrations/'.$name.'.php',
            $target = $directory.'/'.date('Y_m_d_His').'_'.$name.'.php',
        );

        $this->components->info('Published migration ['.basename($target).'].');

        return true;
    }
}
