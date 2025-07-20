<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->date('quotation_date');
            $table->foreignId('contact_id')->constrained()->onDelete('cascade');
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('opportunity_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('assigned_to')->constrained('users')->onDelete('cascade');
            $table->foreignId('product_manager')->constrained('users')->onDelete('cascade');
            $table->string('quotation_number')->unique();
            $table->string('billing_address_source');
            $table->string('shipping_address_source');
            $table->string('province');
            $table->string('city');
            $table->text('customer_address');
            $table->string('postal_code');
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->string('item_name')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->foreignId('price_list')->constrained('price_lists')->onDelete('cascade');
            $table->decimal('discount', 10, 2)->nullable();
            $table->foreignId('unit')->constrained('units')->onDelete('cascade');
            $table->boolean('no_tax_region')->default(false);
            $table->string('tax_type');
            $table->string('adjustment_type')->nullable();
            $table->decimal('adjustment_value', 10, 2)->nullable();
            $table->decimal('item_total', 10, 2);
            $table->decimal('total_discount', 10, 2);
            $table->decimal('surcharge', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2);
            $table->decimal('tax_on_surcharge', 10, 2);
            $table->decimal('tax_deduction', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('quotations');
    }
}; 