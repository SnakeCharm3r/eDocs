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
            // Tariff fields
            $table->enum('tariff_type', ['new_tariff', 'edit_tariff'])->nullable()->after('change_category');
            $table->string('tariff_name')->nullable()->after('tariff_type');
            $table->unsignedBigInteger('tariff_category_id')->nullable()->after('tariff_name');
            $table->foreign('tariff_category_id')->references('id')->on('tariff_categories')->onDelete('set null');
            $table->string('current_tariff_name')->nullable()->after('tariff_category_id');
            
            // Service fields
            $table->enum('service_action_type', ['new_service', 'edit_service'])->nullable()->after('current_tariff_name');
            $table->string('service_name')->nullable()->after('service_action_type');
            $table->unsignedBigInteger('service_category_id')->nullable()->after('service_name');
            $table->foreign('service_category_id')->references('id')->on('service_categories')->onDelete('set null');
            $table->string('current_service_name')->nullable()->after('service_category_id');
            $table->json('service_prices')->nullable()->after('current_service_name'); // Store prices for different payment types
            
            // Price change fields
            $table->string('price_item_name')->nullable()->after('service_prices');
            $table->decimal('current_price', 10, 2)->nullable()->after('price_item_name');
            $table->text('price_change_reason')->nullable()->after('new_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropForeign(['tariff_category_id']);
            $table->dropForeign(['service_category_id']);
            $table->dropColumn([
                'tariff_type',
                'tariff_name',
                'tariff_category_id',
                'current_tariff_name',
                'service_action_type',
                'service_name',
                'service_category_id',
                'current_service_name',
                'service_prices',
                'price_item_name',
                'current_price',
                'new_price',
                'price_change_reason',
            ]);
        });
    }
};















