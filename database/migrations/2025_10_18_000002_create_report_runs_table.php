<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('report_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('executed_at');
            $table->unsignedInteger('exec_ms')->default(0);
            $table->unsignedInteger('rows_count')->default(0);
            $table->boolean('cache_used')->default(false);

            $table->index(['executed_at']);
            $table->index(['user_id']);
            $table->index(['report_id']);
            $table->index(['report_id','executed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_runs');
    }
};
