<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDivisionPolicyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('division_policy')) {
            Schema::create('division_policy', function (Blueprint $table) {
                $table->id();
                $table->foreignId('policy_id')->constrained('policies')->onDelete('cascade');
                $table->foreignId('division_id')->constrained('divisions')->onDelete('cascade');
                $table->timestamps();
                $table->unique(['policy_id', 'division_id'], 'div_policy_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('division_policy');
    }
}
