<?php

namespace App\Jobs;

use App\Models\LeadRoundRobinSetting;
use App\Models\LeadRoundRobinUser;
use App\Models\SalesLead;
use App\Models\User;
use App\Services\Notifications\NotificationRouter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckLeadSlaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(
        protected int $slaHours = 24
    ) {}

    public function handle(NotificationRouter $router): void
    {
        $settings = LeadRoundRobinSetting::query()->first();

        $slaValue = $settings?->sla_duration_value ?? $this->slaHours;
        $slaUnit  = $settings?->sla_duration_unit ?? 'hours';

        $now = Carbon::now();
        $threshold = $slaUnit === 'minutes'
            ? $now->copy()->subMinutes($slaValue)
            : $now->copy()->subHours($slaValue);
        $managers = $this->managerRecipients();

        SalesLead::query()
            ->where('pool_status', SalesLead::POOL_ASSIGNED)
            ->whereNotNull('assigned_to')
            ->whereNotNull('assigned_at')
            ->whereNull('first_activity_at')
            ->where(function ($q) use ($now, $threshold) {
                $q->whereNotNull('rotation_due_at')
                    ->where('rotation_due_at', '<=', $now)
                    ->orWhere(function ($q2) use ($threshold) {
                        $q2->whereNull('rotation_due_at')
                            ->where('assigned_at', '<=', $threshold);
                    });
            })
            ->orderBy('id')
            ->chunkById(200, function ($leads) use ($router, $managers, $slaValue, $slaUnit, $now) {
                foreach ($leads as $lead) {
                    DB::transaction(function () use ($lead, $router, $managers, $slaValue, $slaUnit, $now) {
                        /** @var SalesLead|null $lockedLead */
                        $lockedLead = SalesLead::query()->lockForUpdate()->find($lead->id);
                        if (!$lockedLead) {
                            return;
                        }

                        if (empty($lockedLead->rotation_due_at) && $lockedLead->assigned_at) {
                            $lockedLead->rotation_due_at = $this->calculateRotationDueAt($lockedLead->assigned_at, $slaValue, $slaUnit);
                            $lockedLead->rotation_warning_sent_at = null;
                            $lockedLead->save();
                        }

                        if ($lockedLead->rotation_due_at && $lockedLead->rotation_due_at->gt($now)) {
                            return;
                        }

                        // Skip if the lead no longer matches the SLA-breach conditions.
                        if (
                            $lockedLead->pool_status !== SalesLead::POOL_ASSIGNED
                            || empty($lockedLead->assigned_to)
                            || $lockedLead->first_activity_at !== null
                            || $lockedLead->assigned_at === null
                            || ($lockedLead->rotation_due_at && $lockedLead->rotation_due_at->gt($now))
                        ) {
                            return;
                        }

                        $maxReassignCountLocal = (int) (LeadRoundRobinSetting::query()->value('max_reassign_count') ?? 2);

                        if ($maxReassignCountLocal === 0 || (int) ($lockedLead->auto_reassign_count ?? 0) >= $maxReassignCountLocal) {
                            $lockedLead->forceFill([
                                'pool_status' => SalesLead::POOL_NEEDS_REASSIGNMENT,
                            ])->save();

                            return;
                        }

                        $activeQuery = LeadRoundRobinUser::query()
                            ->where('is_active', true)
                            ->lockForUpdate();

                        $activeCount = (clone $activeQuery)->count();

                        $candidateQuery = (clone $activeQuery)
                            ->orderByRaw('last_assigned_at IS NOT NULL')
                            ->orderBy('last_assigned_at');

                        if ($activeCount >= 2) {
                            $candidateQuery->where('user_id', '!=', $lockedLead->assigned_to);
                        }

                        $nextAssignee = $candidateQuery->first();

                        if ($nextAssignee) {
                            $now = Carbon::now();

                            $lockedLead->forceFill([
                                'assigned_to' => $nextAssignee->user_id,
                                'assigned_at' => $now,
                                'pool_status' => SalesLead::POOL_ASSIGNED,
                                'auto_reassign_count' => ($lockedLead->auto_reassign_count ?? 0) + 1,
                                'rotation_due_at' => $this->calculateRotationDueAt($now, $slaValue, $slaUnit),
                                'rotation_warning_sent_at' => null,
                            ])->save();

                            $nextAssignee->forceFill([
                                'last_assigned_at' => $now,
                            ])->save();

                            return;
                        }

                        $lockedLead->forceFill([
                            'pool_status' => SalesLead::POOL_NEEDS_REASSIGNMENT,
                        ])->save();

                        Log::warning('lead_round_robin_empty_active_list', [
                            'lead_id' => $lockedLead->id,
                        ]);

                        if (!empty($managers)) {
                            $this->notifyManagers($router, $lockedLead, $managers);
                        }
                    });
                }
            });
    }

    protected function calculateRotationDueAt(?Carbon $start, int $value, string $unit): ?Carbon
    {
        if (!$start) {
            return null;
        }

        $base = Carbon::parse($start);

        return $unit === 'minutes'
            ? $base->copy()->addMinutes($value)
            : $base->copy()->addHours($value);
    }

    protected function managerRecipients(): array
    {
        return User::query()
            ->where(function ($q) {
                $q->where('is_admin', true)
                    ->orWhereHas('roles', function ($roleQuery) {
                        $roleQuery->whereIn('name', ['admin', 'super-admin', 'sales_manager']);
                    });
            })
            ->pluck('id')
            ->unique()
            ->values()
            ->all();
    }

    protected function notifyManagers(NotificationRouter $router, SalesLead $lead, array $recipients): void
    {
        try {
            $router->route('leads', 'sla.breached', [
                'model' => $lead,
                'lead_id' => $lead->id,
                'assigned_to' => $lead->assigned_to,
                'url' => route('marketing.leads.show', $lead->id),
            ], $recipients);
        } catch (\Throwable $e) {
            Log::warning('CheckLeadSlaJob notification failed', [
                'lead_id' => $lead->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
