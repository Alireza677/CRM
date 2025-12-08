<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ActivityGuard
{
    /**
     * Determine if the given model has any "real" activities (manual CRM activities or notes)
     * within the provided window.
     */
    public static function hasRealActivities(Model $model, int $withinDays = 30): bool
    {
        return self::countRealActivities($model, $withinDays) > 0;
    }

    /**
     * Returns the count of "real" activities (manual CRM activities or notes)
     * within the provided window for the polymorphic model.
     */
    public static function countRealActivities(Model $model, int $withinDays = 30): int
    {
        return self::realActivityBreakdown($model, $withinDays)['total'];
    }

    /**
     * Returns detailed counts of "real" activities grouped by source for the model.
     */
    public static function realActivityBreakdown(Model $model, int $withinDays = 30): array
    {
        $since = Carbon::now()->subDays($withinDays);

        $crmActivitiesCount = method_exists($model, 'crmActivities')
            ? $model->crmActivities()
                ->where('created_at', '>=', $since)
                ->count()
            : 0;

        $notesCount = method_exists($model, 'notes')
            ? $model->notes()
                ->where('created_at', '>=', $since)
                ->count()
            : 0;

        return [
            'since' => $since,
            'crm' => $crmActivitiesCount,
            'notes' => $notesCount,
            'total' => $crmActivitiesCount + $notesCount,
        ];
    }
}
