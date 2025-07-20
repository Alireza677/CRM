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
    Schema::table('sales_leads', function (Blueprint $table) {
        $table->dropColumn('first_name');
    });
}

public function down()
{
    Schema::table('sales_leads', function (Blueprint $table) {
        $table->string('first_name'); // می‌تونی در صورت نیاز `->nullable()` هم اضافه کنی
    });
}

};
