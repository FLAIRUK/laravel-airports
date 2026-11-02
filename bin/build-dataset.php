<?php

/*
|--------------------------------------------------------------------------
| Rebuild data/airports.php from OurAirports
|--------------------------------------------------------------------------
|
| Adds city, coordinates, type, scheduled service and ICAO code to every row
| of the bundled dataset, matching on the IATA code. The existing id, code,
| name and country_code are kept as they are, so the script can be re-run
| against a newer OurAirports export at any time.
|
| A match must also agree on the country. IATA reassigns codes, and an
| OurAirports row in another country is usually a different place.
|
|   curl -sSfLo /tmp/airports.csv https://davidmegginson.github.io/ourairports-data/airports.csv
|   php bin/build-dataset.php /tmp/airports.csv
|
| OurAirports data is public domain: https://ourairports.com/data/
|
*/

$csv = $argv[1] ?? null;
$target = $argv[2] ?? __DIR__.'/../data/airports.php';

if ($csv === null || ! is_readable($csv)) {
    fwrite(STDERR, "Usage: php bin/build-dataset.php <ourairports airports.csv> [data/airports.php]\n");
    exit(1);
}

// OurAirports type => dataset type. Anything else (balloonport) becomes null.
$types = [
    'large_airport' => 'large',
    'medium_airport' => 'medium',
    'small_airport' => 'small',
    'seaplane_base' => 'seaplane',
    'heliport' => 'heliport',
    'closed' => 'closed',
];

// Higher wins when several OurAirports rows share an IATA code.
$sizes = ['large' => 5, 'medium' => 4, 'small' => 3, 'seaplane' => 2, 'heliport' => 1];

$rank = fn (array $row): array => [
    $row['type'] !== 'closed',
    $row['scheduled'],
    $sizes[$row['type']] ?? 0,
];

$dataset = require $target;
$countries = array_column($dataset, 'country_code', 'code');

$handle = fopen($csv, 'r');
$header = fgetcsv($handle, escape: '');
$matches = [];

while (($values = fgetcsv($handle, escape: '')) !== false) {
    $source = array_combine($header, $values);
    $iata = strtoupper(trim($source['iata_code']));

    if (! isset($countries[$iata]) || $countries[$iata] !== $source['iso_country']) {
        continue;
    }

    $icao = trim($source['icao_code']) ?: trim($source['gps_code']);

    $row = [
        'city' => trim($source['municipality']) ?: null,
        'latitude' => is_numeric($source['latitude_deg']) ? round((float) $source['latitude_deg'], 6) : null,
        'longitude' => is_numeric($source['longitude_deg']) ? round((float) $source['longitude_deg'], 6) : null,
        'type' => $types[$source['type']] ?? null,
        'scheduled' => $source['scheduled_service'] === 'yes',
        'icao' => preg_match('/^[A-Z0-9]{4}$/', $icao) ? $icao : null,
    ];

    if (! isset($matches[$iata]) || $rank($row) > $rank($matches[$iata])) {
        $matches[$iata] = $row;
    }
}

fclose($handle);

$empty = ['city' => null, 'latitude' => null, 'longitude' => null, 'type' => null, 'scheduled' => false, 'icao' => null];
$lines = [];
$matched = 0;

foreach ($dataset as $existing) {
    $base = array_intersect_key($existing, array_flip(['id', 'code', 'name', 'country_code']));
    $extra = $matches[$base['code']] ?? $empty;
    $matched += isset($matches[$base['code']]);

    $pairs = [];
    foreach ($base + $extra as $key => $value) {
        $pairs[] = "'{$key}' => ".($value === null ? 'null' : var_export($value, true));
    }

    $lines[] = '    ['.implode(', ', $pairs).'],';
}

file_put_contents($target, implode("\n", [
    '<?php',
    '',
    '// IATA airport location codes.',
    '// City, coordinates, type, scheduled service and ICAO code from OurAirports (public domain),',
    '// https://ourairports.com/data/. Rebuild with bin/build-dataset.php.',
    '',
    'return [',
    ...$lines,
    '];',
    '',
]));

$total = count($lines);
printf("Matched %d of %d rows (%.1f%%); %d left without coordinates.\n", $matched, $total, $matched / $total * 100, $total - $matched);
