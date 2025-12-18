<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_round_robin_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('lead_round_robin_settings', 'enable_rotation_warning')) {
                $table->boolean('enable_rotation_warning')
                    ->default(false)
                    ->after('max_reassign_count');
            }

            if (!Schema::hasColumn('lead_round_robin_settings', 'rotation_warning_time')) {
                $table->unsignedInteger('rotation_warning_time')
                    ->default(6)
                    ->after('enable_rotation_warning');
            }

            if (!Schema::hasColumn('lead_round_robin_settings', 'rotation_warning_unit')) {
                $table->enum('rotation_warning_unit', ['hours', 'days'])
                    ->default('hours')
                    ->after('rotation_warning_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lead_round_robin_settings', function (Blueprint $table) {
            if (Schema::hasColumn('lead_round_robin_settings', 'rotation_warning_unit')) {
                $table->dropColumn('rotation_warning_unit');
            }
            if (Schema::hasColumn('lead_round_robin_settings', 'rotation_warning_time')) {
                $table->dropColumn('rotation_warning_time');
            }
            if (Schema::hasColumn('lead_round_robin_settings', 'enable_rotation_warning')) {
                $table->dropColumn('enable_rotation_warning');
            }
        });
    }
};
