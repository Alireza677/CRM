<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('proformas', function (Blueprint $table) {
            $table->string('product_name')->nullable()->after('customer_address');
            $table->integer('quantity')->nullable()->after('product_name');
            $table->decimal('price', 15, 2)->nullable()->after('quantity');
            $table->string('unit')->nullable()->after('price');
            $table->decimal('total', 15, 2)->nullable()->after('unit');
        });
    }

    public function down()
    {
        Schema::table('proformas', function (Blueprint $table) {
            $table->dropColumn([
                'product_name',
                'quantity',
                'price',
                'unit',
                'total'
            ]);
        });
    }
}; 