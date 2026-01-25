<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_chat_group_user', function (Blueprint $table) {
            $table->unsignedBigInteger('last_read_message_id')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('online_chat_group_user', function (Blueprint $table) {
            $table->dropColumn('last_read_message_id');
        });
    }
};
