<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRequestedByToClearanceFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clearance_forms', function (Blueprint $table) {
            $table->unsignedBigInteger('requested_by')->nullable()->after('userId');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('set null');
            
            // Optional: Add an index for better performance
            $table->index('requested_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clearance_forms', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->dropColumn('requested_by');
        });
    }
}