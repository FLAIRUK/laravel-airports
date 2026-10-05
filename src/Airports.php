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
     * Case-insensitive match against the code or name; an exact code match is ranked first.
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
            ->filter(fn (Airport $airport) => $airport->code === $exact || mb_stripos($airport->name, $term) !== false)
            ->sortBy(fn (Airport $airport) => $airport->code === $exact ? 0 : 1);
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
