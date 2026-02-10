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
         Schema::table('work_flow_histories', function (Blueprint $table) {
            $table->unsignedTinyInteger('locum_request_status')->nullable()->after('locum_agreement_status')->comment('0: Pending, 1: Approved, 2: Rejected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
     Schema::table('work_flow_histories', function (Blueprint $table) {
            $table->dropColumn('locum_request_status');
        });
    }
};
