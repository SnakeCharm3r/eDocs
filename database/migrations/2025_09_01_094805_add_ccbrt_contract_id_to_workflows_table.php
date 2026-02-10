<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('workflows', function (Blueprint $table) {
            // Add foreign key column
            $table->unsignedBigInteger('ccbrt_contract_id')->nullable()->after('id');

            // Add foreign key constraint (optional, but recommended)
            $table->foreign('ccbrt_contract_id')
                  ->references('id')
                  ->on('ccbrt_contracts')
                  ->onDelete('cascade'); // deletes workflow if contract deleted
        });
    }

    public function down()
    {
        Schema::table('workflows', function (Blueprint $table) {
            $table->dropForeign(['ccbrt_contract_id']); // drop FK first
            $table->dropColumn('ccbrt_contract_id');   // then drop column
        });
    }
};

