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
        Schema::create('online_chat_groups', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('online_chat_group_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('online_chat_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member'); // owner/admin/member
            $table->timestamps();

            $table->unique(['online_chat_group_id', 'user_id']);
        });

        Schema::create('online_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('online_chat_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('online_chat_messages');
        Schema::dropIfExists('online_chat_group_user');
        Schema::dropIfExists('online_chat_groups');
    }
};
