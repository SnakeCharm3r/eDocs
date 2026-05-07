<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('clearance_forms', function (Blueprint $table) {
            if (!Schema::hasColumn('clearance_forms', 'status')) {
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('userId');
            }
            if (!Schema::hasColumn('clearance_forms', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('status');
            }
        });
    }

    public function down()
    {
        Schema::table('clearance_forms', function (Blueprint $table) {
            if (Schema::hasColumn('clearance_forms', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('clearance_forms', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
        });
    }
};

