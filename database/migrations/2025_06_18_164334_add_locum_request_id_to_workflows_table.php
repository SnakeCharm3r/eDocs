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
        Schema::table('workflows', function (Blueprint $table) {
            $table->unsignedBigInteger('locum_request_id')->nullable()->after('locum_agreement_id');
            $table->foreign('locum_request_id')->references('id')->on('locum_requests')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
           Schema::table('workflows', function (Blueprint $table) {
            $table->dropForeign(['locum_request_id']);
            $table->dropColumn('locum_request_id');
        });
    }
};
