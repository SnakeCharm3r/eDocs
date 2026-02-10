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
        Schema::table('ccbrt_contracts', function (Blueprint $table) {
            $table->integer('notice_period_months')->nullable()->after('duration_months');
        });
    }

    public function down(): void
    {
        Schema::table('ccbrt_contracts', function (Blueprint $table) {
            $table->dropColumn('notice_period_months');
        });
    }
};
