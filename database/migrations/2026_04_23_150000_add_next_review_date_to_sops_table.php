<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sops', function (Blueprint $table) {
            if (!Schema::hasColumn('sops', 'next_review_date')) {
                $table->date('next_review_date')->nullable()->after('effective_date');
            }
        });

        DB::table('sops')
            ->whereNull('next_review_date')
            ->whereNotNull('expiry_date')
            ->update(['next_review_date' => DB::raw('expiry_date')]);
    }

    public function down(): void
    {
        Schema::table('sops', function (Blueprint $table) {
            if (Schema::hasColumn('sops', 'next_review_date')) {
                $table->dropColumn('next_review_date');
            }
        });
    }
};