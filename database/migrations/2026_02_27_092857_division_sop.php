<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Allows SOPs to be assigned to multiple entities/divisions.
     */
    public function up(): void
    {
        if (!Schema::hasTable('division_sop')) {
            Schema::create('division_sop', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sop_id')->constrained('sops')->onDelete('cascade');
                $table->foreignId('division_id')->constrained('divisions')->onDelete('cascade');
                $table->timestamps();
                $table->unique(['sop_id', 'division_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('division_sop');
    }
};
