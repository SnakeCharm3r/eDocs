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
        // Only create the table if it doesn't exist
        if (!Schema::hasTable('vendor_scores')) {
            Schema::create('vendor_scores', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('vendor_id');
                $table->unsignedBigInteger('contract_id')->nullable(); // Optional: link to specific contract
                $table->unsignedBigInteger('scored_by'); // User who gave the rating
                $table->integer('score_value')->default(0); // Rating value (1-5 or 1-10)
                $table->text('comments')->nullable(); // Comments about the rating
                $table->string('rating_type')->default('overall'); // overall, contract_performance, quality, etc.
                $table->timestamps();

                // Foreign keys
                $table->foreign('vendor_id')->references('id')->on('ccbrt_vendors')->onDelete('cascade');
                $table->foreign('contract_id')->references('id')->on('ccbrt_contracts')->onDelete('set null');
                $table->foreign('scored_by')->references('id')->on('users')->onDelete('cascade');

                // Indexes for better performance
                $table->index('vendor_id');
                $table->index('contract_id');
                $table->index('scored_by');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_scores');
    }
};
