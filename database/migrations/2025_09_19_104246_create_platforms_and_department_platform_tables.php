<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('department_platform', function (Blueprint $table) {
            $table->id();

            // Assumes departments PK is "id" (BIGINT)
            $table->foreignId('department_id')
                  ->constrained('departments')
                  ->cascadeOnDelete();

            $table->foreignId('platform_id')
                  ->constrained('platforms')
                  ->cascadeOnDelete();

            $table->timestamps();
            $table->unique(['department_id', 'platform_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_platform');
    }
};
