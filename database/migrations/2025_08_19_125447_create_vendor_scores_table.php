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
        Schema::create('vendor_scores', function (Blueprint $table) {
        $table->id();
        $table->foreignId('vendor_id')->constrained('ccbrt_vendors')->onDelete('cascade');
        $table->foreignId('scored_by')->constrained('users')->onDelete('cascade');
        $table->decimal('score_value', 5, 2);
        $table->text('comments')->nullable();
        $table->timestamp('created_at')->useCurrent();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_scores');
    }
};
