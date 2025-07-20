<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('proformas', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->date('proforma_date')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('inventory_manager')->nullable();
            $table->string('proforma_number')->nullable();
            $table->string('proforma_stage')->default('draft');
            $table->string('organization_name')->nullable();
            $table->string('sales_opportunity')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->text('customer_address')->nullable();
            $table->enum('address_type', ['invoice', 'product'])->default('invoice');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_favorite')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('proformas');
    }
}; 