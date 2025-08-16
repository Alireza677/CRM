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
    Schema::table('proformas', function (Blueprint $table) {
        $table->unsignedBigInteger('automation_rule_id')->nullable()->after('proforma_stage');
        $table->foreign('automation_rule_id')->references('id')->on('automation_rules')->onDelete('set null');
    });
}

public function down()
{
    Schema::table('proformas', function (Blueprint $table) {
        $table->dropForeign(['automation_rule_id']);
        $table->dropColumn('automation_rule_id');
    });
}

};
