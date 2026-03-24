<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hec_contracts', function (Blueprint $table) {
            $table->text('archive_comment')->nullable()->after('renewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('hec_contracts', function (Blueprint $table) {
            $table->dropColumn('archive_comment');
        });
    }
};
