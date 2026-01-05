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
        Schema::create('telephony_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('trace_id')->unique();
            $table->string('source')->default('navatel')->index();
            $table->timestamp('received_at')->index();
            $table->string('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->json('query')->nullable();
            $table->string('content_type')->nullable();
            $table->string('processing_status')->default('pending')->index();
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('phone_call_id')->nullable()->constrained('phone_calls')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telephony_webhook_events');
    }
};
