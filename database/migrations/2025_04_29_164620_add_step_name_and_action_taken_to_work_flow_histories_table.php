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
//            $table->string('step_name')->nullable()->after('id');
//            $table->string('action_taken')->nullable()->after('step_name');
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
