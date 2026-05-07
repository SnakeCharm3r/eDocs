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
        Schema::table('hec_contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('hec_contracts', 'owner_email')) {
                $table->string('owner_email')->nullable()->after('contract_owner_id');
            }
            if (!Schema::hasColumn('hec_contracts', 'contract_source')) {
                $table->string('contract_source')->default('new')->after('owner_email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hec_contracts', function (Blueprint $table) {
            if (Schema::hasColumn('hec_contracts', 'owner_email')) {
                $table->dropColumn('owner_email');
            }
            if (Schema::hasColumn('hec_contracts', 'contract_source')) {
                $table->dropColumn('contract_source');
            }
        });
    }
};
