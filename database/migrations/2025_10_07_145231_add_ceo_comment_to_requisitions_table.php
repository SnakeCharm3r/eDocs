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
        // Add the column only if it doesn't exist
        if (!Schema::hasColumn('requisitions', 'ceo_comment')) {
            Schema::table('requisitions', function (Blueprint $table) {
                $table->text('ceo_comment')->nullable()->after('ceo_decision');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the column only if it exists
        if (Schema::hasColumn('requisitions', 'ceo_comment')) {
            Schema::table('requisitions', function (Blueprint $table) {
                $table->dropColumn('ceo_comment');
            });
        }
    }
};
