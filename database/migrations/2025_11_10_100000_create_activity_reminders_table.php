<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('activity_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->enum('kind', ['relative','same_day']);
            $table->integer('offset_minutes')->nullable();
            $table->string('time_of_day', 5)->nullable(); // HH:MM
            $table->foreignId('notify_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['kind']);
            $table->index(['sent_at']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('activity_reminders');
    }
};

