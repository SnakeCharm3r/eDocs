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
        Schema::create('contract_comments', function (Blueprint $table)
         {
        $table->id();
        $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
        $table->decimal('vendor_service_score', 5, 2)->nullable();
        $table->text('comment')->nullable();
        $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
        $table->timestamp('created_at')->useCurrent();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_comments');
    }
};
