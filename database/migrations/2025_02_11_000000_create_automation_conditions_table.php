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
        Schema::create('automation_conditions', function (Blueprint $table) {
            $table->id();
            $table->string('model_type'); // مثل "Proforma"
            $table->string('field');      // مثل "proforma_stage"
            $table->string('operator');   // مثل "=" یا "!="
            $table->string('value');      // مقدار مثلاً "send_for_approval"
            $table->unsignedBigInteger('approver1_id')->nullable();
            $table->unsignedBigInteger('approver2_id')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_conditions');
    }
};
