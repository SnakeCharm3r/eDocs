<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('other_organization_policies', function (Blueprint $table) {
            $table->date('effective_date')->nullable()->after('status');
            $table->date('next_review_date')->nullable()->after('effective_date');
        });
    }

    public function down(): void
    {
        Schema::table('other_organization_policies', function (Blueprint $table) {
            $table->dropColumn(['effective_date', 'next_review_date']);
        });
    }
};
