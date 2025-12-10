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
            $table->unsignedBigInteger('who_approve')->nullable()->after('attended_by');
            $table->foreign('who_approve')->references('id')->on('users')->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_flow_histories', function (Blueprint $table) {
            //
        });
    }
};
