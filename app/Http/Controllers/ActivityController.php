<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Contact;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

    public function create()
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

        return view('activities.create', compact('contacts','organizations','users'));
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
            'related_type'       => ['nullable','in:contact,organization'],
            'related_id'         => ['nullable','integer'],
            'status'             => ['required','in:not_started,in_progress,completed,scheduled'],
            'priority'           => ['required','in:normal,medium,high'],
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

        // نگاشت related_type به کلاس مدل
        if (!empty($data['related_type']) && !empty($data['related_id'])) {
            $map = [
                'contact'      => \App\Models\Contact::class,
                'organization' => \App\Models\Organization::class,
            ];
            $data['related_type'] = $map[$data['related_type']] ?? null;
        } else {
            $data['related_type'] = null;
            $data['related_id']   = null;
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

        // Reminders (optional)
        try {
            $reminders = (array) $request->input('reminders', []);
            $prepared = [];
            foreach ($reminders as $r) {
                $type = (string) ($r['type'] ?? '');
                if ($type === '') continue;

                if (in_array($type, ['30m_before','1h_before','1d_before'], true)) {
                    if (!$activity->due_at) {
                        // Relative reminders need a due_at; skip safely
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
                    ];
                }
            }

            if (!empty($prepared)) {
                $rows = array_map(function ($p) use ($activity, $request) {
                    return array_merge($p, [
                        'activity_id'   => $activity->id,
                        'notify_user_id'=> (int) $activity->assigned_to_id,
                        'created_by_id' => (int) (auth()->id() ?? 0) ?: null,
                    ]);
                }, $prepared);
                \App\Models\ActivityReminder::insert($rows);
            }
        } catch (\Throwable $e) {
            // Do not break creation flow on reminders error
            \Log::warning('ActivityController.store: failed to save reminders', ['error' => $e->getMessage()]);
        }

        return redirect()->route('activities.show', $activity)->with('success','وظیفه ایجاد شد.');
    }

    public function show(Activity $activity)
    {
        $this->authorizeVisibility($activity);
        return view('activities.show', ['activity' => $activity]);
    }

    public function edit(Activity $activity)
    {
        $this->authorizeVisibility($activity);

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
            'related_type'       => ['nullable','in:contact,organization'],
            'related_id'         => ['nullable','integer'],
            'status'             => ['required','in:not_started,in_progress,completed,scheduled'],
            'priority'           => ['required','in:normal,medium,high'],
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

        if (!empty($data['related_type']) && !empty($data['related_id'])) {
            $map = [
                'contact'      => \App\Models\Contact::class,
                'organization' => \App\Models\Organization::class,
            ];
            $data['related_type'] = $map[$data['related_type']] ?? null;
        } else {
            $data['related_type'] = null;
            $data['related_id']   = null;
        }

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
        $activity->update([
            'status' => 'completed',
        ]);

        return redirect()->back()->with('success', 'وضعیت وظیفه به تکمیل شده تغییر کرد.');
    }
}
