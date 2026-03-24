<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add start_date and end_date so locum rates can be managed by period (e.g. per year) for audit.
     */
    public function up(): void
    {
        Schema::table('locum_rates', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('education_level');
            $table->date('end_date')->nullable()->after('start_date');
        });

        // Backfill existing rows: apply from 2020-01-01 to 2099-12-31 so they remain valid
        DB::table('locum_rates')->whereNull('start_date')->update([
            'start_date' => '2020-01-01',
            'end_date'   => '2099-12-31',
        ]);

        // Make non-nullable after backfill (use raw for compatibility)
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE locum_rates MODIFY start_date DATE NOT NULL');
            DB::statement('ALTER TABLE locum_rates MODIFY end_date DATE NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE locum_rates ALTER COLUMN start_date SET NOT NULL');
            DB::statement('ALTER TABLE locum_rates ALTER COLUMN end_date SET NOT NULL');
        }

        // Drop unique on education_level (if exists) and add unique on (education_level, start_date, end_date)
        try {
            Schema::table('locum_rates', function (Blueprint $table) {
                $table->dropUnique(['education_level']);
            });
        } catch (\Throwable $e) {
            // Constraint may have different name or not exist
        }

        Schema::table('locum_rates', function (Blueprint $table) {
            $table->unique(['education_level', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locum_rates', function (Blueprint $table) {
            $table->dropUnique(['education_level', 'start_date', 'end_date']);
        });

        Schema::table('locum_rates', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
            $table->unique('education_level');
        });
    }
};
