<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('task_user')) {
            Schema::create('task_user', function (Blueprint $table) {
                $table->unsignedBigInteger('task_id');
                $table->unsignedBigInteger('user_id');

                $table->primary(['task_id', 'user_id']);
                $table->index('user_id');

                $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('tasks') && Schema::hasColumn('tasks', 'assigned_to')) {
            DB::table('tasks')
                ->whereNotNull('assigned_to')
                ->orderBy('id')
                ->chunkById(500, function ($rows) {
                    $insert = [];
                    foreach ($rows as $row) {
                        $insert[] = [
                            'task_id' => $row->id,
                            'user_id' => $row->assigned_to,
                        ];
                    }
                    if (!empty($insert)) {
                        DB::table('task_user')->insertOrIgnore($insert);
                    }
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('task_user')) {
            Schema::drop('task_user');
        }
    }
};
