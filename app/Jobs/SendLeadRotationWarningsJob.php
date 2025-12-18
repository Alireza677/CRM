<?php

namespace App\Jobs;

use App\Models\LeadRoundRobinSetting;
use App\Models\SalesLead;
use App\Models\User;
use App\Notifications\LeadRotationWarningNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SendLeadRotationWarningsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function handle(): void
    {
        $settings = LeadRoundRobinSetting::query()->first();
        if (!$settings || !$settings->enable_rotation_warning) {
            return;
        }

        $windowValue = (int) ($settings->rotation_warning_time ?? 0);
        if ($windowValue <= 0) {
            return;
        }

        $windowUnit = $settings->rotation_warning_unit ?? 'hours';
        $now = Carbon::now();
        $windowEnd = $windowUnit === 'days'
            ? $now->copy()->addDays($windowValue)
            : $now->copy()->addHours($windowValue);

        SalesLead::query()
            ->where('pool_status', SalesLead::POOL_ASSIGNED)
            ->whereNotNull('assigned_to')
            ->whereNotNull('rotation_due_at')
            ->whereNull('rotation_warning_sent_at')
            ->whereBetween('rotation_due_at', [$now, $windowEnd])
            ->orderBy('id')
            ->chunkById(200, function ($leads) use ($now) {
                foreach ($leads as $lead) {
                    DB::transaction(function () use ($lead, $now) {
                        /** @var SalesLead|null $lockedLead */
                        $lockedLead = SalesLead::query()->lockForUpdate()->find($lead->id);
                        if (!$lockedLead) {
                            return;
                        }

                        if (
                            $lockedLead->pool_status !== SalesLead::POOL_ASSIGNED
                            || empty($lockedLead->assigned_to)
                            || $lockedLead->rotation_warning_sent_at !== null
                            || empty($lockedLead->rotation_due_at)
                            || $lockedLead->rotation_due_at->lt($now)
                        ) {
                            return;
                        }

                        $hoursLeft = max(0.0, $now->diffInMinutes($lockedLead->rotation_due_at, false) / 60);

                        /** @var User|null $assignee */
                        $assignee = User::find($lockedLead->assigned_to);
                        if (!$assignee) {
                            return;
                        }

                        $assignee->notify(new LeadRotationWarningNotification(
                            $lockedLead->id,
                            $lockedLead->getNotificationTitle(),
                            $hoursLeft
                        ));

                        $lockedLead->rotation_warning_sent_at = Carbon::now();
                        $lockedLead->save();
                    });
                }
            });
    }
}
