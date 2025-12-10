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
        // Add columns only if they don't exist
        if (!Schema::hasColumn('requisitions', 'cfo_financing_confirmation')) {
            Schema::table('requisitions', function (Blueprint $table) {
                $table->enum('cfo_financing_confirmation', ['Confirmed', 'Not confirmed'])
                      ->nullable()
                      ->after('funding_available');
            });
        }

        if (!Schema::hasColumn('requisitions', 'cfo_financing_code')) {
            Schema::table('requisitions', function (Blueprint $table) {
                $table->string('cfo_financing_code')
                      ->nullable()
                      ->after('cfo_financing_confirmation');
            });
        }

        if (!Schema::hasColumn('requisitions', 'cfo_comment')) {
            Schema::table('requisitions', function (Blueprint $table) {
                $table->text('cfo_comment')
                      ->nullable()
                      ->after('cfo_financing_code');
            });
        }

        if (!Schema::hasColumn('requisitions', 'ceo_decision')) {
            Schema::table('requisitions', function (Blueprint $table) {
                $table->enum('ceo_decision', ['Approved', 'Declined', 'Needs further information'])
                      ->nullable()
                      ->after('cfo_comment');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop columns only if they exist
        if (Schema::hasColumn('requisitions', 'ceo_decision')) {
            Schema::table('requisitions', function (Blueprint $table) {
                $table->dropColumn('ceo_decision');
            });
        }

        if (Schema::hasColumn('requisitions', 'cfo_comment')) {
            Schema::table('requisitions', function (Blueprint $table) {
                $table->dropColumn('cfo_comment');
            });
        }

        if (Schema::hasColumn('requisitions', 'cfo_financing_code')) {
            Schema::table('requisitions', function (Blueprint $table) {
                $table->dropColumn('cfo_financing_code');
            });
        }

        if (Schema::hasColumn('requisitions', 'cfo_financing_confirmation')) {
            Schema::table('requisitions', function (Blueprint $table) {
                $table->dropColumn('cfo_financing_confirmation');
            });
        }
    }
};
