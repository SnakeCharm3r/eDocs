<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('clearance_forms')){
        Schema::create('clearance_forms', function (Blueprint $table) {
            $table->id();
            // $table->bigInteger('userId')->unsigned();
            $table->string('ccbrt_id_card')->default('N/A');
            $table->string('ccbrt_name_tag')->default('N/A');
            $table->string('nhif_cards')->default('N/A');
            $table->string('work_permit_cancelled')->default('N/A');
            $table->string('residence_permit_cancelled')->default('N/A');
            $table->string('repaid_salary_advance')->default('N/A');
            $table->string('loan_balances_informed')->default('N/A');
            $table->string('repaid_outstanding_imprest')->default('N/A');
            $table->string('changing_room_keys')->default('N/A');
            $table->string('office_keys')->default('N/A');
            $table->string('mobile_phone')->default('N/A');
            $table->string('camera')->default('N/A');
            $table->string('ccbrt_uniforms')->default('N/A');
            $table->string('office_car_keys')->default('N/A');
            $table->string('other_items')->nullable();
            $table->string('laptop_returned')->default('No');
            $table->string('access_card_returned')->default('No');
            $table->string('domain_account_disabled')->default('No');
            $table->string('email_account_disabled')->default('No');
            $table->string('telephone_pin_disabled')->default('No');
            $table->string('openclinic_account_disabled')->default('No');
            $table->string('sap_account_disabled')->default('No');
            $table->string('aruti_account_disabled')->default('No');
            $table->unsignedBigInteger('userId')->nullable(); // Add the userId column
            $table->foreign('userId')->references('id')->on('users');
            $table->timestamps();
        });}
    }

    public function down(): void
    {
        Schema::dropIfExists('clearance_forms');
    }
};