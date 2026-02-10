<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('clearance_work_flow_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('clearance_work_flow_histories', 'step_name')) {
                $table->string('step_name')->nullable()->after('remark');
            }
        });
    }

    public function down()
    {
        Schema::table('clearance_work_flow_histories', function (Blueprint $table) {
            if (Schema::hasColumn('clearance_work_flow_histories', 'step_name')) {
                $table->dropColumn('step_name');
            }
        });
    }
};

