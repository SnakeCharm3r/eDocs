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
        Schema::create('locum_carryovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shift_settings')->nullOnDelete();
            $table->unsignedSmallInteger('year');      // carry belongs to this month-year
            $table->unsignedTinyInteger('month');      // 1..12
            $table->unsignedTinyInteger('hours_per_locum'); // 8 or 12 used to compute this carry
            $table->decimal('hours', 5, 2)->default(0);      // remainder hours carried into this month
            $table->timestamps();

            $table->unique(['user_id', 'shift_id', 'year', 'month', 'hours_per_locum'], 'carry_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locum_carryovers');
    }
};
