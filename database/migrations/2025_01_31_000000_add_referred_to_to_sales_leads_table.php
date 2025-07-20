<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReferredToToSalesLeadsTable extends Migration
{
    public function up()
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            $table->unsignedBigInteger('referred_to')->nullable()->after('assigned_to');

            // اگر بخوایم این فیلد با users جدول مرتبط باشه:
            $table->foreign('referred_to')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            $table->dropForeign(['referred_to']);
            $table->dropColumn('referred_to');
        });
    }
}
