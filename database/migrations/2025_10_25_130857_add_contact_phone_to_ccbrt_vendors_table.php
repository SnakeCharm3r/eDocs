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
        Schema::table('ccbrt_vendors', function (Blueprint $table) {
            if (!Schema::hasColumn('ccbrt_vendors', 'contact_phone')) {
                $table->string('contact_phone')->nullable()->after('contact_person');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ccbrt_vendors', function (Blueprint $table) {
            if (Schema::hasColumn('ccbrt_vendors', 'contact_phone')) {
                $table->dropColumn('contact_phone');
            }
        });
    }
};
