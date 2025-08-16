<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('automation_rules', function (Blueprint $table) {
            // ادمین جایگزین (می‌تواند به‌جای هر دو نفر تأیید کند)
            $table->foreignId('emergency_approver_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->after('approver_2');
        });
    }

    public function down(): void {
        Schema::table('automation_rules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('emergency_approver_id');
        });
    }
};
