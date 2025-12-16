<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mail_attachments')) {
            Schema::create('mail_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mail_message_id')->constrained('mail_messages')->cascadeOnDelete();
                $table->string('filename');
                $table->string('mime')->nullable();
                $table->unsignedBigInteger('size')->nullable();
                $table->string('storage_path');
                $table->string('content_id')->nullable();
                $table->boolean('is_inline')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_attachments');
    }
};
