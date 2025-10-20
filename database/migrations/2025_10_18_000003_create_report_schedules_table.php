<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('frequency', ['daily','weekly','monthly','custom']);
            $table->time('time_of_day');
            $table->unsignedTinyInteger('weekday')->nullable(); // 0=Sun .. 6=Sat (or local)
            $table->unsignedTinyInteger('day_of_month')->nullable(); // 1..31
            $table->json('emails');
            $table->enum('export_format', ['csv','xlsx','pdf']);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['active']);
            $table->index(['frequency']);
            $table->index(['report_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_schedules');
    }
};
