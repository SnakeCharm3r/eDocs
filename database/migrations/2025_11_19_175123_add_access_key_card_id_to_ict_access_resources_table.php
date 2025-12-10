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
        if (!Schema::hasColumn('ict_access_resources', 'access_key_card_id')) {
            Schema::table('ict_access_resources', function (Blueprint $table) {
                $table->unsignedBigInteger('access_key_card_id')->nullable()->after('physical_access');
            });
            
            if (Schema::hasTable('access_key_cards')) {
                Schema::table('ict_access_resources', function (Blueprint $table) {
                    $table->foreign('access_key_card_id')->references('id')->on('access_key_cards')->onDelete('set null');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ict_access_resources', function (Blueprint $table) {
            $table->dropForeign(['access_key_card_id']);
            $table->dropColumn('access_key_card_id');
        });
    }
};
