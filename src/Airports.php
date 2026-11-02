<?php

namespace FLAIRUK\Airports;

use FLAIRUK\Airports\Data\Airport;
use Illuminate\Support\Collection;
use Illuminate\Support\ItemNotFoundException;

/**
 * In-memory lookup of IATA airport codes.
 *
 * The dataset is loaded lazily on first use and kept for the lifetime of the
 * instance (bound as a singleton), so lookups never touch the database.
 */
class Airports
{
    /** @var Collection<string, Airport>|null */
    protected ?Collection $airports = null;

    /**
     * Open airports with coordinates, as [latitude, longitude, cos(latitude), Airport] in radians.
     *
     * @var list<array{float, float, float, Airport}>|null
     */
    protected ?array $located = null;

    public function __construct(
        protected string $path = __DIR__.'/../data/airports.php',
    ) {}

    /**
     * Every airport, keyed by IATA code.
     *
     * @return Collection<string, Airport>
     */
    public function all(): Collection
    {
        return $this->airports ??= collect(require $this->path)
            ->mapWithKeys(fn (array $row) => [$row['code'] => Airport::fromArray($row)]);
    }

    /**
     * Find an airport by its three-letter IATA code, e.g. "LHR".
     */
    public function find(string $code): ?Airport
    {
        return $this->all()->get(strtoupper(trim($code)));
    }

    /**
     * @throws ItemNotFoundException
     */
    public function findOrFail(string $code): Airport
    {
        return $this->find($code) ?? throw new ItemNotFoundException("Unknown airport code [{$code}].");
    }

    public function findById(int $id): ?Airport
    {
        return $this->all()->firstWhere('id', $id);
    }

    public function exists(string $code): bool
    {
        return $this->all()->has(strtoupper(trim($code)));
    }

    /**
     * Airports in the given ISO 3166-1 alpha-2 country, e.g. "GB".
     *
     * @return Collection<string, Airport>
     */
    public function inCountry(string $countryCode): Collection
    {
        return $this->all()->where('countryCode', strtoupper($countryCode));
    }

    /**
     * Case-insensitive match against the code, name or city; an exact code match is ranked first.
     *
     * @return Collection<string, Airport>
     */
    public function search(string $term): Collection
    {
        $term = trim($term);

        if ($term === '') {
            return new Collection;
        }

        $exact = strtoupper($term);

        return $this->all()
            ->filter(fn (Airport $airport) => $airport->code === $exact
                || mb_stripos($airport->name, $term) !== false
                || ($airport->city !== null && mb_stripos($airport->city, $term) !== false))
            ->sortBy(fn (Airport $airport) => $airport->code === $exact ? 0 : 1);
    }

    /**
     * The airports nearest to a point, nearest first. Airports without coordinates and closed ones are skipped.
     *
     * @param  int  $limit  At most this many airports
     * @param  float|null  $withinKm  Only airports within this great-circle distance
     * @param  bool  $scheduledOnly  Only airports with scheduled passenger service
     * @param  list<string>|null  $types  Only these types, e.g. ['large', 'medium']
     * @return Collection<string, Airport>
     */
    public function nearest(
        float $latitude,
        float $longitude,
        int $limit = 5,
        ?float $withinKm = null,
        bool $scheduledOnly = false,
        ?array $types = null,
    ): Collection {
        if ($limit < 1) {
            return new Collection;
        }

        $lat = deg2rad($latitude);
        $lng = deg2rad($longitude);
        $cosLat = cos($lat);

        // Latitude alone bounds the distance, so a band check skips most of the world cheaply.
        $band = $withinKm === null ? null : $withinKm / Airport::EARTH_RADIUS_KM;
        $distances = [];
        $airports = [];

        foreach ($this->located() as [$aLat, $aLng, $aCos, $airport]) {
            if ($band !== null && abs($aLat - $lat) > $band) {
                continue;
            }

            if (($scheduledOnly && ! $airport->scheduled) || ($types !== null && ! in_array($airport->type, $types, true))) {
                continue;
            }

            $a = sin(($aLat - $lat) / 2) ** 2 + $cosLat * $aCos * sin(($aLng - $lng) / 2) ** 2;
            $distance = 2 * Airport::EARTH_RADIUS_KM * asin(min(1.0, sqrt($a)));

            if ($withinKm !== null && $distance > $withinKm) {
                continue;
            }

            $distances[$airport->code] = $distance;
            $airports[$airport->code] = $airport;
        }

        asort($distances);

        return (new Collection(array_slice($distances, 0, $limit, true)))
            ->map(fn (float $distance, string $code) => $airports[$code]);
    }

    /**
     * @return list<array{float, float, float, Airport}>
     */
    protected function located(): array
    {
        return $this->located ??= $this->all()
            ->filter(fn (Airport $airport) => $airport->latitude !== null && $airport->longitude !== null && $airport->type !== 'closed')
            ->map(fn (Airport $airport) => [
                $lat = deg2rad($airport->latitude),
                deg2rad($airport->longitude),
                cos($lat),
                $airport,
            ])
            ->values()
            ->all();
    }

    /**
     * Key/label pairs for a <select>, sorted by label.
     *
     * @return Collection<int|string, string>
     */
    public function options(string $key = 'code', string $label = 'name'): Collection
    {
        return $this->all()->sortBy($label, SORT_NATURAL | SORT_FLAG_CASE)->pluck($label, $key);
    }

    /**
     * @return list<string>
     */
    public function codes(): array
    {
        return $this->all()->keys()->all();
    }
}
