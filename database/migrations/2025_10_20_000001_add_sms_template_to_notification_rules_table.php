<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_rules', function (Blueprint $table) {
            $table->text('sms_template')->nullable()->after('body_template');
        });
    }

    public function down(): void
    {
        Schema::table('notification_rules', function (Blueprint $table) {
            $table->dropColumn('sms_template');
        });
    }
};

