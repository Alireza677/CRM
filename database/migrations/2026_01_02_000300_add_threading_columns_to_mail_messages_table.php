<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('mail_messages', 'thread_key')) {
                $table->string('thread_key')->nullable()->after('message_id');
            }
            if (!Schema::hasColumn('mail_messages', 'in_reply_to')) {
                $table->string('in_reply_to')->nullable()->after('thread_key');
            }
            if (!Schema::hasColumn('mail_messages', 'references')) {
                $table->json('references')->nullable()->after('in_reply_to');
            }
            if (!Schema::hasColumn('mail_messages', 'is_starred')) {
                $table->boolean('is_starred')->default(false)->after('is_read');
            }
            if (!Schema::hasColumn('mail_messages', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('is_starred');
            }
            if (!Schema::hasColumn('mail_messages', 'is_deleted')) {
                $table->boolean('is_deleted')->default(false)->after('is_archived');
            }

            $table->index('thread_key');
            $table->index('is_archived');
            $table->index('is_deleted');
            $table->index('is_starred');
        });
    }

    public function down(): void
    {
        Schema::table('mail_messages', function (Blueprint $table) {
            $table->dropIndex(['thread_key']);
            $table->dropIndex(['is_archived']);
            $table->dropIndex(['is_deleted']);
            $table->dropIndex(['is_starred']);

            $table->dropColumn([
                'thread_key',
                'in_reply_to',
                'references',
                'is_starred',
                'is_archived',
                'is_deleted',
            ]);
        });
    }
};
