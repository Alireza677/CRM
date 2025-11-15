<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('after_sales_services', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('coordinator_name');
            $table->string('coordinator_mobile', 32);
            $table->text('address')->nullable();
            $table->text('issue_description');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('after_sales_services');
    }
};

