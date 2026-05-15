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
        Schema::table('ict_access_resources', function (Blueprint $table) {
            if (!Schema::hasColumn('ict_access_resources', 'requested_email_address')) {
                $table->string('requested_email_address')->nullable()->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ict_access_resources', function (Blueprint $table) {
            if (Schema::hasColumn('ict_access_resources', 'requested_email_address')) {
                $table->dropColumn('requested_email_address');
            }
        });
    }
};
