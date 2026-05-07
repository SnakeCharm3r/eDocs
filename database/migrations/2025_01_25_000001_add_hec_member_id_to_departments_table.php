<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds hec_member_id to departments table to map each department to its primary HEC member (CFO/COO/CMS/CCDRO)
     */
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (!Schema::hasColumn('departments', 'hec_member_id')) {
                $table->foreignId('hec_member_id')->nullable()->after('hec_id')->constrained('users')->onDelete('set null');
                $table->index('hec_member_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'hec_member_id')) {
                $table->dropForeign(['hec_member_id']);
                $table->dropColumn('hec_member_id');
            }
        });
    }
};







