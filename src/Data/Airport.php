<?php

namespace FLAIRUK\Airports\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<string, int|string|float|bool|null>
 */
final readonly class Airport implements Arrayable, JsonSerializable
{
    /** Mean radius of the Earth, in kilometres. */
    public const EARTH_RADIUS_KM = 6371.0088;

    /**
     * @param  'large'|'medium'|'small'|'heliport'|'seaplane'|'closed'|null  $type
     */
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public string $countryCode,
        public ?string $city = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $type = null,
        public bool $scheduled = false,
        public ?string $icao = null,
    ) {}

    /**
     * Accepts 1.0 rows (id, code, name, country_code) as well as the 1.1 shape.
     *
     * @param  array{id: int, code: string, name: string, country_code: string, city?: ?string, latitude?: ?float, longitude?: ?float, type?: ?string, scheduled?: bool, icao?: ?string}  $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: $row['id'],
            code: $row['code'],
            name: $row['name'],
            countryCode: $row['country_code'],
            city: $row['city'] ?? null,
            latitude: isset($row['latitude']) ? (float) $row['latitude'] : null,
            longitude: isset($row['longitude']) ? (float) $row['longitude'] : null,
            type: $row['type'] ?? null,
            scheduled: (bool) ($row['scheduled'] ?? false),
            icao: $row['icao'] ?? null,
        );
    }

    /**
     * Great-circle distance to the point in kilometres, or null when the airport has no coordinates.
     */
    public function distanceTo(float $latitude, float $longitude): ?float
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        return self::haversine(deg2rad($this->latitude), deg2rad($this->longitude), deg2rad($latitude), deg2rad($longitude));
    }

    /**
     * Haversine distance in kilometres between two points given in radians.
     *
     * @internal
     */
    public static function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $a = sin(($lat2 - $lat1) / 2) ** 2 + cos($lat1) * cos($lat2) * sin(($lng2 - $lng1) / 2) ** 2;

        return 2 * self::EARTH_RADIUS_KM * asin(min(1.0, sqrt($a)));
    }

    /**
     * @return array{id: int, code: string, name: string, country_code: string, city: ?string, latitude: ?float, longitude: ?float, type: ?string, scheduled: bool, icao: ?string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'country_code' => $this->countryCode,
            'city' => $this->city,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'type' => $this->type,
            'scheduled' => $this->scheduled,
            'icao' => $this->icao,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
