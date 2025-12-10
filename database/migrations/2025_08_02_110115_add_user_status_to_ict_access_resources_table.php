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
    Schema::table('ict_access_resources', function (Blueprint $table) {
        $table->string('user_status')->nullable()->after('delete_status');
    });
}

public function down()
{
    Schema::table('ict_access_resources', function (Blueprint $table) {
        $table->dropColumn('user_status');
    });
}

};
