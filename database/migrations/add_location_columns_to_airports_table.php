<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Upgrades a table created by the 1.0 migration with the 1.1 columns.
 * Does nothing when the table is missing or already has them.
 */
return new class extends Migration
{
    public function getConnection(): ?string
    {
        return config('airports.connection');
    }

    public function up(): void
    {
        $table = config('airports.table', 'airports');
        $schema = Schema::connection($this->getConnection());

        if (! $schema->hasTable($table) || $schema->hasColumn($table, 'latitude')) {
            return;
        }

        $schema->table($table, function (Blueprint $table) {
            $table->string('city')->nullable();
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->string('type', 16)->nullable()->index();
            $table->boolean('scheduled')->default(false)->index();
            $table->char('icao', 4)->nullable();
        });
    }

    public function down(): void
    {
        $table = config('airports.table', 'airports');
        $schema = Schema::connection($this->getConnection());

        if (! $schema->hasColumn($table, 'latitude')) {
            return;
        }

        $schema->table($table, function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['scheduled']);
            $table->dropColumn(['city', 'latitude', 'longitude', 'type', 'scheduled', 'icao']);
        });
    }
};
