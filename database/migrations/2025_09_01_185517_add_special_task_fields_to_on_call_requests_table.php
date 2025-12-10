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
        Schema::table('on_call_requests', function (Blueprint $table) {
            $table->boolean('has_special_task')->default(false)->after('description');
            $table->string('special_task_path')->nullable()->after('has_special_task');
            $table->string('special_task_original_name')->nullable()->after('special_task_path');
            $table->string('special_task_mime')->nullable()->after('special_task_original_name');
            $table->unsignedBigInteger('special_task_size')->nullable()->after('special_task_mime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('on_call_requests', function (Blueprint $table) {
            $table->dropColumn('has_special_task');
            $table->dropColumn('special_task_path');
            $table->dropColumn('special_task_original_name');
            $table->dropColumn('special_task_mime');
            $table->dropColumn('special_task_size');
        });
    }
};
