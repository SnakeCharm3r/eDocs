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
            $table->string('eye_product_name')->nullable()->after('price_change_reason');
            $table->decimal('eye_product_price', 12, 2)->nullable()->after('eye_product_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropColumn(['eye_product_name', 'eye_product_price']);
        });
    }
};
