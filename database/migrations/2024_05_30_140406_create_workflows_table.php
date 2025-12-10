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

        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users');
            $table->bigInteger('ict_request_resource_id')->nullable();
            $table->string('hr_form')->nullable();
            $table->bigInteger('bank_form')->unsigned()->nullable();
            $table->bigInteger('heslb_form')->unsigned()->nullable();
            $table->bigInteger('nhif_form')->unsigned()->nullable();
            $table->bigInteger('id_form')->unsigned()->nullable();
            $table->foreign('ict_request_resource_id')->references('id')->on('ict_access_resources');
            $table->foreign('bank_form')->references('id')->on('bank_details');
            $table->foreign('heslb_form')->references('id')->on('loan_declarations');
            $table->foreign('nhif_form')->references('id')->on('nhif_registrations');
            $table->foreign('id_form')->references('id')->on('id_card_requests');


            $table->string('work_flow_status');
            $table->integer('work_flow_completed')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};
