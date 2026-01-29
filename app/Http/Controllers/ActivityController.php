<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\SalesLead;
use App\Models\ActivityReminder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Morilog\Jalali\Jalalian;
use Spatie\Activitylog\Models\Activity as LogActivity;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $q        = trim((string) $request->get('q', ''));
        $status   = $request->get('status');
        $priority = $request->get('priority');

        $query = Activity::query()
            ->with([
                'assignedTo:id,name',
                // 'related'
            ]);

        // Hide system-generated logs from task list.
        $query->whereNotIn('subject', [
            'proforma_created',
            'lead_status_reason',
            'lost_reason',
        ]);

        if ($q !== '') {
            $query->where(function ($qb) use ($q) {
                $qb->where('subject', 'like', "%{$q}%")
                   ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($priority)) {
            $query->where('priority', $priority);
        }

        $activities = $query->orderByDesc('start_at')
                            ->orderByDesc('id')
                            ->paginate(20);

        return view('activities.index', compact('activities'));
    }

    public function create(Request $request)
    {
        $contacts = \DB::table('contacts')
            ->selectRaw("
                id, mobile,
                CASE
                WHEN TRIM(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))) <> ''
                    THEN TRIM(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,'')))
                WHEN COALESCE(company,'') <> '' THEN company
                ELSE 'بدون نام'
                END AS full_name
            ")
            ->orderBy('full_name')
            ->get();

        $organizations = \DB::table('organizations')
            ->select('id','name','phone')
            ->orderBy('name')
            ->get();

        $users = \App\Models\User::select('id','name')->orderBy('name')->get();

        $prefillRelated = $this->extractPrefillFromRequest($request);

        return view('activities.create', compact('contacts','organizations','users','prefillRelated'));
    }

    public function store(Request $request)
    {
        // توجه: امکان ورودی شمسی یا میلادی
        $validator = Validator::make($request->all(), [
            'subject'            => ['required','string','max:255'],

            // یکی از این دو الزامی است:
            'start_at_jalali'    => ['required_without:start_at','nullable','string'],
            'start_at'           => ['required_without:start_at_jalali','nullable','date'],

            // موعد اختیاری (شمسی/میلادی)
            'due_at_jalali'      => ['nullable','string'],
            'due_at'             => ['nullable','date'],

            'assigned_to_id'     => ['required','exists:users,id'],
            'related_type'       => ['nullable', Rule::in($this->relatedTypeRuleValues())],
            'related_id'         => ['nullable','integer'],
            'status'             => ['required','in:not_started,in_progress,completed,scheduled'],
            'priority'           => ['required','in:normal,medium,high'],
            'progress'           => ['nullable','integer','min:0','max:100'],
            'description'        => ['nullable','string'],
            'is_private'         => ['sometimes','boolean'],
        ], [], [
            'start_at_jalali' => 'تاریخ شروع (شمسی)',
            'start_at'        => 'تاریخ شروع (میلادی)',
            'due_at_jalali'   => 'تاریخ پایان (شمسی)',
            'due_at'          => 'تاریخ پایان (میلادی)',
        ]);

        $validator->after(function ($v) use ($request) {
            // اگر هر دو تاریخ (پس از تبدیل) موجود شوند، موعد نباید قبل از شروع باشد.
            // در این مرحله هنوز تبدیل انجام نشده؛ پس به‌صورت ساده اگر هر دو نسخه میلادی آمده‌اند چک می‌کنیم.
            // چک دقیق‌تر بعد از ست‌کردن روی مدل نیز انجام می‌شود.
            if ($request->filled('start_at') && $request->filled('due_at')) {
                $sa = strtotime($request->input('start_at'));
                $da = strtotime($request->input('due_at'));
                if ($sa !== false && $da !== false && $da < $sa) {
                    $v->errors()->add('due_at', 'تاریخ پایان نمی‌تواند قبل از تاریخ شروع باشد.');
                }
            }
        });

        $data = $validator->validate();

        $original = $this->timelineSnapshot($activity);

        [$data['related_type'], $data['related_id']] = $this->resolveRelatedPayload(
            $data['related_type'] ?? null,
            $data['related_id'] ?? null
        );

        if (!isset($data['progress'])) {
            $data['progress'] = $activity->progress ?? 0;
        }
        if (($data['status'] ?? null) === 'completed' && (int) $data['progress'] < 100) {
            $data['progress'] = 100;
        }

        if (!isset($data['progress'])) {
            $data['progress'] = 0;
        }
        if (($data['status'] ?? null) === 'completed' && (int) $data['progress'] < 100) {
            $data['progress'] = 100;
        }

        $activity = new Activity();
        // فیلدهای غیرتاریخی را fill کن
        $activity->fill(collect($data)->except([
            'start_at','due_at','start_at_jalali','due_at_jalali'
        ])->toArray());

        // ورودی تاریخ‌ها:
        // اولویت با نسخه‌های شمسی؛ در غیراینصورت میلادی را مستقیماً می‌پذیریم
        if ($request->filled('start_at_jalali')) {
            $activity->start_at_jalali = $request->input('start_at_jalali'); // میوتیتور → میلادی
        } elseif ($request->filled('start_at')) {
            $activity->start_at = $request->input('start_at');
        }

        if ($request->filled('due_at_jalali')) {
            $activity->due_at_jalali = $request->input('due_at_jalali'); // میوتیتور → میلادی
        } elseif ($request->filled('due_at')) {
            $activity->due_at = $request->input('due_at');
        }

        // چک ترتیب زمانی پس از تبدیل
        if ($activity->start_at && $activity->due_at && $activity->due_at->lt($activity->start_at)) {
            return back()
                ->withErrors(['due_at_jalali' => 'تاریخ پایان نمی‌تواند قبل از تاریخ شروع باشد.'])
                ->withInput();
        }

        $activity->created_by_id = auth()->id();
        $activity->updated_by_id = auth()->id();
        $activity->is_private    = (bool) $request->boolean('is_private');
        $activity->save();

        $this->touchLeadOrOpportunity($activity);

        $this->syncReminders($activity, $request);

        $this->logActivityEntry($activity, 'created', 'ایجاد فعالیت', [
            'subject' => $activity->subject,
        ]);

        return redirect()->route('activities.show', $activity)->with('success','وظیفه ایجاد شد.');
    }

    public function show(Activity $activity)
    {
        $this->authorizeVisibility($activity);
        $activity->load([
            'assignedTo:id,name',
            'creator:id,name',
            'updater:id,name',
            'related',
            'reminders',
            'notes.author:id,name,email',
            'notes.attachments',
            'followups.assignedTo:id,name',
            'followups.creator:id,name',
            'attachments',
        ]);

        $timeline = LogActivity::query()
            ->with('causer:id,name')
            ->where('subject_type', Activity::class)
            ->where('subject_id', $activity->id)
            ->latest()
            ->get();

        $users = User::select('id','name','username','email')->orderBy('name')->get();

        return view('activities.show', [
            'activity' => $activity,
            'timeline' => $timeline,
            'users' => $users,
        ]);
    }

    public function edit(Activity $activity)
    {
        $this->authorizeVisibility($activity);
        $activity->load('reminders');

        // یکدست با create: ساخت name ترکیبی برای Contacts
        $contacts = Contact::query()
            ->selectRaw("
                id,
                CASE
                  WHEN TRIM(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))) <> ''
                    THEN TRIM(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,'')))
                  WHEN COALESCE(company,'') <> '' THEN company
                  ELSE 'بدون نام'
                END AS name
            ")
            ->orderBy('name')
            ->get();

        $organizations = Organization::select('id','name')->orderBy('name')->get();
        $users = \App\Models\User::select('id','name')->orderBy('name')->get();

        return view('activities.edit', compact('activity','contacts','organizations','users'));
    }

    public function update(Request $request, Activity $activity)
    {
        $this->authorizeVisibility($activity);

        $validator = Validator::make($request->all(), [
            'subject'            => ['required','string','max:255'],

            // یکی از این دو الزامی است:
            'start_at_jalali'    => ['required_without:start_at','nullable','string'],
            'start_at'           => ['required_without:start_at_jalali','nullable','date'],

            'due_at_jalali'      => ['nullable','string'],
            'due_at'             => ['nullable','date'],

            'assigned_to_id'     => ['required','exists:users,id'],
            'related_type'       => ['nullable', Rule::in($this->relatedTypeRuleValues())],
            'related_id'         => ['nullable','integer'],
            'status'             => ['required','in:not_started,in_progress,completed,scheduled'],
            'priority'           => ['required','in:normal,medium,high'],
            'progress'           => ['nullable','integer','min:0','max:100'],
            'description'        => ['nullable','string'],
            'is_private'         => ['sometimes','boolean'],
        ], [], [
            'start_at_jalali' => 'تاریخ شروع (شمسی)',
            'start_at'        => 'تاریخ شروع (میلادی)',
            'due_at_jalali'   => 'تاریخ پایان (شمسی)',
            'due_at'          => 'تاریخ پایان (میلادی)',
        ]);

        $validator->after(function ($v) use ($request) {
            if ($request->filled('start_at') && $request->filled('due_at')) {
                $sa = strtotime($request->input('start_at'));
                $da = strtotime($request->input('due_at'));
                if ($sa !== false && $da !== false && $da < $sa) {
                    $v->errors()->add('due_at', 'تاریخ پایان نمی‌تواند قبل از تاریخ شروع باشد.');
                }
            }
        });

        $data = $validator->validate();

        [$data['related_type'], $data['related_id']] = $this->resolveRelatedPayload(
            $data['related_type'] ?? null,
            $data['related_id'] ?? null
        );

        // فیلدهای غیرتاریخی
        $activity->fill(collect($data)->except([
            'start_at','due_at','start_at_jalali','due_at_jalali'
        ])->toArray());

        // تاریخ‌ها (اولویت با شمسی)
        if ($request->filled('start_at_jalali')) {
            $activity->start_at_jalali = $request->input('start_at_jalali');
        } elseif ($request->filled('start_at')) {
            $activity->start_at = $request->input('start_at');
        }

        if ($request->filled('due_at_jalali')) {
            $activity->due_at_jalali = $request->input('due_at_jalali');
        } elseif ($request->filled('due_at')) {
            $activity->due_at = $request->input('due_at');
        }

        // چک ترتیب زمانی پس از تبدیل
        if ($activity->start_at && $activity->due_at && $activity->due_at->lt($activity->start_at)) {
            return back()
                ->withErrors(['due_at_jalali' => 'تاریخ پایان نمی‌تواند قبل از تاریخ شروع باشد.'])
                ->withInput();
        }

        $activity->updated_by_id = auth()->id();
        $activity->is_private    = (bool) $request->boolean('is_private');
        $activity->save();

        $this->syncReminders($activity, $request);
        $this->logTimelineChanges($activity, $original);

        return redirect()->route('activities.show', $activity)->with('success','وظیفه بروزرسانی شد.');
    }

    public function destroy(Activity $activity)
    {
        $this->authorizeVisibility($activity);
        $activity->delete();
        return redirect()->route('activities.index')->with('success','وظیفه حذف شد.');
    }

    private function authorizeVisibility(Activity $a): void
    {
        $u = auth()->user();
        abort_unless(
            !$a->is_private || $a->created_by_id === $u->id || $a->assigned_to_id === $u->id,
            403, 'اجازه دسترسی ندارید.'
        );
    }

    
    
    public function markComplete(Activity $activity)
    {
        $original = $this->timelineSnapshot($activity);

        $activity->update([
            'status' => 'completed',
            'progress' => 100,
        ]);

        $this->logTimelineChanges($activity, $original);

        return redirect()->back()->with('success', 'وضعیت وظیفه به تکمیل‌شده تغییر کرد.');
    }

    public function updateProgress(Request $request, Activity $activity)
    {
        $this->authorizeVisibility($activity);

        $validated = $request->validate([
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $original = $this->timelineSnapshot($activity);
        $activity->progress = (int) $validated['progress'];
        $activity->updated_by_id = $request->user()?->id;
        $activity->save();

        $this->logTimelineChanges($activity, $original);

        return redirect()->back()->with('success', 'پیشرفت بروزرسانی شد.');
    }

    /**
     * Ø§ÛÙ ÙÚ¯Ø§Ø´Øª Ø¯Ø± create/store/edit/update Ø§Ø³ØªÙØ§Ø¯Ù ÙÛâØ´ÙØ¯ ØªØ§ Ø§Ø³ÙØ§Ú¯ ÙÙØ¹ ÙØ±ØªØ¨Ø· Ø¨Ù Ú©ÙØ§Ø³ ÙØ¯Ù ØªØ¨Ø¯ÛÙ Ø´ÙØ¯.
     */
    private function relatedTypeMap(): array
    {
        return [
            'contact'      => Contact::class,
            'organization' => Organization::class,
            'sales_lead'   => SalesLead::class,
            'opportunity'  => Opportunity::class,
        ];
    }

    /**
     * Ø§ÛÙ ÙØªØ¯ ÙÙÚ¯Ø§Ù validate Ø¯Ø± store/update ÙØ±Ø§Ø®ÙØ§ÙÛ ÙÛâØ´ÙØ¯ ØªØ§ Rule::in Ø§Ø² ÙÙØ§Ø¯ÛØ± ÙØ¬Ø§Ø² Ø³Ø§Ø®ØªÙ Ø´ÙØ¯.
     */
    private function relatedTypeRuleValues(): array
    {
        $map = $this->relatedTypeMap();
        return array_values(array_unique(array_merge(array_keys($map), array_values($map))));
    }

    /**
     * Ø§ÛÙ ÙØªØ¯ Ø¯Ø± create ØµØ¯Ø§ Ø²Ø¯Ù ÙÛâØ´ÙØ¯ ØªØ§ Ø§Ú¯Ø± ÙØ±Ù Ø§Ø² ØµÙØ­Ù Lead/Opportunity Ø¨Ø§ query string Ø¢ÙØ¯Ù Ø¨ÙØ¯Ø hidden ÙØ§ Ø¯Ø±Ø³Øª Ù¾Ø± Ø´ÙÙØ¯.
     */
    private function extractPrefillFromRequest(Request $request): array
    {
        $type = $this->normalizeRelatedTypeKey($request->get('related_type'));
        $id   = $request->get('related_id');

        [$resolvedType, $resolvedId] = $this->resolveRelatedPayload($type, $id);
        $label = trim((string) $request->get('related_label', ''));

        return [
            'type'  => $resolvedType ? $this->normalizeRelatedTypeKey($type) : null,
            'id'    => $resolvedId,
            'label' => $label !== '' ? $label : null,
        ];
    }

    /**
     * Ø§ÛÙ ÙØªØ¯ Ø¯Ø± store/update Ø§Ø³ØªÙØ§Ø¯Ù ÙÛâØ´ÙØ¯ ØªØ§ related_type/related_id ØªØ¨Ø¯ÛÙ Ø¨Ù Ú©ÙØ§Ø³ ÙØ¹ØªØ¨Ø± Ù Ø±Ú©ÙØ±Ø¯ ÙÙØ¬ÙØ¯ Ø´ÙØ¯.
     */
    private function resolveRelatedPayload(?string $type, $id): array
    {
        if (empty($type) || empty($id)) {
            return [null, null];
        }

        $slug = $this->normalizeRelatedTypeKey($type);
        $map  = $this->relatedTypeMap();

        if (!$slug || !isset($map[$slug])) {
            return [null, null];
        }

        $class = $map[$slug];
        $intId = (int) $id;

        if ($intId <= 0 || !$class::where('id', $intId)->exists()) {
            return [null, null];
        }

        return [$class, $intId];
    }

    /**
     * Ø§ÛÙ ÙØªØ¯ Ø¯Ø± create/store/update Ø¨Ø±Ø§Û ØªØ¨Ø¯ÛÙ FQCN Ø¨Ù Ø§Ø³ÙØ§Ú¯ Ù Ø¨Ø±Ø¹Ú©Ø³ (Ø¨ÙâØµÙØ±Øª Ø§ÙÙ) Ø§Ø³ØªÙØ§Ø¯Ù ÙÛâØ´ÙØ¯.
     */
    private function normalizeRelatedTypeKey(?string $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $map = $this->relatedTypeMap();
        if (isset($map[$raw])) {
            return $raw;
        }

        $slug = array_search($raw, $map, true);
        return $slug === false ? null : $slug;
    }

    private function syncReminders(Activity $activity, Request $request): void
    {
        if (!$request->has('reminders_present') && !$request->has('reminders')) {
            return;
        }

        try {
            ActivityReminder::where('activity_id', $activity->id)->delete();

            $reminders = (array) $request->input('reminders', []);
            $prepared = [];
            foreach ($reminders as $r) {
                $type = (string) ($r['type'] ?? '');
                if ($type === '') {
                    continue;
                }

                if (in_array($type, ['30m_before','1h_before','1d_before'], true)) {
                    if (!$activity->due_at) {
                        continue;
                    }
                    $map = [
                        '30m_before' => -30,
                        '1h_before'  => -60,
                        '1d_before'  => -1440,
                    ];
                    $prepared[] = [
                        'kind' => 'relative',
                        'offset_minutes' => $map[$type] ?? null,
                        'time_of_day' => null,
                        'remind_at' => null,
                    ];
                } elseif ($type === 'same_day') {
                    $time = trim((string) ($r['time'] ?? ''));
                    if ($time === '' || !preg_match('/^\d{2}:\d{2}$/', $time)) {
                        continue;
                    }
                    $prepared[] = [
                        'kind' => 'same_day',
                        'offset_minutes' => null,
                        'time_of_day' => $time,
                        'remind_at' => null,
                    ];
                } elseif ($type === 'absolute') {
                    $dt = $this->parseDateTimeInput($r['datetime'] ?? $r['remind_at'] ?? null);
                    if (!$dt) {
                        continue;
                    }
                    $prepared[] = [
                        'kind' => 'absolute',
                        'offset_minutes' => null,
                        'time_of_day' => null,
                        'remind_at' => $dt,
                    ];
                }
            }

            if (!empty($prepared)) {
                $rows = array_map(function ($p) use ($activity, $request) {
                    return array_merge($p, [
                        'activity_id'   => $activity->id,
                        'notify_user_id'=> (int) $activity->assigned_to_id,
                        'created_by_id' => (int) ($request->user()?->id ?? 0) ?: null,
                    ]);
                }, $prepared);
                ActivityReminder::insert($rows);
            }
        } catch (\Throwable $e) {
            \Log::warning('ActivityController.syncReminders: failed to save reminders', ['error' => $e->getMessage()]);
        }
    }

    private function parseDateTimeInput($value): ?string
    {
        if ($value === null) {
            return null;
        }

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

    private function toEnDigits(?string $s): ?string
    {
        if ($s === null) return null;
        $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $ar = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $en = ['0','1','2','3','4','5','6','7','8','9'];
        return str_replace($ar, $en, str_replace($fa, $en, $s));
    }

    private function timelineSnapshot(Activity $activity): array
    {
        return [
            'status' => $activity->status,
            'progress' => (int) ($activity->progress ?? 0),
            'start_at' => $activity->start_at?->toDateTimeString(),
            'due_at' => $activity->due_at?->toDateTimeString(),
            'assigned_to_id' => $activity->assigned_to_id,
        ];
    }

    private function logTimelineChanges(Activity $activity, array $original): void
    {
        $current = $this->timelineSnapshot($activity);

        if (($original['status'] ?? null) !== ($current['status'] ?? null)) {
            $old = $this->statusLabel($original['status'] ?? null);
            $new = $this->statusLabel($current['status'] ?? null);
            $this->logActivityEntry($activity, 'status_changed', "وضعیت تغییر کرد: {$old} → {$new}", [
                'field' => 'status',
                'old' => $original['status'] ?? null,
                'new' => $current['status'] ?? null,
            ]);
        }

        if (($original['progress'] ?? null) !== ($current['progress'] ?? null)) {
            $this->logActivityEntry($activity, 'progress_changed', 'پیشرفت تغییر کرد: ' . ($original['progress'] ?? 0) . '% → ' . ($current['progress'] ?? 0) . '%', [
                'field' => 'progress',
                'old' => $original['progress'] ?? 0,
                'new' => $current['progress'] ?? 0,
            ]);
        }

        if (($original['start_at'] ?? null) !== ($current['start_at'] ?? null)) {
            $old = $this->formatJalaliDateTime($original['start_at'] ?? null);
            $new = $this->formatJalaliDateTime($current['start_at'] ?? null);
            $this->logActivityEntry($activity, 'start_at_changed', "تاریخ شروع تغییر کرد: {$old} → {$new}", [
                'field' => 'start_at',
                'old' => $original['start_at'] ?? null,
                'new' => $current['start_at'] ?? null,
            ]);
        }

        if (($original['due_at'] ?? null) !== ($current['due_at'] ?? null)) {
            $old = $this->formatJalaliDateTime($original['due_at'] ?? null);
            $new = $this->formatJalaliDateTime($current['due_at'] ?? null);
            $this->logActivityEntry($activity, 'due_at_changed', "موعد مقرر تغییر کرد: {$old} → {$new}", [
                'field' => 'due_at',
                'old' => $original['due_at'] ?? null,
                'new' => $current['due_at'] ?? null,
            ]);
        }

        if (($original['assigned_to_id'] ?? null) !== ($current['assigned_to_id'] ?? null)) {
            $ids = collect([$original['assigned_to_id'] ?? null, $current['assigned_to_id'] ?? null])->filter()->unique();
            $names = User::whereIn('id', $ids)->pluck('name', 'id');
            $oldName = $original['assigned_to_id'] ? ($names[$original['assigned_to_id']] ?? ('#' . $original['assigned_to_id'])) : '—';
            $newName = $current['assigned_to_id'] ? ($names[$current['assigned_to_id']] ?? ('#' . $current['assigned_to_id'])) : '—';
            $this->logActivityEntry($activity, 'assignee_changed', "ارجاع به تغییر کرد: {$oldName} → {$newName}", [
                'field' => 'assigned_to_id',
                'old' => $original['assigned_to_id'] ?? null,
                'new' => $current['assigned_to_id'] ?? null,
            ]);
        }
    }

    private function logActivityEntry(Activity $activity, string $event, string $description, array $properties = []): void
    {
        try {
            activity('activities')
                ->performedOn($activity)
                ->causedBy(auth()->user())
                ->withProperties(array_merge(['event' => $event], $properties))
                ->log($description);
        } catch (\Throwable $e) {
            \Log::warning('ActivityController.logActivityEntry failed', ['error' => $e->getMessage()]);
        }
    }

    private function statusLabel(?string $status): string
    {
        $map = [
            'not_started' => 'شروع نشده',
            'in_progress' => 'در حال انجام',
            'completed' => 'تکمیل شده',
            'scheduled' => 'برنامه‌ریزی شده',
        ];
        return $map[$status] ?? '—';
    }

    private function formatJalaliDateTime(?string $value): string
    {
        if (!$value) {
            return '—';
        }

        try {
            return Jalalian::fromCarbon(\Carbon\Carbon::parse($value))->format('Y/m/d H:i');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    /**
     * Ø§ÛÙ ÙØªØ¯ Ø¨ÙØ§ÙØ§ØµÙÙ Ø¨Ø¹Ø¯ Ø§Ø² Ø³Ø§Ø®Øª Activity ØµØ¯Ø§ Ø²Ø¯Ù ÙÛâØ´ÙØ¯ ØªØ§ first_activity_at Ø±ÙÛ Lead (Ù Ø¯Ø± Ø¢ÛÙØ¯Ù Opportunity) Ø³Øª Ø´ÙØ¯.
     */
    private function touchLeadOrOpportunity(Activity $activity): void
    {
        if ($activity->related_type === SalesLead::class && $activity->related_id) {
            $lead = SalesLead::find($activity->related_id);
            if ($lead) {
                $lead->markFirstActivity($activity->start_at ?? $activity->due_at ?? now());
            }
            return;
        }

        if ($activity->related_type === Opportunity::class && $activity->related_id) {
            // در صورت اضافه شدن first_activity_at به Opportunity، اینجا مشابه Lead مقداردهی کنید.
        }
    }
}
