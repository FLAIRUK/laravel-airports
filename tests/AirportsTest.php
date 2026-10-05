<?php

namespace FLAIRUK\Airports\Tests;

use FLAIRUK\Airports\Airports as AirportsRepository;
use FLAIRUK\Airports\Data\Airport;
use FLAIRUK\Airports\Facades\Airports;
use FLAIRUK\Airports\Rules\AirportCode;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ItemNotFoundException;
use PHPUnit\Framework\Attributes\Test;

class AirportsTest extends TestCase
{
    #[Test]
    public function it_resolves_a_singleton_through_the_facade_and_alias(): void
    {
        $this->assertSame(app(AirportsRepository::class), app('airports'));
        $this->assertInstanceOf(AirportsRepository::class, Airports::getFacadeRoot());
    }

    #[Test]
    public function the_dataset_is_well_formed(): void
    {
        $airports = Airports::all();

        $this->assertGreaterThan(10000, $airports->count());
        $this->assertContainsOnlyInstancesOf(Airport::class, $airports);
        $this->assertSame($airports->count(), $airports->pluck('id')->unique()->count(), 'ids must be unique');

        $airports->each(function (Airport $airport, string $code) {
            $this->assertSame($code, $airport->code);
            $this->assertMatchesRegularExpression('/^[A-Z]{3}$/', $airport->code);
            $this->assertMatchesRegularExpression('/^[A-Z]{2}$/', $airport->countryCode);
            $this->assertNotSame('', $airport->name);
        });
    }

    #[Test]
    public function it_finds_airports_by_code_case_insensitively(): void
    {
        $airport = Airports::find(' lhr ');

        $this->assertSame('LHR', $airport->code);
        $this->assertSame('GB', $airport->countryCode);
        $this->assertNull(Airports::find('ZZ9'));
        $this->assertTrue(Airports::exists('lhr'));
        $this->assertFalse(Airports::exists('ZZ9'));
    }

    #[Test]
    public function find_or_fail_throws_for_unknown_codes(): void
    {
        $this->expectException(ItemNotFoundException::class);

        Airports::findOrFail('ZZ9');
    }

    #[Test]
    public function it_finds_by_id(): void
    {
        $first = Airports::all()->first();

        $this->assertSame($first, Airports::findById($first->id));
        $this->assertNull(Airports::findById(-1));
    }

    #[Test]
    public function it_filters_by_country(): void
    {
        $british = Airports::inCountry('gb');

        $this->assertTrue($british->has('LHR'));
        $this->assertTrue($british->every(fn (Airport $a) => $a->countryCode === 'GB'));
    }

    #[Test]
    public function it_searches_by_name_and_ranks_exact_code_matches_first(): void
    {
        $this->assertTrue(Airports::search('heathrow')->has('LHR'));
        $this->assertSame('LHR', Airports::search('lhr')->first()->code);
        $this->assertCount(0, Airports::search(''));
    }

    #[Test]
    public function it_builds_select_options(): void
    {
        $options = Airports::options();

        $this->assertSame(Airports::all()->count(), $options->count());
        $this->assertSame(Airports::find('LHR')->name, $options['LHR']);
        $this->assertSame(Airports::find('LHR')->name, Airports::options('id')[Airports::find('LHR')->id]);
    }

    #[Test]
    public function data_objects_serialise_to_snake_case_arrays(): void
    {
        $this->assertSame(
            ['code', 'name', 'country_code'],
            array_keys(collect(Airports::find('LHR')->toArray())->except('id')->all()),
        );
        $this->assertJson(json_encode(Airports::find('LHR')));
    }

    #[Test]
    public function the_validation_rule_accepts_known_codes_only(): void
    {
        $this->assertTrue(Validator::make(['airport' => 'lhr'], ['airport' => new AirportCode])->passes());
        $this->assertFalse(Validator::make(['airport' => 'ZZ9'], ['airport' => new AirportCode])->passes());
        $this->assertFalse(Validator::make(['airport' => null], ['airport' => new AirportCode])->passes());
    }
}
