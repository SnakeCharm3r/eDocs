<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $levels = ['primary', 'o_level', 'a_level', 'certificate', 'diploma', 'degree', 'masters', 'phd'];

            foreach ($levels as $level) {
                if (!Schema::hasColumn('users', "{$level}_institution")) {
                    $table->string("{$level}_institution")->nullable();
                }
                if (!Schema::hasColumn('users', "{$level}_start_year")) {
                    $table->year("{$level}_start_year")->nullable();
                }
                if (!Schema::hasColumn('users', "{$level}_completion_year")) {
                    $table->year("{$level}_completion_year")->nullable();
                }
                if (!Schema::hasColumn('users', "{$level}_certificate")) {
                    $table->string("{$level}_certificate")->nullable();
                }
                if (in_array($level, ['certificate', 'diploma', 'degree', 'masters', 'phd']) && !Schema::hasColumn('users', "{$level}_transcript")) {
                    $table->string("{$level}_transcript")->nullable();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $levels = ['primary', 'o_level', 'a_level', 'certificate', 'diploma', 'degree', 'masters', 'phd'];
            foreach ($levels as $level) {
                $table->dropColumn("{$level}_institution");
                $table->dropColumn("{$level}_start_year");
                $table->dropColumn("{$level}_completion_year");
                $table->dropColumn("{$level}_certificate");
                if (in_array($level, ['certificate', 'diploma', 'degree', 'masters', 'phd'])) {
                    $table->dropColumn("{$level}_transcript");
                }
            }
        });
    }
};
