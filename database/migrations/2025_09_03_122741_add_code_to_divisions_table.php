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
        Schema::table('divisions', function (Blueprint $table) {
            $table->integer('code')->nullable()->after('name');
            $table->foreignId('department')
                ->nullable()
                ->after('code')
                ->constrained('departments')
                ->onDelete('set null');
            $table->string('location')->nullable()->after('department');
            $table->foreignId('HEC')
                ->nullable()
                ->after('department')
                ->constrained('hecs')
                ->onDelete('set null');
            $table->string('status')->nullable()->after('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};