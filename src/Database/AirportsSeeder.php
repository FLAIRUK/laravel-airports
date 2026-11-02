<?php

namespace FLAIRUK\Airports\Database;

use FLAIRUK\Airports\Airports;
use FLAIRUK\Airports\Data\Airport as AirportData;
use FLAIRUK\Airports\Models\Airport;
use Illuminate\Database\Seeder;

/**
 * Upserts the airport dataset into the airports table. Safe to run repeatedly.
 *
 * A table still on the 1.0 columns is seeded with those only, until the
 * upgrade migration adds the location columns.
 */
class AirportsSeeder extends Seeder
{
    public const COLUMNS = ['code', 'name', 'country_code'];

    public const LOCATION_COLUMNS = ['city', 'latitude', 'longitude', 'type', 'scheduled', 'icao'];

    public function run(Airports $airports): void
    {
        $model = new Airport;
        $columns = $model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'latitude')
            ? [...self::COLUMNS, ...self::LOCATION_COLUMNS]
            : self::COLUMNS;

        $airports->all()
            ->map(fn (AirportData $airport) => array_intersect_key($airport->toArray(), array_flip(['id', ...$columns])))
            ->chunk(500)
            ->each(fn ($chunk) => Airport::query()->upsert($chunk->values()->all(), ['id'], $columns));
    }
}
