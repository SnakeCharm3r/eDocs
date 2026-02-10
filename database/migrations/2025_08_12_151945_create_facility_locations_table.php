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
        Schema::create('Facility_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Name of the Facility
            $table->string('code')->unique(); // Short code for location
            $table->string('Location')->nullable();
            $table->string('Status')->nullable(); // status of the Location
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facility_locations');
    }
};
