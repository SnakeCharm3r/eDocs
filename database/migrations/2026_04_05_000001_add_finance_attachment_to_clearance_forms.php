<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clearance_forms', function (Blueprint $table) {
            if (!Schema::hasColumn('clearance_forms', 'finance_attachment_path')) {
                $table->string('finance_attachment_path')->nullable()->after('finance_checklist_reviewed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clearance_forms', function (Blueprint $table) {
            if (Schema::hasColumn('clearance_forms', 'finance_attachment_path')) {
                $table->dropColumn('finance_attachment_path');
            }
        });
    }
};
