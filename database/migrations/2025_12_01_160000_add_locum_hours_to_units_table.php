<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            // Nullable per‑unit locum hours. When null, the platform's locum_hours (or 8) is used.
            if (!Schema::hasColumn('units', 'locum_hours')) {
                $table->unsignedTinyInteger('locum_hours')
                    ->nullable()
                    ->after('is_active')
                    ->comment('Override platform locum_hours for this unit (e.g. 8 or 12). Null = use platform default.');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            if (Schema::hasColumn('units', 'locum_hours')) {
                $table->dropColumn('locum_hours');
            }
        });
    }
};


