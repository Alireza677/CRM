<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_meetings', function (Blueprint $table) {
            $table->foreignId('online_chat_group_id')
                ->nullable()
                ->after('related_id')
                ->constrained('online_chat_groups')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('online_meetings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('online_chat_group_id');
        });
    }
};
