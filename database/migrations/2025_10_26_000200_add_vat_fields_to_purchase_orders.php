<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('vat_percent', 5, 2)->nullable()->after('status');
            $table->decimal('vat_amount', 15, 2)->default(0)->after('vat_percent');
            $table->decimal('total_with_vat', 15, 2)->default(0)->after('total_amount');
        });
    }

    public function down()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['vat_percent', 'vat_amount', 'total_with_vat']);
        });
    }
};

