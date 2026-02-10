
@extends('layouts.app')

@section('content')
<div class="container mx-auto space-y-6">
    @if (session('success'))
        <div class="p-3 rounded bg-green-50 text-green-800 border border-green-100">
            {{ session('success') }}
        </div>
    @endif

    <div>
        <a href="{{ route('projects.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-blue-200 bg-blue-50 text-blue-700 text-sm font-medium hover:bg-blue-100 hover:text-blue-800 transition">
            بازگشت به پروژه‌ها
        </a>
    </div>

    @php
        $isCompleted = ($project->status ?? \App\Models\Project::STATUS_ACTIVE) === \App\Models\Project::STATUS_COMPLETED;
        $statusLabel = $isCompleted ? 'اتمام یافته' : 'در حال اجرا';
        $statusClass = $isCompleted ? 'bg-red-100 text-red-700 border-red-200' : 'bg-emerald-100 text-emerald-700 border-emerald-200';
        $tasksCount = (int) $project->tasks()->count();
        $doneCount = (int) $project->tasks()->where('status', \App\Models\Task::STATUS_DONE)->count();
        $progress = $tasksCount > 0 ? (int) round(($doneCount / $tasksCount) * 100) : 0;
        $progressRadius = 18;
        $progressCircumference = 2 * 3.14159 * $progressRadius;
        $progressOffset = $progressCircumference * (1 - ($progress / 100));
    @endphp

    @if($isCompleted)
        <div class="p-4 rounded-lg border border-red-200 bg-red-50 text-red-800">
            <strong class="font-semibold">این پروژه به اتمام رسیده است.</strong>
        </div>
    @endif

    {{-- Project Header Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
            <div class="space-y-3">
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900">{{ $project->name }}</h1>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full border text-xs font-medium {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-700">
                    <div>
                        <span class="font-semibold">مسئول پروژه:</span>
                        <span>{{ $project->manager?->name ?? $project->manager?->email ?? '—' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-semibold">اعضا:</span>
                        <div class="flex items-center gap-3 flex-wrap">
                            <div class="flex items-center -space-x-2 space-x-reverse">
                                @forelse($project->members as $member)
                                @php
                                    $memberName = trim($member->name ?? $member->email ?? '');
                                    $parts = preg_split('/\s+/u', $memberName, -1, PREG_SPLIT_NO_EMPTY);
                                    $initials = '';
                                    if (!empty($parts)) {
                                        $first = $parts[0] ?? '';
                                        $second = $parts[1] ?? '';
                                        $initials = mb_substr($first, 0, 1, 'UTF-8');
                                        if ($second) {
                                            $initials .= mb_substr($second, 0, 1, 'UTF-8');
                                        } elseif (mb_strlen($first, 'UTF-8') > 1) {
                                            $initials .= mb_substr($first, 1, 1, 'UTF-8');
                                        }
                                    }
                                    $avatarUrl = $member && $member->profile_photo_path
                                        ? asset('storage/' . $member->profile_photo_path)
                                        : null;
                                    $avatarPalette = [
                                        'bg-amber-500 text-white',
                                        'bg-emerald-500 text-white',
                                        'bg-sky-500 text-white',
                                        'bg-rose-500 text-white',
                                        'bg-violet-500 text-white',
                                        'bg-lime-500 text-white',
                                    ];
                                    $avatarClass = $avatarPalette[$member->id % count($avatarPalette)];
                                @endphp
                                    <div class="flex flex-col items-center -space-y-1 relative group cursor-default">
                                        <div class="w-8 h-8 rounded-full border border-white overflow-hidden flex items-center justify-center text-xs font-semibold {{ $avatarUrl ? 'bg-gray-100' : $avatarClass }}" title="{{ $member->name ?? $member->email }}">
                                            @if($avatarUrl)
                                                <img src="{{ $avatarUrl }}" alt="{{ $member->name ?? $member->email }}" class="w-full h-full object-cover">
                                            @else
                                                {{ $initials ?: '—' }}
                                            @endif
                                        </div>
                                        <span class="pointer-events-none absolute -top-12 right-1/2 translate-x-1/2 whitespace-nowrap rounded bg-gray-900 text-white text-[11px] px-2 py-1 opacity-0 group-hover:opacity-100 transition">
                                            {{ $member->name ?? $member->email }}
                                        </span>
                                        @can('manageMembers', $project)
                                            @unless($isCompleted)
                                                @if($member->id !== $project->manager_id)
                                                    <form action="{{ route('projects.members.remove', [$project, $member]) }}" method="POST"
                                                          onsubmit="return confirm('حذف این کاربر از پروژه؟')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="text-[10px] leading-none text-red-600 hover:text-red-700 font-bold">×</button>
                                                    </form>
                                                @endif
                                            @endunless
                                        @endcan
                                    </div>
                                @empty
                                    <span class="text-gray-500">—</span>
                                @endforelse
                            </div>
                            @can('manageMembers', $project)
                                @unless($isCompleted)
                                    <form action="{{ route('projects.members.add', $project) }}" method="POST" class="flex items-center gap-2">
                                        @csrf
                                        <select name="user_id" class="border rounded p-2 text-xs min-w-[200px]">
                                            <option value="">-- افزودن کاربر جدید به پروژه --</option>
                                            @foreach($nonMembers as $u)
                                                <option value="{{ $u->id }}">{{ $u->name ?? $u->email }}</option>
                                            @endforeach
                                        </select>
                                        <button class="px-3 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 text-xs">افزودن</button>
                                    </form>
                                @endunless
                            @endcan
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 ">
               
                <div class="relative" data-dropdown>
                    <button type="button" data-dropdown-toggle="project-actions"
                            class="h-9 w-9 rounded-full border border-blue-400 text-gray-600 hover:bg-gray-50 flex items-center justify-center">
                        ⋮
                    </button>
                    <div id="project-actions" data-dropdown-menu
                         class="hidden absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-lg text-sm z-20">
                        <div class="py-1">
                            @can('update', $project)
                                @unless($isCompleted)
                                    <a href="{{ route('projects.edit', $project) }}" class="block px-4 py-2 hover:bg-gray-50 text-gray-700">ویرایش پروژه</a>
                                @endunless
                            @endcan

                            @can('complete', $project)
                                @if(!$isCompleted)
                                    <form method="POST" action="{{ route('projects.complete', $project) }}">
                                        @csrf
                                        <button type="submit" class="w-full text-right px-4 py-2 hover:bg-gray-50 text-gray-700"
                                                onclick="return confirm('پروژه به وضعیت تمام شده تغییر کند؟');">
                                            اتمام پروژه
                                        </button>
                                    </form>
                                @endif
                            @endcan

                            @can('delete', $project)
                                <form method="POST" action="{{ route('projects.destroy', $project) }}"
                                      onsubmit="return confirm('حذف این پروژه؟');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-full text-right px-4 py-2 text-red-600 hover:bg-red-50">
                                        حذف پروژه
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    @php
        $hasActivities = isset($activities) && method_exists($activities, 'count') && $activities->count();
    @endphp
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="border-b border-gray-100 px-6">
            <nav class="flex flex-wrap items-center gap-6 text-sm" id="projectTabs">
                <button type="button" data-tab-button="overview" class="py-4 text-gray-900 border-b-2 border-blue-600">نمای کلی</button>
                @if($hasActivities)
                    <button type="button" data-tab-button="activity" class="py-4 text-gray-600 hover:text-gray-900">فعالیت / تاریخچه</button>
                @endif
            </nav>
        </div>

        <div class="p-6 space-y-8">
            {{-- Overview Tab --}}
            <section data-tab-panel="overview" class="hidden space-y-8">
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-gray-100 p-4">
                        <div class="text-sm text-gray-500 mb-2">توضیحات پروژه</div>
                        <div class="text-gray-800">
                            {{ $project->description ?: '—' }}
                        </div>
                    </div>
                    <div class="rounded-xl border border-gray-100 p-4">
                        <div class="text-sm text-gray-500 mb-2">زمان‌بندی</div>
                        <div class="flex items-start justify-between gap-6 text-gray-800">
                            <div class="space-y-1">
                                <div>
                                    <span class="text-gray-500">تاریخ شروع:</span>
                                    <span>{{ $project->start_date ? \Morilog\Jalali\Jalalian::fromDateTime($project->start_date)->format('Y/m/d') : '—' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">موعد / پایان:</span>
                                    <span>{{ $project->due_date ? \Morilog\Jalali\Jalalian::fromDateTime($project->due_date)->format('Y/m/d') : '—' }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="relative w-12 h-12" title="پیشرفت پروژه: {{ $progress }}%">
                                    <svg class="w-12 h-12 -rotate-90" viewBox="0 0 48 48" aria-hidden="true">
                                        <circle cx="24" cy="24" r="{{ $progressRadius }}" stroke="currentColor" stroke-width="6" fill="transparent" class="text-rose-200"></circle>
                                        <circle cx="24" cy="24" r="{{ $progressRadius }}" stroke="currentColor" stroke-width="6" fill="transparent"
                                                class="text-emerald-500"
                                                stroke-dasharray="{{ $progressCircumference }}"
                                                stroke-dashoffset="{{ $progressOffset }}"
                                                stroke-linecap="round"></circle>
                                    </svg>
                                    <span class="absolute inset-0 flex items-center justify-center text-[11px] font-semibold text-gray-700">
                                        {{ $progress }}%
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500">پیشرفت</div>
                            </div>
                        </div>
                    </div>
                </div>

                @unless($isCompleted)
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">تسک‌ها</h2>
                            <p class="text-sm text-gray-600 mt-1">مدیریت تسک‌های پروژه در این بخش انجام می‌شود.</p>
                        </div>
                        <button type="button" id="openTaskModal" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-sm">
                            ایجاد تسک
                        </button>
                    </div>
                @endunless

                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-500">{{ $tasks->total() }} مورد</div>
                    <details class="relative">
                        <summary class="list-none cursor-pointer inline-flex items-center gap-2 px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                            فیلتر
                            <span class="text-gray-400">▾</span>
                        </summary>
                        <div class="absolute left-0 mt-2 w-56 bg-white border border-gray-200 rounded-lg shadow-lg p-3 z-20">
                            <form method="GET" action="{{ route('projects.show', $project) }}" class="space-y-3">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">اولویت</label>
                                    <select name="priority" class="w-full border rounded-md p-2 text-sm" onchange="this.form.submit()">
                                        <option value=""      {{ $priority===null ? 'selected' : '' }}>همه</option>
                                        <option value="urgent" {{ $priority==='urgent' ? 'selected' : '' }}>اضطراری</option>
                                        <option value="normal" {{ $priority==='normal' ? 'selected' : '' }}>عادی</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">وضعیت</label>
                                    <select id="taskStatusFilter" class="w-full border rounded-md p-2 text-sm">
                                        <option value="">همه</option>
                                        <option value="done">انجام شد</option>
                                        <option value="pending">در انتظار</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </details>
                </div>

                <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                    <table class="min-w-full text-right text-sm">
                        <thead class="border-b bg-gray-50">
                            <tr class="text-gray-600">
                                <th class="py-3 px-4">#</th>
                                <th class="py-3 px-4">عنوان</th>
                                <th class="py-3 px-4">تاریخ شروع</th>
                                <th class="py-3 px-4"> موعد مقرر</th>
                                <th class="py-3 px-4">اولویت</th>
                                <th class="py-3 px-4">وضعیت</th>
                                <th class="py-3 px-4">مسئول</th>
                                <th class="py-3 px-4">اقدامات</th>
                            </tr>
                        </thead>
                        <tbody id="taskTableBody">
                        @forelse($tasks as $task)
                            <tr class="border-b hover:bg-gray-50" data-task-row data-status="{{ $task->status === 'done' ? 'done' : 'pending' }}">
                                <td class="py-3 px-4">{{ $task->id }}</td>
                                <td class="py-3 px-4 font-medium">
                                    <a href="{{ route('projects.tasks.show', [$project, $task]) }}" class="text-blue-700 hover:underline">
                                        {{ $task->title }}
                                    </a>
                                    @if($task->notes_count ?? false)
                                        <span class="ml-2 align-middle inline-flex items-center text-xs text-gray-600 bg-gray-100 rounded px-2 py-0.5">
                                            یادداشت‌ها: {{ $task->notes_count }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-gray-700">
                                    {{ $task->start_at ? \Morilog\Jalali\Jalalian::fromDateTime($task->start_at)->format('Y/m/d') : '—' }}
                                </td>
                                <td class="py-3 px-4 text-gray-700">
                                    {{ $task->due_at ? \Morilog\Jalali\Jalalian::fromDateTime($task->due_at)->format('Y/m/d') : '—' }}
                                </td>
                                <td class="py-3 px-4">
                                    @if($task->priority === 'urgent')
                                        <span class="px-2 py-1 rounded text-white bg-red-600 text-xs">اضطراری</span>
                                    @else
                                        <span class="px-2 py-1 rounded bg-gray-200 text-xs">عادی</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    @if($task->status === 'done')
                                        <span class="px-2 py-1 rounded text-white bg-green-600 text-xs">انجام شد</span>
                                    @else
                                        <span class="px-2 py-1 rounded bg-yellow-100 text-yellow-800 text-xs">در انتظار</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-gray-700">
                                    @php
                                        $assigneeNames = ($task->assignees ?? collect())
                                            ->map(fn ($u) => $u->name ?? $u->email)
                                            ->filter()
                                            ->values()
                                            ->all();
                                    @endphp
                                    {{ !empty($assigneeNames) ? implode('، ', $assigneeNames) : (optional($task->assignee)->name ?? optional($task->assignee)->email ?? '—') }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="relative" data-dropdown>
                                        <button type="button" data-dropdown-toggle="task-actions-{{ $task->id }}"
                                                class="px-3 py-1 rounded-md border border-gray-200 text-gray-700 hover:bg-gray-50 text-xs">
                                            عملیات
                                        </button>
                                        <div id="task-actions-{{ $task->id }}" data-dropdown-menu
                                             class="hidden absolute left-0 bottom-full mb-2 w-40 bg-white border border-gray-200 rounded-lg shadow-lg text-sm z-20">
                                            <a href="{{ route('projects.tasks.show', [$project, $task]) }}" class="block px-4 py-2 hover:bg-gray-50 text-gray-700">مشاهده</a>

                                            @unless($isCompleted)
                                                <a href="{{ route('projects.tasks.edit', [$project, $task]) }}" class="block px-4 py-2 hover:bg-gray-50 text-gray-700">ویرایش</a>
                                            @endunless

                                            @unless($isCompleted)
                                                @if($task->status !== 'done')
                                                    <form action="{{ route('projects.tasks.done', [$project, $task]) }}" method="POST"
                                                          onsubmit="return confirm('تسک انجام شد؟');">
                                                        @csrf
                                                        <button type="submit" class="w-full text-right px-4 py-2 hover:bg-gray-50 text-gray-700">
                                                            تایید انجام
                                                        </button>
                                                    </form>
                                                @endif
                                            @endunless

                                            @unless($isCompleted)
                                                <form action="{{ route('projects.tasks.destroy', [$project, $task]) }}" method="POST"
                                                      onsubmit="return confirm('حذف این تسک؟');">
                                                    @csrf @method('DELETE')
                                                    <button class="w-full text-right px-4 py-2 text-red-600 hover:bg-red-50">
                                                        حذف
                                                    </button>
                                                </form>
                                            @endunless
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="py-6 px-4 text-gray-500" colspan="8">هنوز تسکی ثبت نشده است.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-2">
                    {{ $tasks->links() }}
                </div>
            </section>
            {{-- Activity Tab --}}
            @if($hasActivities)
                <section data-tab-panel="activity" class="hidden space-y-4">
                    <div class="rounded-xl border border-gray-100 p-4">
                        <div class="text-sm text-gray-600">تاریخچه فعالیت‌ها</div>
                        <div class="mt-3 space-y-3">
                            @forelse($activities as $activity)
                                <div class="border border-gray-100 rounded-lg p-3">
                                    <div class="text-sm text-gray-800">{{ $activity->title ?? '—' }}</div>
                                    <div class="text-xs text-gray-500 mt-1">{{ $activity->created_at ?? '' }}</div>
                                </div>
                            @empty
                                <div class="text-sm text-gray-500">موردی ثبت نشده است.</div>
                            @endforelse
                        </div>
                    </div>
                </section>
            @endif
        </div>
    </div>

    @php
        $taskStartValue = old('start_at', '');
        $taskDueValue = old('due_at', '');
        $taskRelatedType = old('related_type');
        $taskRelatedId = old('related_id');
        $taskRelatedDisplay = old('related_display', '');
        if (!$taskRelatedDisplay && $taskRelatedType && $taskRelatedId) {
            $labels = ['contact' => 'مخاطب', 'organization' => 'سازمان'];
            $taskRelatedDisplay = ($labels[$taskRelatedType] ?? '#') . " #{$taskRelatedId}";
        }
        $taskAssigneeIds = old('assigned_to_ids');
        if (!is_array($taskAssigneeIds)) {
            $taskAssigneeIds = old('assigned_to') ? [old('assigned_to')] : [];
        }
        $taskErrorFields = [
            'title','priority','assigned_to','description',
            'start_at','due_at','related_type','related_id'
        ];
        $hasTaskErrors = $errors->hasAny($taskErrorFields);
    @endphp

    @unless($isCompleted)
    <div id="taskCreateModal" class="fixed inset-0 bg-black/50 z-[60] hidden items-center justify-center">
        <div class="bg-white w-11/12 md:w-3/4 max-h-[85vh] overflow-y-auto p-6 rounded shadow">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold">ایجاد تسک جدید</h2>
                <button type="button" id="closeTaskModal" class="text-gray-500 hover:text-red-600 text-xl">&times;</button>
            </div>

            @if ($hasTaskErrors)
                <div class="mb-4 p-3 rounded bg-red-100 text-red-800">
                    <ul class="list-disc pr-6">
                        @foreach ($errors->all() as $error)
                            <li class="mb-1">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('projects.tasks.store', $project) }}" method="POST" class="grid gap-4 md:grid-cols-2">
                @csrf

                <div class="md:col-span-1">
                    <label class="block mb-1 font-medium">عنوان تسک <span class="text-red-500">*</span></label>
                    <input type="text" id="task_title" name="title" value="{{ old('title') }}" required
                           class="w-full border rounded p-2 focus:outline-none focus:ring"
                           placeholder="مثلاً: نصب رادیانت لاین شماره ۳">
                </div>

                <div class="md:col-span-1">
                    <label class="block mb-1 font-medium">اولویت <span class="text-red-500">*</span></label>
                    <select name="priority" class="w-full border rounded p-2 focus:outline-none focus:ring">
                        <option value="normal" {{ old('priority')==='normal' ? 'selected' : '' }}>عادی</option>
                        <option value="urgent" {{ old('priority')==='urgent' ? 'selected' : '' }}>اضطراری</option>
                    </select>
                </div>

                <div class="md:col-span-1">
                    <label class="block mb-1 font-medium">تاریخ شروع</label>
                    <input type="hidden" id="start_at" name="start_at" value="{{ $taskStartValue }}">
                    <input type="text"
                           id="start_at_display"
                           class="persian-datepicker w-full border rounded p-2"
                           data-alt-field="start_at"
                           autocomplete="off"
                           value="">
                </div>

                <div class="md:col-span-1">
                    <label class="block mb-1 font-medium">موعد/پایان</label>
                    <input type="hidden" id="due_at" name="due_at" value="{{ $taskDueValue }}">
                    <input type="text"
                           id="due_at_display"
                           class="persian-datepicker w-full border rounded p-2"
                           data-alt-field="due_at"
                           autocomplete="off"
                           value="">
                </div>

                <div class="md:col-span-1">
                    <label class="block mb-1 font-medium">ارجاع به</label>
                    <div class="w-full border rounded p-2 focus-within:ring max-h-[200px] overflow-y-auto space-y-2">
                        @foreach($users as $user)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="assigned_to_ids[]" value="{{ $user->id }}"
                                       class="rounded border-gray-300 text-blue-600 focus:ring"
                                       {{ in_array((string)$user->id, array_map('strval', $taskAssigneeIds), true) ? 'checked' : '' }}>
                                <span>{{ $user->name ?? $user->email }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="text-xs text-gray-500 mt-1">می‌توانید چند نفر را انتخاب کنید.</div>
                </div>

                <div class="md:col-span-1">
                    <label class="block mb-1 font-medium">مربوط به</label>
                    <div class="flex gap-2">
                        <button type="button" onclick="openContactModal()" class="px-3 py-2 rounded-md bg-slate-100 hover:bg-slate-200">مخاطب +</button>
                        <button type="button" onclick="openOrganizationModal()" class="px-3 py-2 rounded-md bg-slate-100 hover:bg-slate-200">سازمان +</button>
                    </div>
                </div>

                <div class="md:col-span-1">
                    <label class="block mb-1 font-medium">آیتم انتخاب‌شده</label>
                    <input id="related_display" type="text" class="w-full rounded-md border p-2 bg-gray-50" placeholder="— انتخاب نشده —" readonly value="{{ $taskRelatedDisplay }}">
                </div>

                <input type="hidden" name="related_type" id="related_type" value="{{ $taskRelatedType }}">
                <input type="hidden" name="related_id" id="related_id" value="{{ $taskRelatedId }}">

                <div class="md:col-span-2">
                    <label class="block mb-1 font-medium">توضیحات</label>
                    <textarea name="description" rows="3"
                              class="w-full border rounded p-2 focus:outline-none focus:ring"
                              placeholder="توضیح کوتاه درباره کار...">{{ old('description') }}</textarea>
                </div>

                <div class="md:col-span-2 flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
                        ذخیره تسک
                    </button>
                    <button type="button" id="cancelTaskModal" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300">
                        انصراف
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endunless

    @include('activities.modals')
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const modalId = 'taskCreateModal';
        const openBtn = document.getElementById('openTaskModal');
        const closeBtn = document.getElementById('closeTaskModal');
        const cancelBtn = document.getElementById('cancelTaskModal');

        function toggleModal(id, open = true, focusId = null) {
            const el = document.getElementById(id);
            if (!el) return;
            if (open) {
                el.classList.remove('hidden');
                el.classList.add('flex');
                if (focusId) setTimeout(() => document.getElementById(focusId)?.focus(), 10);
            } else {
                el.classList.add('hidden');
                el.classList.remove('flex');
            }
        }

        function toggleTask(open) {
            toggleModal(modalId, open, 'task_title');
        }

        openBtn?.addEventListener('click', () => toggleTask(true));
        closeBtn?.addEventListener('click', () => toggleTask(false));
        cancelBtn?.addEventListener('click', () => toggleTask(false));

        const modal = document.getElementById(modalId);
        modal?.addEventListener('click', (e) => {
            if (e.target === modal) toggleTask(false);
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                toggleTask(false);
                closeAllDropdowns();
            }
        });

        window.openContactModal = () => toggleModal('contactModal', true, 'contactSearchInput');
        window.closeContactModal = () => toggleModal('contactModal', false);
        window.openOrganizationModal = () => toggleModal('organizationModal', true, 'organizationSearchInput');
        window.closeOrganizationModal = () => toggleModal('organizationModal', false);

        window.pickContact = (id, name) => {
            document.getElementById('related_type').value = 'contact';
            document.getElementById('related_id').value = id;
            document.getElementById('related_display').value = name;
            closeContactModal();
        };

        window.pickOrganization = (id, name) => {
            document.getElementById('related_type').value = 'organization';
            document.getElementById('related_id').value = id;
            document.getElementById('related_display').value = name;
            closeOrganizationModal();
        };

        function normalizeDigits(str) {
            if (!str) return '';
            const fa = '۰۱۲۳۴۵۶۷۸۹', ar = '٠١٢٣٤٥٦٧٨٩';
            return String(str).split('').map(ch => {
                const iFa = fa.indexOf(ch); if (iFa > -1) return String(iFa);
                const iAr = ar.indexOf(ch); if (iAr > -1) return String(iAr);
                return ch;
            }).join('');
        }

        function stripSep(str) {
            return String(str)
                .replace(/[\u200C\u200B\u00A0\s]/g, '')
                .replace(/[,\u060C.\u066B\u066C]/g, '');
        }

        function makeLiveFilter({ inputId, tbodyId, noResId }) {
            const input = document.getElementById(inputId);
            const body = document.getElementById(tbodyId);
            const noRes = document.getElementById(noResId);
            if (!input || !body) return;
            let t = null;
            input.addEventListener('input', () => { clearTimeout(t); t = setTimeout(apply, 150); });
            function apply() {
                const raw = normalizeDigits(input.value || '').toLowerCase();
                const num = stripSep(raw);
                const rows = [...body.querySelectorAll('tr')];
                if (!raw) { rows.forEach(tr => tr.classList.remove('hidden')); noRes?.classList.add('hidden'); return; }
                const isNum = /^[0-9]+$/.test(num);
                let vis = 0;
                rows.forEach(tr => {
                    const name = (tr.getAttribute('data-name') || '').toLowerCase();
                    const phone = (tr.getAttribute('data-phone') || '');
                    const ok = name.includes(raw) || (isNum ? phone.includes(num) : false);
                    tr.classList.toggle('hidden', !ok);
                    if (ok) vis++;
                });
                if (noRes) noRes.classList.toggle('hidden', vis !== 0);
            }
        }

        function initDateTimePicker(selector) {
            const $ui = $(selector);
            if (!$ui.length) return;

            const altId = $ui.attr('data-alt-field');
            const $alt = altId ? $('#' + altId) : null;

            try { $ui.persianDatepicker('destroy'); } catch (e) {}

            $ui.persianDatepicker({
                format: 'YYYY/MM/DD HH:mm',
                initialValue: false,
                autoClose: true,
                observer: true,
                calendar: {
                    persian:   { locale: 'fa', leapYearMode: 'astronomical' },
                    gregorian: { locale: 'en' }
                },
                timePicker: { enabled: true, step: 1, meridiem: { enabled: false } },
                onSelect: function (unix) {
                    if (!$alt) return;
                    try {
                        const g = new persianDate(unix).toCalendar('gregorian');
                        const y = g.year(), m = String(g.month()).padStart(2, '0'), d = String(g.date()).padStart(2, '0');
                        const hh = String(g.hour()).padStart(2, '0'), mm = String(g.minute()).padStart(2, '0');
                        $alt.val(`${y}-${m}-${d} ${hh}:${mm}:00`);
                    } catch (e) {
                        const dt = new Date(unix);
                        const y = dt.getFullYear();
                        const m = String(dt.getMonth() + 1).padStart(2, '0');
                        const d = String(dt.getDate()).padStart(2, '0');
                        const hh = String(dt.getHours()).padStart(2, '0');
                        const mm = String(dt.getMinutes()).padStart(2, '0');
                        const ss = String(dt.getSeconds()).padStart(2, '0');
                        $alt.val(`${y}-${m}-${d} ${hh}:${mm}:${ss}`);
                    }
                }
            });

            if ($alt && ($alt.val() || '').trim()) {
                const g = ($alt.val() || '').trim();
                const m = g.match(/^(\d{4})-(\d{2})-(\d{2})[T\s](\d{2}):(\d{2})(?::(\d{2}))?$/);
                if (m) {
                    const y  = parseInt(m[1], 10);
                    const mo = parseInt(m[2], 10);
                    const d  = parseInt(m[3], 10);
                    const hh = parseInt(m[4], 10);
                    const mm = parseInt(m[5], 10);
                    const ss = parseInt(m[6] || '0', 10);
                    const unixMs = new Date(y, mo - 1, d, hh, mm, ss).getTime();
                    try {
                        $ui.persianDatepicker('setDate', unixMs);
                    } catch (e) {
                        const p = new persianDate(unixMs).toCalendar('persian');
                        $ui.val(p.format('YYYY/MM/DD HH:mm'));
                    }
                }
            }
        }

        function initTabs() {
            const buttons = [...document.querySelectorAll('[data-tab-button]')];
            const panels = [...document.querySelectorAll('[data-tab-panel]')];
            if (!buttons.length || !panels.length) return;

            function activate(tabId) {
                panels.forEach(panel => {
                    panel.classList.toggle('hidden', panel.getAttribute('data-tab-panel') !== tabId);
                });
                buttons.forEach(btn => {
                    const isActive = btn.getAttribute('data-tab-button') === tabId;
                    btn.classList.toggle('text-gray-900', isActive);
                    btn.classList.toggle('text-gray-600', !isActive);
                    btn.classList.toggle('border-b-2', isActive);
                    btn.classList.toggle('border-blue-600', isActive);
                });
            }

            buttons.forEach(btn => {
                btn.addEventListener('click', () => activate(btn.getAttribute('data-tab-button')));
            });

            activate('overview');
        }

        function closeAllDropdowns() {
            document.querySelectorAll('[data-dropdown-menu]').forEach(menu => {
                menu.classList.add('hidden');
            });
        }

        function initDropdowns() {
            const toggles = document.querySelectorAll('[data-dropdown-toggle]');
            toggles.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = btn.getAttribute('data-dropdown-toggle');
                    const menu = document.getElementById(id);
                    if (!menu) return;
                    const isOpen = !menu.classList.contains('hidden');
                    closeAllDropdowns();
                    if (!isOpen) menu.classList.remove('hidden');
                });
            });

            document.addEventListener('click', (e) => {
                if (!e.target.closest('[data-dropdown]')) closeAllDropdowns();
            });
        }

        function initTaskStatusFilter() {
            const select = document.getElementById('taskStatusFilter');
            const rows = document.querySelectorAll('[data-task-row]');
            if (!select || !rows.length) return;

            function apply() {
                const val = select.value;
                rows.forEach(row => {
                    const status = row.getAttribute('data-status');
                    const show = !val || status === val;
                    row.classList.toggle('hidden', !show);
                });
            }

            select.addEventListener('change', apply);
            apply();
        }

        $(function () {
            makeLiveFilter({ inputId: 'contactSearchInput', tbodyId: 'contactTableBody', noResId: 'contactNoResults' });
            makeLiveFilter({ inputId: 'organizationSearchInput', tbodyId: 'organizationTableBody', noResId: 'organizationNoResults' });
            initDateTimePicker('#start_at_display');
            initDateTimePicker('#due_at_display');
            initTabs();
            initDropdowns();
            initTaskStatusFilter();
        });

        @if ($hasTaskErrors)
            toggleTask(true);
        @endif
    })();
</script>
@endpush
