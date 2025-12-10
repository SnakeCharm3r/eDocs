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
        Schema::table('requisitions', function (Blueprint $table) {
            if (!Schema::hasColumn('requisitions', 'job_title_id')) {
                $table->unsignedBigInteger('job_title_id')->nullable()->after('user_id');
                $table->foreign('job_title_id')->references('id')->on('job_titles')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            if (Schema::hasColumn('requisitions', 'job_title_id')) {
                $table->dropForeign(['job_title_id']);
                $table->dropColumn('job_title_id');
            }
        });
    }
};
