<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mail_folders')) {
            Schema::create('mail_folders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mailbox_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('imap_path');
                $table->unsignedBigInteger('uid_validity')->nullable();
                $table->unsignedBigInteger('last_uid')->nullable();
                $table->timestamp('last_sync_at')->nullable();
                $table->timestamps();

                $table->unique(['mailbox_id', 'imap_path']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_folders');
    }
};
