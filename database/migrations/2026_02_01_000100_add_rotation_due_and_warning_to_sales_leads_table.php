<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_leads', 'rotation_due_at')) {
                $table->dateTime('rotation_due_at')->nullable()->after('assigned_at')->index();
            }
            if (!Schema::hasColumn('sales_leads', 'rotation_warning_sent_at')) {
                $table->dateTime('rotation_warning_sent_at')->nullable()->after('rotation_due_at')->index();
            }
        });

        $settings = DB::table('lead_round_robin_settings')->first();
        $slaValue = (int) ($settings->sla_duration_value ?? 24);
        $slaUnit  = $settings->sla_duration_unit ?? 'hours';

        DB::table('sales_leads')
            ->whereNotNull('assigned_at')
            ->orderBy('id')
            ->chunkById(500, function ($leads) use ($slaValue, $slaUnit) {
                foreach ($leads as $lead) {
                    $assignedAt = $lead->assigned_at ? Carbon::parse($lead->assigned_at) : null;
                    if (!$assignedAt) {
                        continue;
                    }

                    $dueAt = $slaUnit === 'minutes'
                        ? $assignedAt->copy()->addMinutes($slaValue)
                        : $assignedAt->copy()->addHours($slaValue);

                    DB::table('sales_leads')
                        ->where('id', $lead->id)
                        ->update([
                            'rotation_due_at' => $dueAt,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            if (Schema::hasColumn('sales_leads', 'rotation_warning_sent_at')) {
                $table->dropIndex(['rotation_warning_sent_at']);
                $table->dropColumn('rotation_warning_sent_at');
            }
            if (Schema::hasColumn('sales_leads', 'rotation_due_at')) {
                $table->dropIndex(['rotation_due_at']);
                $table->dropColumn('rotation_due_at');
            }
        });
    }
};
