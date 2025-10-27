<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_workflow_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('first_approver_id')->nullable();
            $table->unsignedBigInteger('second_approver_id')->nullable();
            $table->unsignedBigInteger('accounting_user_id')->nullable();
            $table->timestamps();

            $table->foreign('first_approver_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('second_approver_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('accounting_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_workflow_settings');
    }
};

