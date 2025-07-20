<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('proforma_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('proforma_id')->constrained()->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('proforma_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });
    }
}; 