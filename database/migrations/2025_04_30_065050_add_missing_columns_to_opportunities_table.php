<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->string('type')->nullable();
            $table->integer('success_rate')->nullable();
            $table->bigInteger('amount')->nullable();
        });
    }

    public function down()
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn(['type', 'success_rate', 'amount']);
        });
    }
}; 