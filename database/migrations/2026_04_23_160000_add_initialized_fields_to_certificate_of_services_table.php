<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_of_services', function (Blueprint $table) {
            $table->timestamp('initialized_at')->nullable()->after('status');
            $table->unsignedBigInteger('initialized_by')->nullable()->after('initialized_at');
            $table->foreign('initialized_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('certificate_of_services', function (Blueprint $table) {
            $table->dropForeign(['initialized_by']);
            $table->dropColumn(['initialized_at', 'initialized_by']);
        });
    }
};
