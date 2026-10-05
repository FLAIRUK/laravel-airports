<?php

namespace FLAIRUK\Airports\Rules;

use Closure;
use FLAIRUK\Airports\Airports;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that the value is a known IATA airport designator.
 */
class AirportCode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! app(Airports::class)->exists($value)) {
            $fail('The :attribute must be a valid IATA airport code.');
        }
    }
}
