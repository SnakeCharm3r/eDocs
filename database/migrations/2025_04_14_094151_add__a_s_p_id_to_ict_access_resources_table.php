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
        Schema::table('ict_access_resources', function (Blueprint $table) {
            // $table->unsignedBigInteger('ASPId')->nullable()->after('sap');
            // $table->foreign('ASPId')->references('id')->on('s_a_p_levels')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ict_access_resources', function (Blueprint $table) {
            //
        });
    }
};
