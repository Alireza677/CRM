<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (Schema::hasTable('activity_followups')) {
            return;
        }

        Schema::create('activity_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->dateTime('followup_at');
            $table->string('title');
            $table->text('note')->nullable();
            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'done', 'canceled'])->default('pending');
            $table->timestamps();

            $table->index(['activity_id', 'followup_at'], 'idx_activity_followups_activity_date');
            $table->index(['status', 'followup_at'], 'idx_activity_followups_status_date');
        });
    }

    public function down()
    {
        if (Schema::hasTable('activity_followups')) {
            Schema::dropIfExists('activity_followups');
        }
    }
};