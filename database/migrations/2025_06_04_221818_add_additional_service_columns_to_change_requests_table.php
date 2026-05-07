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
                 $table->string('drug_name')->nullable()->after('supporting_document');
                 $table->string('ms_number')->nullable()->after('drug_name');
                 $table->decimal('cash_price', 10, 2)->nullable()->after('ms_number');
                 $table->decimal('standard_nhif_price', 10, 2)->nullable()->after('cash_price');
                 $table->decimal('nhif_supplementary', 10, 2)->nullable()->after('standard_nhif_price');
                 $table->decimal('bot', 10, 2)->nullable()->after('nhif_supplementary');
                 $table->decimal('crdb', 10, 2)->nullable()->after('bot');
                 $table->decimal('word_vision_nmb', 10, 2)->nullable()->after('crdb');
                 $table->decimal('premium_insurance', 10, 2)->nullable()->after('word_vision_nmb');
                 $table->string('nhif_codes')->nullable()->after('premium_insurance');
            });
        }

    /**
     * Reverse the migrations.
     */
public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropColumn([
                 'drug_name',
                 'ms_number',
                 'cash_price',
                 'standard_nhif_price',
                 'nhif_supplementary',
                 'bot',
                 'crdb',
                 'word_vision_nmb',
                 'premium_insurance',
                 'nhif_codes',
            ]);
        });
    }
};
