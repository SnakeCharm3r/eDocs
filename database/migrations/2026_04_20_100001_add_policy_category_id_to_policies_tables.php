<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->foreignId('policy_category_id')->nullable()->after('id')->constrained('policy_categories')->nullOnDelete();
        });

        Schema::table('other_organization_policies', function (Blueprint $table) {
            $table->foreignId('policy_category_id')->nullable()->after('id')->constrained('policy_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->dropForeign(['policy_category_id']);
            $table->dropColumn('policy_category_id');
        });

        Schema::table('other_organization_policies', function (Blueprint $table) {
            $table->dropForeign(['policy_category_id']);
            $table->dropColumn('policy_category_id');
        });
    }
};
