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
    public function the_dataset_carries_locations_from_ourairports(): void
    {
        $heathrow = Airports::find('LHR');

        $this->assertSame('London', $heathrow->city);
        $this->assertEqualsWithDelta(51.4707, $heathrow->latitude, 0.01);
        $this->assertEqualsWithDelta(-0.4599, $heathrow->longitude, 0.01);
        $this->assertSame('large', $heathrow->type);
        $this->assertTrue($heathrow->scheduled);
        $this->assertSame('EGLL', $heathrow->icao);

        // Rows OurAirports cannot place keep their id, code and name, without coordinates.
        $unplaced = Airports::all()->whereNull('latitude');
        $this->assertGreaterThan(0, $unplaced->count());
        $this->assertLessThan(Airports::all()->count() / 4, $unplaced->count());

        Airports::all()->each(function (Airport $airport) {
            $this->assertContains($airport->type, [null, 'large', 'medium', 'small', 'heliport', 'seaplane', 'closed']);
            $this->assertSame($airport->latitude === null, $airport->longitude === null);
        });
    }

    #[Test]
    public function it_searches_by_city(): void
    {
        $london = Airports::search('london');

        $this->assertTrue($london->has('LHR'));
        $this->assertTrue($london->has('LGW'));
    }

    #[Test]
    public function it_finds_the_nearest_airports_to_a_point(): void
    {
        $nearest = Airports::nearest(51.754, -1.254);

        $this->assertCount(5, $nearest);
        $this->assertSame('OXF', $nearest->keys()->first());
        $this->assertEqualsWithDelta(10.3, $nearest->first()->distanceTo(51.754, -1.254), 0.5);

        // Oxford is ringed by airfields, so Heathrow only makes the top five among scheduled airports.
        $this->assertTrue(Airports::nearest(51.754, -1.254, scheduledOnly: true)->has('LHR'));

        $distances = $nearest->map(fn (Airport $airport) => $airport->distanceTo(51.754, -1.254))->values()->all();
        $sorted = $distances;
        sort($sorted);
        $this->assertSame($sorted, $distances, 'nearest first');

        $this->assertTrue($nearest->every(fn (Airport $airport, string $code) => $code === $airport->code && $airport->type !== 'closed'));
        $this->assertCount(0, Airports::nearest(51.754, -1.254, limit: 0));
    }

    #[Test]
    public function nearest_can_be_limited_to_scheduled_service(): void
    {
        $scheduled = Airports::nearest(51.754, -1.254, limit: 8, scheduledOnly: true);

        $this->assertCount(8, $scheduled);
        $this->assertFalse($scheduled->has('OXF'), 'Oxford has no scheduled service');
        $this->assertSame(['LTN', 'LHR'], $scheduled->keys()->take(2)->all());
        $this->assertTrue($scheduled->every(fn (Airport $airport) => $airport->scheduled));
    }

    #[Test]
    public function nearest_can_be_limited_by_distance(): void
    {
        $within = Airports::nearest(51.754, -1.254, limit: 100, withinKm: 50);

        $this->assertTrue($within->has('OXF'));
        $this->assertFalse($within->has('LGW'), 'Gatwick is over 50 km from Oxford');
        $this->assertTrue($within->every(fn (Airport $airport) => $airport->distanceTo(51.754, -1.254) <= 50));
        $this->assertCount(0, Airports::nearest(0.0, -160.0, withinKm: 1));
    }

    #[Test]
    public function nearest_can_be_limited_by_type(): void
    {
        $large = Airports::nearest(51.754, -1.254, limit: 3, types: ['large']);

        $this->assertCount(3, $large);
        $this->assertContains('LHR', $large->keys()->all());
        $this->assertTrue($large->every(fn (Airport $airport) => $airport->type === 'large'));
    }

    #[Test]
    public function distance_to_is_the_great_circle_distance_in_kilometres(): void
    {
        // Heathrow to JFK is about 5,540 km.
        $jfk = Airports::find('JFK');
        $this->assertEqualsWithDelta(5540, Airports::find('LHR')->distanceTo($jfk->latitude, $jfk->longitude), 15);
        $this->assertEqualsWithDelta(0.0, Airports::find('LHR')->distanceTo(51.470748, -0.459909), 0.001);
        $this->assertNull(Airports::all()->whereNull('latitude')->first()->distanceTo(51.5, -0.1));
    }

    #[Test]
    public function data_objects_accept_1_0_rows(): void
    {
        $airport = Airport::fromArray(['id' => 4329, 'code' => 'LHR', 'name' => 'Heathrow', 'country_code' => 'GB']);

        $this->assertNull($airport->city);
        $this->assertNull($airport->latitude);
        $this->assertNull($airport->type);
        $this->assertFalse($airport->scheduled);
        $this->assertNull($airport->icao);
        $this->assertNull($airport->distanceTo(51.5, -0.1));

        $file = tempnam(sys_get_temp_dir(), 'airports');
        file_put_contents($file, '<?php return '.var_export([['id' => 1, 'code' => 'AAA', 'name' => 'Anaa', 'country_code' => 'PF']], true).';');

        $repository = new AirportsRepository($file);
        $this->assertSame('Anaa', $repository->find('AAA')->name);
        $this->assertCount(0, $repository->nearest(0.0, 0.0));

        unlink($file);
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
            ['code', 'name', 'country_code', 'city', 'latitude', 'longitude', 'type', 'scheduled', 'icao'],
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
