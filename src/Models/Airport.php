<?php

namespace FLAIRUK\Airports\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for the optional airports table (see `php artisan airports:install`).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $country_code
 * @property string|null $city
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $type
 * @property bool $scheduled
 * @property string|null $icao
 */
class Airport extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'scheduled' => 'boolean',
        ];
    }

    public function getTable(): string
    {
        return config('airports.table', 'airports');
    }

    public function getConnectionName(): ?string
    {
        return $this->connection ?? config('airports.connection');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeCode(Builder $query, string $code): void
    {
        $query->where('code', strtoupper($code));
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeInCountry(Builder $query, string $countryCode): void
    {
        $query->where('country_code', strtoupper($countryCode));
    }

    /**
     * Airports with scheduled passenger service.
     *
     * @param  Builder<self>  $query
     */
    public function scopeScheduled(Builder $query): void
    {
        $query->where('scheduled', true);
    }
}
