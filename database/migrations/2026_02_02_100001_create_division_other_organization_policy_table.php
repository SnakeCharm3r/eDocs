<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Allows Organization policies to be assigned to multiple entities/divisions.
     */
    public function up(): void
    {
        if (!Schema::hasTable('division_other_organization_policy')) {
            Schema::create('division_other_organization_policy', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('other_organization_policy_id');
                $table->foreignId('division_id')->constrained('divisions')->onDelete('cascade');
                $table->timestamps();
                $table->unique(['other_organization_policy_id', 'division_id'], 'div_other_org_policy_unique');
                $table->foreign('other_organization_policy_id', 'div_other_org_policy_fk')
                    ->references('id')->on('other_organization_policies')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('division_other_organization_policy');
    }
};
