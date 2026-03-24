<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('other_organization_policies', function (Blueprint $table) {
            if (!Schema::hasColumn('other_organization_policies', 'view_count')) {
                $table->unsignedInteger('view_count')->default(0)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('other_organization_policies', function (Blueprint $table) {
            if (Schema::hasColumn('other_organization_policies', 'view_count')) {
                $table->dropColumn('view_count');
            }
        });
    }
};
