<?php

namespace FLAIRUK\Airports\Console;

use FLAIRUK\Airports\Airports;
use FLAIRUK\Airports\Database\AirportsSeeder;
use FLAIRUK\Airports\Models\Airport;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'airports:seed')]
class SeedCommand extends Command
{
    protected $signature = 'airports:seed
                            {--prune : Delete rows that are no longer in the dataset}';

    protected $description = 'Insert or update the airports table from the bundled dataset';

    public function handle(Airports $airports): int
    {
        $this->laravel->call([$this->laravel->make(AirportsSeeder::class), 'run']);

        if ($this->option('prune')) {
            $pruned = Airport::query()->whereNotIn('id', $airports->all()->pluck('id'))->delete();
            $this->components->info("Pruned {$pruned} stale airports.");
        }

        $this->components->info("Seeded {$airports->all()->count()} airports.");

        return self::SUCCESS;
    }
}
