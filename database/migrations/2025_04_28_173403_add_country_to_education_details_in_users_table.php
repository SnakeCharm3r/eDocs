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
        Schema::table('users', function (Blueprint $table) {
            $levels = ['primary', 'o_level', 'a_level', 'certificate', 'diploma', 'degree', 'masters', 'phd'];
            foreach ($levels as $level) {
                $table->text("{$level}_country")->nullable()->after("{$level}_institution");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
