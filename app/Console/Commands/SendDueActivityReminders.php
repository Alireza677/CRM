<?php

namespace App\Console\Commands;

use App\Models\ActivityReminder;
use App\Models\Activity;
use App\Models\User;
use App\Services\Notifications\NotificationRouter;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendDueActivityReminders extends Command
{
    protected $signature = 'activities:send-reminders';
    protected $description = 'Send due activity reminders to assignees';

    public function handle(): int
    {
        $now = Carbon::now();

        $reminders = ActivityReminder::query()
            ->with(['activity.assignedTo','notifyUser'])
            ->whereNull('sent_at')
            ->limit(500)
            ->get();

        $sent = 0; $skipped = 0;
        foreach ($reminders as $rem) {
            $activity = $rem->activity; if (!$activity) { $skipped++; continue; }
            $user = $rem->notifyUser ?: $activity->assignedTo; if (!$user) { $skipped++; continue; }

            $scheduledAt = $this->computeScheduledAt($rem, $activity);
            if (!$scheduledAt) { $skipped++; continue; }
            if ($now->lt($scheduledAt)) { continue; } // not yet

            try {
                $url = route('activities.show', $activity);
                $subject = 'یادآوری وظیفه: ' . (string) ($activity->subject ?? ('#'.$activity->id));
                $body = 'زمان موعد: ' . (string) ($activity->due_at_jalali ?? $activity->start_at_jalali ?? '') . "\n" . 'برای مشاهده کلیک کنید.';

                // Prefer NotificationRouter if available/configured
                try {
                    if (app()->bound(NotificationRouter::class)) {
                        $router = app(NotificationRouter::class);
                        $ctx = [
                            'activity' => $activity,
                            'url' => $url,
                            'actor' => auth()->user(),
                        ];
                        $router->route('activities', 'reminder.due', $ctx, [$user]);
                    } else {
                        $user->notify(new \App\Notifications\CustomRoutedNotification('activities','reminder.due',$subject,$body,$url));
                    }
                } catch (\Throwable $e) {
                    // Fallback to database-only
                    $user->notify(new \App\Notifications\CustomRoutedNotification('activities','reminder.due',$subject,$body,$url));
                }

                $rem->sent_at = now();
                $rem->save();
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('SendDueActivityReminders: failed to notify', [
                    'reminder_id' => $rem->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Reminders processed. sent={$sent} skipped={$skipped}");
        return self::SUCCESS;
    }

    private function computeScheduledAt(ActivityReminder $rem, Activity $activity): ?Carbon
    {
        if ($rem->kind === 'absolute') {
            return $rem->remind_at ? Carbon::parse($rem->remind_at) : null;
        }

        if ($rem->kind === 'relative') {
            if (!$activity->due_at) return null;
            $base = $activity->due_at->copy();
            $mins = (int) ($rem->offset_minutes ?? 0);
            return $base->copy()->addMinutes($mins);
        }

        if ($rem->kind === 'same_day') {
            $time = (string) ($rem->time_of_day ?? '');
            if (!preg_match('/^(\d{2}):(\d{2})$/', $time, $m)) return null;
            $hh = (int) $m[1]; $mm = (int) $m[2];
            $date = $activity->due_at ?: $activity->start_at; if (!$date) return null;
            return $date->copy()->setTime($hh, $mm, 0);
        }

        return null;
    }
}
