<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void {
        Schema::table('proformas', function (Blueprint $t) {
            $t->unsignedBigInteger('items_subtotal')->default(0)->after('total');
            $t->enum('global_discount_type', ['percentage','fixed'])->nullable()->after('items_subtotal');
            $t->unsignedBigInteger('global_discount_value')->default(0)->after('global_discount_type');
            $t->unsignedBigInteger('global_discount_amount')->default(0)->after('global_discount_value');
            $t->enum('global_tax_type', ['percentage','fixed'])->nullable()->after('global_discount_amount');
            $t->unsignedBigInteger('global_tax_value')->default(0)->after('global_tax_type');
            $t->unsignedBigInteger('global_tax_amount')->default(0)->after('global_tax_value');
            // total_amount موجود است و به‌عنوان جمع نهایی استفاده می‌کنیم
        });
    }
    public function down(): void {
        Schema::table('proformas', function (Blueprint $t) {
            $t->dropColumn([
                'items_subtotal','global_discount_type','global_discount_value',
                'global_discount_amount','global_tax_type','global_tax_value','global_tax_amount'
            ]);
        });
    }
    
};
