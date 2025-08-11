<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // اگر میخوای اعشار ذخیره بشه (پیشنهادی)
            $table->decimal('unit_price', 15, 2)->change();
            
            // اگر فقط عدد صحیح بزرگ میخوای
            // $table->bigInteger('unit_price')->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // اینجا باید نوع قبلی رو برگردونی
            $table->integer('unit_price')->change();
        });
    }
};
