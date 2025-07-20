<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sales_leads', function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 10)->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('website')->nullable();
            $table->string('lead_source');
            $table->string('lead_status');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->date('lead_date');
            $table->date('next_follow_up_date');
            $table->boolean('do_not_email')->default(false);
            $table->string('customer_type')->nullable();
            $table->string('industry')->nullable();
            $table->string('nationality')->nullable();
            $table->string('main_test_field')->nullable();
            $table->string('dependent_test_field')->nullable();
            $table->text('address')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales_leads');
    }
}; 