<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mailboxes')) {
            Schema::create('mailboxes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('email_address');
                $table->string('imap_host')->nullable();
                $table->unsignedInteger('imap_port')->nullable();
                $table->enum('imap_encryption', ['none', 'ssl', 'tls'])->default('none');
                $table->string('smtp_host')->nullable();
                $table->unsignedInteger('smtp_port')->nullable();
                $table->enum('smtp_encryption', ['none', 'ssl', 'tls'])->default('none');
                $table->string('username')->nullable();
                $table->text('password_encrypted')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_sync_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mailboxes');
    }
};
