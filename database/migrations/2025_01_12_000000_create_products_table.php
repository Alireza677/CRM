<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('sales_start_date')->nullable();
            $table->date('sales_end_date')->nullable();
            $table->date('support_start_date')->nullable();
            $table->date('support_end_date')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('manufacturer')->nullable();
            $table->string('series')->nullable();
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->boolean('has_vat')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('website')->nullable();
            $table->string('part_number')->nullable();
            $table->string('type')->nullable();
            $table->decimal('thermal_power', 10, 2)->nullable();
            $table->decimal('commission', 5, 2)->nullable();
            $table->decimal('purchase_cost', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
}; 