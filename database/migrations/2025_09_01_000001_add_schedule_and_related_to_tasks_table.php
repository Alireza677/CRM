<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dateTime('start_at')->nullable()->after('description');
            $table->dateTime('due_at')->nullable()->after('start_at');
            $table->string('related_type')->nullable()->after('assigned_to');
            $table->unsignedBigInteger('related_id')->nullable()->after('related_type');

            $table->index(['related_type', 'related_id']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['related_type', 'related_id']);
            $table->dropColumn(['start_at', 'due_at', 'related_type', 'related_id']);
        });
    }
};
