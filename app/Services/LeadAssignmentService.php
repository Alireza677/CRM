<?php

namespace App\Services;

use App\Models\SalesLead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LeadAssignmentService
{
    /**
     * Assign the lead to the best available user based on the current rules.
     */
    public function assignToBestUser(SalesLead $lead): ?User
    {
        $query = $this->fitBySkillOrRegion($lead);

        $candidates = $this->balanceByWorkload($lead, $query);
        $winner = $this->weightByPerformance($lead, $candidates);

        if (!$winner) {
            return null;
        }

        $lead->forceFill([
            'assigned_to'   => $winner->id,
            'assigned_at'   => Carbon::now(),
            'pool_status'   => SalesLead::POOL_ASSIGNED,
        ])->save();

        return $winner;
    }

    /**
     * TODO: add real skill/region matching (skills, territories, product lines).
     * For now we try to respect team/department affinity and skip users on leave.
     */
    public function fitBySkillOrRegion(SalesLead $lead): Builder
    {
        $query = User::query()
            ->where(function (Builder $q) {
                $q->whereNull('is_on_leave')->orWhere('is_on_leave', false);
            });

        if (!empty($lead->team_id)) {
            $query->where('team_id', $lead->team_id);
        } elseif (!empty($lead->department)) {
            $query->where('department', $lead->department);
        }

        return $query;
    }

    /**
     * Spread load by preferring users with fewer open/untouched assignments.
     */
    public function balanceByWorkload(SalesLead $lead, Builder $query): Collection
    {
        $workload = SalesLead::query()
            ->selectRaw('count(*)')
            ->whereColumn('assigned_to', 'users.id')
            ->where('pool_status', SalesLead::POOL_ASSIGNED)
            ->whereNull('first_activity_at');

        return $query
            ->select('users.*')
            ->selectSub($workload, 'active_leads_count')
            ->orderBy('active_leads_count')
            ->orderBy('id')
            ->get();
    }

    /**
     * TODO: incorporate conversion rate / SLA success; for now prefer highest (optional) performance_score.
     */
    public function weightByPerformance(SalesLead $lead, Collection $candidates): ?User
    {
        if ($candidates->isEmpty()) {
            return null;
        }

        return $candidates
            ->sortByDesc(fn ($user) => (float) ($user->performance_score ?? 0))
            ->first();
    }
}
