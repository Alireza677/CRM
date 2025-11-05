<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_rules', function (Blueprint $table) {
            $table->text('subject_template')->nullable()->change();
            $table->longText('body_template')->nullable()->change();
            // sms_template column already exists and is nullable in a prior migration
        });
    }

    public function down(): void
    {
        Schema::table('notification_rules', function (Blueprint $table) {
            $table->text('subject_template')->nullable(false)->change();
            $table->longText('body_template')->nullable(false)->change();
        });
    }
};

