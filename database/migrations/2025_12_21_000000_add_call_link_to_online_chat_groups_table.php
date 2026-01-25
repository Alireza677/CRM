<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('online_chat_groups', 'call_link')) {
            return;
        }

        Schema::table('online_chat_groups', function (Blueprint $table) {
            $table->string('call_link')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('online_chat_groups', 'call_link')) {
            return;
        }

        Schema::table('online_chat_groups', function (Blueprint $table) {
            $table->dropColumn('call_link');
        });
    }
};
