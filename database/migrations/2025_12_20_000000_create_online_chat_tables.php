<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // اگر ستون از قبل وجود دارد، هیچ تغییری نده
        if (Schema::hasColumn('online_meetings', 'online_chat_group_id')) {
            return;
        }

        Schema::table('online_meetings', function (Blueprint $table) {
            // موقعیت ستون را اگر می‌خواهی مثل قبل باشد، بعد از related_id قرار بده
            $table->unsignedBigInteger('online_chat_group_id')
                  ->nullable()
                  ->after('related_id');

            // اگر FK لازم داری، نگهش دار
            $table->foreign('online_chat_group_id')
                  ->references('id')
                  ->on('online_chat_groups')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // اگر ستون وجود ندارد، نیازی به rollback نیست
        if (!Schema::hasColumn('online_meetings', 'online_chat_group_id')) {
            return;
        }

        Schema::table('online_meetings', function (Blueprint $table) {
            // نام پیش‌فرض: online_meetings_online_chat_group_id_foreign
            $table->dropForeign(['online_chat_group_id']);
            $table->dropColumn('online_chat_group_id');
        });
    }
};
