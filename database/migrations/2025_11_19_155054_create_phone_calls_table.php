<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('phone_calls', function (Blueprint $table) {
            $table->id();
            $table->string('status')->index();
            $table->string('customer_number')->index();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('handled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_identifier')->nullable()->index();
            $table->timestamp('started_at')->nullable()->index();
            $table->string('direction')->default('inbound');
            $table->json('payload_raw')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phone_calls');
    }
};
