<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_leads', 'rotation_timer_paused_at')) {
                $table->dateTime('rotation_timer_paused_at')->nullable()->after('rotation_warning_sent_at')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            if (Schema::hasColumn('sales_leads', 'rotation_timer_paused_at')) {
                $table->dropIndex(['rotation_timer_paused_at']);
                $table->dropColumn('rotation_timer_paused_at');
            }
        });
    }
};
