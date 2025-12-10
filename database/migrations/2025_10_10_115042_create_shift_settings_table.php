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
        Schema::create('shift_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');                         // e.g., "Official Duty", "Day Shift", "Night Shift"
            $table->string('code')->nullable();             // optional short code, e.g., "OD", "DS", "NS"
            $table->boolean('is_official_duty')->default(false);

            // Time & duration
            $table->time('start_time')->nullable();         // e.g., 08:00
            $table->time('end_time')->nullable();           // e.g., 17:00

            // Planning fields
            $table->unsignedTinyInteger('days_per_week')->nullable();   // e.g., 5, 6, 7
            $table->decimal('hours_per_day', 5, 2)->nullable();         // e.g., 8.00, 12.00

            // Optional weekly total cache (you can compute in accessor; this is handy for filters)
            $table->decimal('weekly_hours', 6, 2)->nullable();

            // Status & misc
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_settings');
    }
};
