<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    |
    | The in-memory lookup API (the Airports facade) works without a database.
    | These settings only apply if you publish the migration and seed the
    | airports into a table, e.g. so other tables can reference them.
    |
    */

    'table' => env('AIRPORTS_TABLE', 'airports'),

    'connection' => env('AIRPORTS_DB_CONNECTION'),

];
