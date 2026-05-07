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
            $table->unsignedBigInteger('change_request_id')->nullable()->after('id');
            $table->foreign('change_request_id')
                  ->references('id')->on('change_requests')
                  ->onDelete('cascade');
        });

        Schema::table('work_flow_histories', function (Blueprint $table) {
            $table->string('step_name')->nullable()->after('id'); // e.g., Line Manager, HR
            $table->string('action_taken')->nullable()->after('step_name'); // e.g., approved, rejected
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_tables', function (Blueprint $table) {
            //
        });
    }
};
