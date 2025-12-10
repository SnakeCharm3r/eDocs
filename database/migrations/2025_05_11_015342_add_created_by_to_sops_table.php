<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Make 'created_by' nullable to allow cleaning invalid data
        Schema::table('sops', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->change();
        });

        // Step 2: Fix invalid 'created_by' values
        $validUserIds = DB::table('users')->pluck('id')->toArray();

        DB::table('sops')
            ->whereNotIn('created_by', $validUserIds)
            ->update(['created_by' => null]); // or assign a default user ID if preferred

        // Step 3: Add the foreign key
        Schema::table('sops', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sops', function (Blueprint $table) {
            // Drop the foreign key
            $table->dropForeign(['created_by']);

            // Optional: make column non-nullable again if needed
            // $table->unsignedBigInteger('created_by')->nullable(false)->change();
        });
    }
};
