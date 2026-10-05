<?php

namespace FLAIRUK\Airports\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection<string, \FLAIRUK\Airports\Data\Airport> all()
 * @method static \FLAIRUK\Airports\Data\Airport|null find(string $code)
 * @method static \FLAIRUK\Airports\Data\Airport findOrFail(string $code)
 * @method static \FLAIRUK\Airports\Data\Airport|null findById(int $id)
 * @method static bool exists(string $code)
 * @method static \Illuminate\Support\Collection<string, \FLAIRUK\Airports\Data\Airport> inCountry(string $countryCode)
 * @method static \Illuminate\Support\Collection<string, \FLAIRUK\Airports\Data\Airport> search(string $term)
 * @method static \Illuminate\Support\Collection<int|string, string> options(string $key = 'code', string $label = 'name')
 * @method static list<string> codes()
 *
 * @see \FLAIRUK\Airports\Airports
 */
class Airports extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \FLAIRUK\Airports\Airports::class;
    }
}
