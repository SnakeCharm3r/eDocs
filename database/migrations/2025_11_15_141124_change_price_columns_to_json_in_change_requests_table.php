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
        Schema::table('change_requests', function (Blueprint $table) {
            // Change current_price and new_price from decimal to json to store multiple price types
            $table->json('current_price')->nullable()->change();
            $table->json('new_price')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            // Revert back to decimal (note: data loss may occur if JSON contains multiple values)
            $table->decimal('current_price', 10, 2)->nullable()->change();
            $table->decimal('new_price', 10, 2)->nullable()->change();
        });
    }
};
