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
        Schema::create('asset_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->foreignId('from_department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->foreignId('from_division_id')->nullable()->constrained('divisions')->onDelete('set null');
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->string('from_custom_location')->nullable();
            $table->foreignId('to_department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->foreignId('to_division_id')->nullable()->constrained('divisions')->onDelete('set null');
            $table->foreignId('to_location_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->string('to_custom_location')->nullable();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('to_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('movement_date');
            $table->foreignId('moved_by')->constrained('users')->onDelete('restrict');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_movements');
    }
};
