# Changelog

## 1.1.0 - 2026-10-27

Adds locations to the dataset. Backwards compatible with 1.0.

- City, latitude, longitude, type (`large`, `medium`, `small`, `heliport`, `seaplane`, `closed`), scheduled service and ICAO code on 8,423 of the 10,050 rows, from [OurAirports](https://ourairports.com/data/) (public domain). Every 1.0 row and id is kept; rows without a match have `null`s.
- `Airports::nearest($latitude, $longitude, $limit, $withinKm, $scheduledOnly, $types)`, nearest first and keyed by code.
- `Airport::distanceTo($latitude, $longitude)`: great-circle distance in km.
- `search()` also matches the city.
- `Airport::fromArray()` still accepts 1.0-shaped rows.
- Migration stub has the new columns; `add_location_columns_to_airports_table` upgrades a 1.0 table (published by `airports:install` or the `airports-upgrade-migration` tag). The seeder fills the new columns, and still seeds a table that has not been upgraded.
- Model casts for the new columns and a `scheduled()` scope.
- `bin/build-dataset.php` rebuilds the dataset from an OurAirports export.

## 1.0.0 - 2026-10-05

Complete rewrite for Laravel 12 and 13 (PHP 8.2+). See the upgrade guide in the README.

- In-memory lookup API (`find`, `findOrFail`, `exists`, `inCountry`, `search`, `options`, …) returning readonly `Airport` objects.
- `AirportCode` validation rule.
- Package auto-discovery; `FLAIRUK\Airports` namespace.
- Optional publishable migration, Eloquent model, idempotent seeder, `airports:install` and `airports:seed` commands.
- Dataset shipped as an opcache-friendly PHP array.
- Test suite and GitHub Actions CI.
