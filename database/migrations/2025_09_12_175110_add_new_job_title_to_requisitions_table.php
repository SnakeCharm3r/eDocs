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
        // Only add the column if it doesn't exist
        if (!Schema::hasColumn('requisitions', 'new_job_title')) {
            Schema::table('requisitions', function (Blueprint $table) {
                $table->string('new_job_title')->nullable()->after('contract_end_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop the column if it exists
        if (Schema::hasColumn('requisitions', 'new_job_title')) {
            Schema::table('requisitions', function (Blueprint $table) {
                $table->dropColumn('new_job_title');
            });
        }
    }
};
