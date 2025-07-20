<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('title');
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->date('purchase_date');
            $table->string('status');
            $table->decimal('total', 10, 2);
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('forms');
    }
}; 