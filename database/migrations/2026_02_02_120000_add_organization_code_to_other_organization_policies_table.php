<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Allow COO to set an optional organization code for Other Organization policies.
     */
    public function up(): void
    {
        Schema::table('other_organization_policies', function (Blueprint $table) {
            if (!Schema::hasColumn('other_organization_policies', 'organization_code')) {
                $table->string('organization_code', 50)->nullable()->after('document_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('other_organization_policies', function (Blueprint $table) {
            if (Schema::hasColumn('other_organization_policies', 'organization_code')) {
                $table->dropColumn('organization_code');
            }
        });
    }
};
