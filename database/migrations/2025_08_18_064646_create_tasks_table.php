<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            // ارتباط هر تسک با یک پروژه
            $table->foreignId('project_id')
                  ->constrained('projects')
                  ->cascadeOnDelete();

            $table->string('title');                    // عنوان تسک (لازم)
            $table->text('description')->nullable();    // توضیحات (اختیاری)

            // اولویت: عادی یا اضطراری
            $table->enum('priority', ['normal','urgent'])->default('normal');

            // وضعیت: در انتظار یا انجام شده
            $table->enum('status', ['pending','done'])->default('pending');

            $table->timestamps();

            $table->index(['project_id', 'priority', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
