<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Column already exists; this migration is now a no-op to avoid duplicate column errors.
    }

    public function down(): void
    {
        if (Schema::hasColumn('online_meetings', 'online_chat_group_id')) {
            Schema::table('online_meetings', function (Blueprint $table) {
                $table->dropConstrainedForeignId('online_chat_group_id');
            });
        }
    }
};
