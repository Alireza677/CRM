<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::create('automation_rules', function (Blueprint $table) {
        $table->id();
        $table->string('proforma_stage');
        $table->string('operator');
        $table->string('value');
        $table->unsignedBigInteger('approver_1');
        $table->unsignedBigInteger('approver_2')->nullable();
        $table->timestamps();
    });
}
};