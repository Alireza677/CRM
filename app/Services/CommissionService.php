<?php

namespace App\Services;

use App\Models\Opportunity;
use App\Models\RoleAssignment;
use Illuminate\Support\Collection;

class CommissionService
{
    /**
     * محاسبهٔ پورسانت فرصت وقتی مرحله به «won» می‌رسد؛ مبلغ فروش از فیلد `amount`
     * (در صورت نبود، value/total_price/total_amount) خوانده می‌شود و ضرایب
     * config/commission.php برای درصد پایه و سطح اعمال می‌شود.
     */
    public function calculateForOpportunity(Opportunity $opportunity): void
    {
        $opportunity->loadMissing('roleAssignments');

        $netAmount = $this->resolveNetAmount($opportunity);
        $rolePercents = config('commission.roles', []);
        $levelMultipliers = config('commission.levels', []);
        $levelOrder = config('commission.level_order', []);

        $grouped = $opportunity->roleAssignments->groupBy('role_type');

        foreach ($grouped as $roleType => $assignments) {
            // Only highest-priority level assignments (per level_order) are paid; peers in that level each get the full share.
            $targetAssignments = $this->filterAssignmentsByLevelPriority($assignments, $levelOrder);

            foreach ($targetAssignments as $assignment) {
                $appliedBasePercent = array_key_exists($roleType, $rolePercents)
                    ? (float) $rolePercents[$roleType]
                    : (float) ($assignment->base_commission_percent ?? 0);

                $multiplier = (float) ($levelMultipliers[$assignment->level] ?? 1.0);
                $finalPercent = $appliedBasePercent * $multiplier;
                $finalAmount = $this->calculateFinalAmount($netAmount, $finalPercent);

                $assignment->base_commission_percent = $appliedBasePercent;
                $assignment->final_commission_amount = $finalAmount;
                $assignment->save();
            }
        }
    }

    private function resolveNetAmount(Opportunity $opportunity): float
    {
        $fields = ['amount', 'value', 'total_price', 'total_amount'];

        foreach ($fields as $field) {
            $value = $opportunity->getAttribute($field);
            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return 0.0;
    }

    /**
     * Only keep assignments with the highest-priority level based on level_order.
     */
    private function filterAssignmentsByLevelPriority(Collection $assignments, array $levelOrder): Collection
    {
        if ($assignments->isEmpty() || empty($levelOrder)) {
            return $assignments;
        }

        $priorityMap = array_change_key_case(array_flip($levelOrder), CASE_UPPER);
        $fallbackPriority = count($levelOrder);

        $bestPriority = $assignments
            ->map(fn (RoleAssignment $assignment) => $priorityMap[strtoupper((string) $assignment->level)] ?? $fallbackPriority)
            ->min();

        return $assignments->filter(function (RoleAssignment $assignment) use ($priorityMap, $fallbackPriority, $bestPriority) {
            $priority = $priorityMap[strtoupper((string) $assignment->level)] ?? $fallbackPriority;
            return $priority === $bestPriority;
        });
    }

    private function calculateFinalAmount(float $netAmount, float $percent): float
    {
        if ($netAmount <= 0 || $percent <= 0) {
            return 0.0;
        }

        return round($netAmount * ($percent / 100), 2);
    }
}
