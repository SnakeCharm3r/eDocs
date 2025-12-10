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
        if (!Schema::hasColumn('access_key_cards', 'card_number')) {
            Schema::table('access_key_cards', function (Blueprint $table) {
                $table->string('card_number')->unique()->after('id');
                $table->string('status')->default('active')->after('card_number');
                $table->text('notes')->nullable()->after('status');
                $table->unsignedBigInteger('assigned_by')->nullable()->after('notes');
                $table->tinyInteger('delete_status')->default(0)->after('assigned_by');
            });
            
            // Add foreign key if users table exists
            if (Schema::hasTable('users')) {
                Schema::table('access_key_cards', function (Blueprint $table) {
                    $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('access_key_cards', function (Blueprint $table) {
            $table->dropForeign(['assigned_by']);
            $table->dropColumn([
                'card_number',
                'status',
                'notes',
                'assigned_by',
                'delete_status'
            ]);
        });
    }
};
