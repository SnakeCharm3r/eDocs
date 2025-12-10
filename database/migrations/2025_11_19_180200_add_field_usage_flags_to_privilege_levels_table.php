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
        if (!Schema::hasColumn('privilege_levels', 'can_use_for_domain_access')) {
            Schema::table('privilege_levels', function (Blueprint $table) {
                $table->boolean('can_use_for_domain_access')->default(false)->after('prv_status');
                $table->boolean('can_use_for_email_access')->default(false)->after('can_use_for_domain_access');
                $table->boolean('can_use_for_vpn_access')->default(false)->after('can_use_for_email_access');
                $table->boolean('can_use_for_pbax_access')->default(false)->after('can_use_for_vpn_access');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('privilege_levels', function (Blueprint $table) {
            $table->dropColumn([
                'can_use_for_domain_access',
                'can_use_for_email_access',
                'can_use_for_vpn_access',
                'can_use_for_pbax_access'
            ]);
        });
    }
};
