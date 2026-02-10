<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityFollowup;
use App\Models\ActivityReminder;
use App\Support\FollowupReminderSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ActivityFollowupController extends Controller
{
    public function store(Request $request, Activity $activity)
    {
        $this->authorizeVisibility($activity, $request);

        $validated = $request->validate([
            'followup_at' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
            'assigned_to_id' => ['nullable', 'exists:users,id'],
            'status' => ['nullable', 'in:pending,done,canceled'],
        ]);

        $followupAt = $this->parseDateTime($validated['followup_at'] ?? null);
        if (!$followupAt) {
            return back()->withErrors(['followup_at' => 'تاریخ پیگیری معتبر نیست.'])->withInput();
        }

        $followup = $activity->followups()->create([
            'followup_at' => $followupAt,
            'title' => $validated['title'],
            'note' => $validated['note'] ?? null,
            'assigned_to_id' => $validated['assigned_to_id'] ?? null,
            'status' => $validated['status'] ?? 'pending',
            'created_by_id' => $request->user()?->id,
        ]);

        $this->syncFollowupReminders($followup, $activity, $request->user());

        if (!empty($validated['assigned_to_id'])) {
            $assignee = \App\Models\User::find((int) $validated['assigned_to_id']);
            if ($assignee) {
                try {
                    $router = app(\App\Services\Notifications\NotificationRouter::class);
                    $context = [
                        'activity' => $activity,
                        'followup_at' => $followupAt,
                        'followup_title' => $validated['title'],
                        'note' => $validated['note'] ?? null,
                        'actor' => $request->user(),
                        'url' => route('activities.show', $activity->id) . '#followups',
                    ];
                    $router->route('activities', 'followup.assigned', $context, [$assignee]);
                } catch (\Throwable $e) {
                    \Log::warning('ActivityFollowup notify failed', ['error' => $e->getMessage()]);
                }
            }
        }

        return redirect()->route('activities.show', $activity)->with('success', 'پیگیری ثبت شد.');
    }

    public function update(Request $request, Activity $activity, ActivityFollowup $followup)
    {
        $this->authorizeVisibility($activity, $request);

        if ((int) $followup->activity_id !== (int) $activity->id) {
            abort(404);
        }

        $validated = $request->validate([
            'followup_at' => ['nullable', 'string'],
            'title' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
            'assigned_to_id' => ['nullable', 'exists:users,id'],
            'status' => ['nullable', 'in:pending,done,canceled'],
        ]);

        $payload = [];
        if (array_key_exists('followup_at', $validated)) {
            $followupAt = $this->parseDateTime($validated['followup_at']);
            if (!$followupAt) {
                return back()->withErrors(['followup_at' => 'تاریخ پیگیری معتبر نیست.'])->withInput();
            }
            $payload['followup_at'] = $followupAt;
        }
        if (array_key_exists('title', $validated)) {
            $payload['title'] = $validated['title'];
        }
        if (array_key_exists('note', $validated)) {
            $payload['note'] = $validated['note'];
        }
        if (array_key_exists('assigned_to_id', $validated)) {
            $payload['assigned_to_id'] = $validated['assigned_to_id'];
        }
        if (array_key_exists('status', $validated)) {
            $payload['status'] = $validated['status'];
        }

        if (!empty($payload)) {
            $followup->update($payload);
        }

        $this->syncFollowupReminders($followup, $activity, $request->user());

        return redirect()->route('activities.show', $activity)->with('success', 'پیگیری بروزرسانی شد.');
    }

    public function destroy(Activity $activity, ActivityFollowup $followup)
    {
        $this->authorizeVisibility($activity, request());

        if ((int) $followup->activity_id !== (int) $activity->id) {
            abort(404);
        }

        ActivityReminder::where('followup_id', $followup->id)->delete();
        $followup->delete();

        return back()->with('success', 'پیگیری حذف شد.');
    }

    private function authorizeVisibility(Activity $activity, Request $request): void
    {
        $user = $request->user();
        abort_unless(
            !$activity->is_private || $activity->created_by_id === $user?->id || $activity->assigned_to_id === $user?->id,
            403,
            'اجازه دسترسی ندارید.'
        );
    }

    private function parseDateTime(?string $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $raw = $this->toEnDigits($raw);
        $raw = str_replace('T', ' ', $raw);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $raw)) {
            $raw .= ':00';
        }

        try {
            return \Carbon\Carbon::parse($raw)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function toEnDigits(string $value): string
    {
        $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $ar = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $en = ['0','1','2','3','4','5','6','7','8','9'];
        return str_replace($ar, $en, str_replace($fa, $en, $value));
    }

    private function syncFollowupReminders(ActivityFollowup $followup, Activity $activity, ?\App\Models\User $actor): void
    {
        try {
            ActivityReminder::where('followup_id', $followup->id)->delete();

            if ($followup->status !== 'pending') {
                return;
            }

            $followupAt = $followup->followup_at instanceof Carbon
                ? $followup->followup_at
                : ($followup->followup_at ? Carbon::parse($followup->followup_at) : null);

            if (!$followupAt) {
                return;
            }

            $notifyUserId = (int) ($followup->assigned_to_id ?: $activity->assigned_to_id);
            if ($notifyUserId <= 0) {
                return;
            }

            $settingsRows = FollowupReminderSettings::getRows();
            if (empty($settingsRows)) {
                return;
            }

            $rows = [];
            foreach ($settingsRows as $row) {
                $minutes = (int) ($row['minutes'] ?? 0);
                if ($minutes < 1) {
                    continue;
                }
                $remindAt = $followupAt->copy()->subMinutes($minutes);
                $time = isset($row['time_of_day']) ? trim((string) $row['time_of_day']) : '';
                if ($time !== '' && preg_match('/^(\d{2}):(\d{2})$/', $time, $m)) {
                    $remindAt = $remindAt->setTime((int) $m[1], (int) $m[2], 0);
                }
                $rows[] = [
                    'activity_id' => $activity->id,
                    'followup_id' => $followup->id,
                    'kind' => 'absolute',
                    'offset_minutes' => null,
                    'time_of_day' => null,
                    'remind_at' => $remindAt->format('Y-m-d H:i:s'),
                    'notify_user_id' => $notifyUserId,
                    'created_by_id' => (int) ($actor?->id ?? 0) ?: null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($rows)) {
                ActivityReminder::insert($rows);
            }
        } catch (\Throwable $e) {
            \Log::warning('ActivityFollowupController.syncFollowupReminders failed', ['error' => $e->getMessage()]);
        }
    }
}
