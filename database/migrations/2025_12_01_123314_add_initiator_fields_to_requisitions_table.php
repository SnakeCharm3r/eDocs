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
        Schema::table('requisitions', function (Blueprint $table) {
            if (!Schema::hasColumn('requisitions', 'initiator_id')) {
                $table->unsignedBigInteger('initiator_id')->nullable()->after('user_id');
                $table->string('initiator_role')->nullable()->after('initiator_id');
                $table->string('initiator_name')->nullable()->after('initiator_role');

                $table->foreign('initiator_id')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            if (Schema::hasColumn('requisitions', 'initiator_id')) {
                $table->dropForeign(['initiator_id']);
                $table->dropColumn(['initiator_id', 'initiator_role', 'initiator_name']);
            }
        });
    }
};
