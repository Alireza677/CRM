<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sms_list_contact', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_list_id')->constrained('sms_lists')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['sms_list_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_list_contact');
        Schema::dropIfExists('sms_lists');
    }
};

