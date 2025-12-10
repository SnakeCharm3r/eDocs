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
        // Create platforms table (standalone)
        Schema::create('platforms', function (Blueprint $table) {
            $table->id();                               // BIGINT UNSIGNED PK
            $table->string('name')->unique();           // e.g., OPD, IPD, OTD
            $table->string('description')->nullable();  // optional text
            $table->timestamps();
        });

        // Safety: if a legacy pivot exists from earlier attempts, drop it
        if (Schema::hasTable('department_platform')) {
            Schema::drop('department_platform');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate legacy pivot only if you want rollback to restore it (optional).
        // If not needed, you can just drop platforms below and remove this block.

        // Drop platforms table
        Schema::dropIfExists('platforms');
    }
};
