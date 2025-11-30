<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
    {
        // اگر جدول از قبل وجود دارد، چیزی نساز
        if (Schema::hasTable('online_meetings')) {
            return;
        }

        Schema::create('online_meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->foreignId('online_chat_group_id')->nullable()->constrained('online_chat_groups')->nullOnDelete();
            $table->dateTime('scheduled_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->string('room_name');
            $table->string('jitsi_url');
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_meetings');
    }
};
