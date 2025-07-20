<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('proforma_items', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn([
                'discount_percent',
                'tax_group',
                'tax_percent'
            ]);

            // Add new columns
            $table->string('discount_type')->nullable()->after('unit_of_use');
            $table->decimal('discount_value', 15, 2)->nullable()->after('discount_type');
            $table->string('tax_type')->nullable()->after('discount_amount');
            $table->decimal('tax_value', 15, 2)->nullable()->after('tax_type');
        });
    }

    public function down()
    {
        Schema::table('proforma_items', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn([
                'discount_type',
                'discount_value',
                'tax_type',
                'tax_value'
            ]);

            // Add back old columns
            $table->decimal('discount_percent', 5, 2)->default(0)->after('unit_of_use');
            $table->string('tax_group')->nullable()->after('discount_amount');
            $table->decimal('tax_percent', 5, 2)->default(0)->after('tax_group');
        });
    }
}; 