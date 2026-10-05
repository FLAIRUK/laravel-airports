<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="art/logo-dark.svg">
    <img src="art/logo-light.svg" alt="Laravel Airports" width="420">
  </picture>
</p>

<h2 align="center">
  <a href="https://www.php.net/" target="_blank"><img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat&logo=php&logoColor=white" alt="PHP 8.2+"></a>&nbsp;
  <a href="https://laravel.com/docs/" target="_blank"><img src="https://img.shields.io/badge/Laravel-12%20%7C%2013-FF2D20?style=flat&logo=laravel&logoColor=white" alt="Laravel 12 or 13"></a>&nbsp;
  <a href="https://github.com/FLAIRUK/laravel-airports/actions/workflows/tests.yml" target="_blank"><img src="https://img.shields.io/badge/Lint-%E2%9C%93-2EA043?style=flat&logo=githubactions&logoColor=white" alt="Lint"></a>&nbsp;
  <a href="https://github.com/FLAIRUK/laravel-airports/actions/workflows/tests.yml" target="_blank"><img src="https://img.shields.io/badge/Tests-%E2%9C%93-2EA043?style=flat&logo=githubactions&logoColor=white" alt="Tests"></a>&nbsp;
  <a href="https://packagist.org/packages/flairuk/laravel-airports" target="_blank"><img src="https://img.shields.io/packagist/dt/flairuk/laravel-airports?style=flat&logo=packagist&logoColor=white&label=Downloads&color=F28D1A" alt="Downloads on Packagist"></a>&nbsp;
  <a href="https://github.com/FLAIRUK/laravel-airports/blob/main/LICENSE" target="_blank"><img src="https://img.shields.io/github/license/FLAIRUK/laravel-airports?style=flat&label=License&color=3DA639" alt="MIT licence"></a>&nbsp;
  <a href="https://www.iata.org/en/publications/directories/code-search/" target="_blank"><img src="https://img.shields.io/badge/Data-IATA-EA580C?style=flat" alt="IATA"></a>&nbsp;
  <br>&nbsp;
</h2>

**Laravel Airports** — Over 10,000 IATA airport codes (`LHR`, `JFK`, `DXB`, …) for Laravel 12 and 13.

- **No database required.** Look airports up through a facade backed by an in-memory dataset.
- **Typed results.** Every lookup returns readonly `Airport` objects in Laravel collections keyed by code.
- **Validation rule.** `new AirportCode` accepts known codes only.
- **Optional table.** Publish a migration and seed an `airports` table when other tables need to reference airports.

<p align="center">
  📦&nbsp;<a href="#-installation">Installation</a> ·
  🚀&nbsp;<a href="#-usage">Usage</a> ·
  💾&nbsp;<a href="#-database-table-optional">Database table</a> ·
  🔄&nbsp;<a href="#-upgrading-from-dev-master">Upgrading</a>
</p>

<br><br>

## 📦 Installation

```bash
composer require flairuk/laravel-airports
```

Requires PHP 8.2 or later with Laravel 12, or PHP 8.3 or later with Laravel 13.

Laravel discovers the service provider and the `Airports` facade automatically.

<br><br>

## 🚀 Usage

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

`AirportCode` ignores case but `different` does not, so `LHR` and `lhr` would pass as different airports. Uppercase both fields first (for example in a form request's `prepareForValidation()`) when that matters.

### Dependency injection

The facade resolves a singleton `FLAIRUK\Airports\Airports`, which you can type-hint instead.

<br><br>

## 💾 Database table (optional)

```bash
php artisan airports:install             # publish config + migration, then ask to migrate and seed
php artisan airports:install --migrate   # migrate and seed without asking
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

<br><br>

## 🔄 Upgrading from dev-master

Version 1.0 is a rewrite. Breaking changes:

| dev-master | 1.0 |
| --- | --- |
| Package `ijeffro/laravel-airports` | `flairuk/laravel-airports` |
| `ijeffro\Airports\…` namespace | `FLAIRUK\Airports\…` |
| Facade `ijeffro\Airports\AirportsFacade` | `FLAIRUK\Airports\Facades\Airports` (auto-discovered) |
| `Airports::getList($sort)` (array) | `Airports::all()->sortBy($property, SORT_NATURAL \| SORT_FLAG_CASE)` (Collection of `Airport`; properties are camelCase, e.g. `countryCode`) |
| `Airports::getOne($id)` | `Airports::findById($id)` or `Airports::find($code)` |
| `Airports::getListForSelect()` (keyed by id) | `Airports::options('id')` |
| `php artisan airports:migration` | `php artisan airports:install` / `airports:seed` |
| Config key `airports.table_name` | `airports.table` |

Row `id`s and columns (`code`, `name`, `country_code`) are unchanged, so existing tables and foreign keys stay valid.

<br><br>

## 🧪 Testing

```bash
composer test
```

<br><br>

## 📄 License

MIT. See [LICENSE](LICENSE).
