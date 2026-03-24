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
        Schema::create('external_system_links', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Health AI", "Aruti", "SBS"
            $table->string('url'); // The link URL
            $table->string('icon')->nullable(); // Font Awesome icon class (e.g., "fas fa-heartbeat")
            $table->string('color')->default('#0f813c'); // Color for the link/icon
            $table->boolean('open_in_new_tab')->default(true); // Whether to open in new tab
            $table->boolean('is_active')->default(true); // Whether to show on login page
            $table->integer('display_order')->default(0); // Order of display
            $table->text('description')->nullable(); // Optional description
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_system_links');
    }
};
