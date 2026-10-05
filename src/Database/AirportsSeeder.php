<?php

namespace FLAIRUK\Airports\Database;

use FLAIRUK\Airports\Airports;
use FLAIRUK\Airports\Data\Airport as AirportData;
use FLAIRUK\Airports\Models\Airport;
use Illuminate\Database\Seeder;

/**
 * Upserts the airport dataset into the airports table. Safe to run repeatedly.
 */
class AirportsSeeder extends Seeder
{
    public function run(Airports $airports): void
    {
        $airports->all()
            ->map(fn (AirportData $airport) => $airport->toArray())
            ->chunk(500)
            ->each(fn ($chunk) => Airport::query()->upsert(
                $chunk->values()->all(),
                ['id'],
                ['code', 'name', 'country_code'],
            ));
    }
}
