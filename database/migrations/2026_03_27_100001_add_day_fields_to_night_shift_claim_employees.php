<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('night_shift_claim_employees', function (Blueprint $table) {
            $table->string('date')->nullable()->after('days_on_duty'); // ISO date e.g. 2026-02-03
            $table->unsignedBigInteger('platform_id')->nullable()->after('date');
            $table->unsignedBigInteger('unit_id')->nullable()->after('platform_id');

            $table->foreign('platform_id')->references('id')->on('platforms')->onDelete('set null');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('night_shift_claim_employees', function (Blueprint $table) {
            $table->dropForeign(['platform_id']);
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['date', 'platform_id', 'unit_id']);
        });
    }
};
