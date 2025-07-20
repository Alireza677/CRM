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
        Schema::table('automation_rules', function (Blueprint $table) {
            $table->dropColumn('approver_1');
            $table->dropColumn('approver_2');
        });
    }
    
    public function down()
    {
        Schema::table('automation_rules', function (Blueprint $table) {
            $table->unsignedBigInteger('approver_1');
            $table->unsignedBigInteger('approver_2')->nullable();
        });
    }
    
};
