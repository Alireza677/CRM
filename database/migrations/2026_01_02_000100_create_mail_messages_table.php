<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mail_messages')) {
            Schema::create('mail_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mailbox_id')->constrained()->cascadeOnDelete();
                $table->foreignId('folder_id')->constrained('mail_folders')->cascadeOnDelete();
                $table->unsignedBigInteger('imap_uid');
                $table->string('message_id')->nullable();
                $table->string('subject')->nullable();
                $table->string('from_name')->nullable();
                $table->string('from_email')->nullable()->index();
                $table->json('to')->nullable();
                $table->json('cc')->nullable();
                $table->dateTime('date')->nullable()->index();
                $table->text('snippet')->nullable();
                $table->longText('body_text')->nullable();
                $table->longText('body_html')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamps();

                $table->unique(['folder_id', 'imap_uid']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_messages');
    }
};
