# Changelog

## 2.0.0 - Unreleased

Complete rewrite for Laravel 12 and 13 (PHP 8.2+). See the upgrade guide in the README.

- In-memory lookup API (`find`, `findOrFail`, `exists`, `inCountry`, `search`, `options`, …) returning readonly `Airport` objects.
- `AirportCode` validation rule.
- Package auto-discovery; `FLAIRUK\Airports` namespace.
- Optional publishable migration, Eloquent model, idempotent seeder, `airports:install` and `airports:seed` commands.
- Dataset shipped as an opcache-friendly PHP array.
- Test suite and GitHub Actions CI.
