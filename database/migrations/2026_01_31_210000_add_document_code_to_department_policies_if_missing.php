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
        if (Schema::hasTable('department_policies') && !Schema::hasColumn('department_policies', 'document_code')) {
            Schema::table('department_policies', function (Blueprint $table) {
                $table->string('document_code', 50)->nullable()->after('title');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('department_policies') && Schema::hasColumn('department_policies', 'document_code')) {
            Schema::table('department_policies', function (Blueprint $table) {
                $table->dropColumn('document_code');
            });
        }
    }
};
