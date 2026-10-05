<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="art/logo-dark.svg">
    <img src="art/logo-light.svg" alt="Laravel Airports" width="420">
  </picture>
</p>

[![Tests](https://github.com/FLAIRUK/laravel-airports/actions/workflows/tests.yml/badge.svg)](https://github.com/FLAIRUK/laravel-airports/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/ijeffro/laravel-airports/v/stable)](https://packagist.org/packages/ijeffro/laravel-airports)
[![License](https://poser.pugx.org/ijeffro/laravel-airports/license)](https://packagist.org/packages/ijeffro/laravel-airports)

Over 10,000 IATA airport codes (`LHR`, `JFK`, `DXB`, …) for Laravel 12 and 13.

- **No database required.** Look airports up through a facade backed by an in-memory dataset.
- **Typed results.** Every lookup returns readonly `Airport` objects in Laravel collections keyed by code.
- **Validation rule.** `new AirportCode` accepts known codes only.
- **Optional table.** Publish a migration and seed an `airports` table when other tables need to reference airports.

## Installation

```bash
composer require ijeffro/laravel-airports
```

Laravel discovers the service provider and the `Airports` facade automatically.

## Usage

```php
use FLAIRUK\Airports\Facades\Airports;

Airports::find('lhr');           // Airport { id: 4329, code: "LHR", name: "Heathrow", countryCode: "GB" }
Airports::findOrFail('LHR');     // throws ItemNotFoundException for unknown codes
Airports::exists('JFK');         // true
Airports::findById(4329);

Airports::all();                 // Collection<string, Airport> keyed by code
Airports::inCountry('GB');       // airports in the UK
Airports::search('heathrow');    // matches on name or exact code
Airports::codes();               // ['AAA', 'AAB', ...]
```

### Select options

```php
Airports::options();             // ['LHR' => 'Heathrow', ...] sorted by name
Airports::options('id');         // [4329 => 'Heathrow', ...]
```

### Validation

```php
use FLAIRUK\Airports\Rules\AirportCode;

$request->validate([
    'origin' => ['required', new AirportCode],
    'destination' => ['required', new AirportCode, 'different:origin'],
]);
```

### Dependency injection

The facade resolves a singleton `FLAIRUK\Airports\Airports`, which you can type-hint instead.

## Database table (optional)

```bash
php artisan airports:install         # publish config + migration, then migrate and seed
php artisan airports:seed            # insert / update (safe to re-run)
php artisan airports:seed --prune    # also delete rows no longer in the dataset
```

You can also call the seeder from your own `DatabaseSeeder`:

```php
$this->call(\FLAIRUK\Airports\Database\AirportsSeeder::class);
```

Query the table through the bundled Eloquent model:

```php
use FLAIRUK\Airports\Models\Airport;

Airport::code('LHR')->first();
Airport::inCountry('GB')->orderBy('name')->get();
```

The table name and connection come from `AIRPORTS_TABLE` and `AIRPORTS_DB_CONNECTION`, or from the published config.

## Upgrading from 1.x / dev-master

Version 2 is a rewrite. Breaking changes:

| 1.x | 2.x |
| --- | --- |
| `ijeffro\Airports\…` namespace | `FLAIRUK\Airports\…` |
| Facade `ijeffro\Airports\AirportsFacade` | `FLAIRUK\Airports\Facades\Airports` (auto-discovered) |
| `Airports::getList($sort)` (array) | `Airports::all()->sortBy($sort)` (Collection of `Airport`) |
| `Airports::getOne($id)` | `Airports::findById($id)` or `Airports::find($code)` |
| `Airports::getListForSelect()` | `Airports::options()` |
| `php artisan airports:migration` | `php artisan airports:install` / `airports:seed` |
| Config key `airports.table_name` | `airports.table` |

Row `id`s and columns (`code`, `name`, `country_code`) are unchanged, so existing tables and foreign keys stay valid.

## Testing

```bash
composer test
```

## License

MIT. See [LICENSE](LICENSE).
