@extends('layouts.app')

@section('content')
<div class="container mx-auto">
    @if (session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6">
        <a href="{{ route('projects.index') }}" class="text-blue-700 hover:underline">← بازگشت به پروژه‌ها</a>
    </div>

    <div class="bg-white rounded shadow p-6 mb-8">
        <h1 class="text-2xl font-bold mb-2">{{ $project->name }}</h1>

        <div class="grid md:grid-cols-2 gap-3 text-sm text-gray-700 mb-3">
            <div>
                <span class="font-semibold">مسئول پروژه:</span>
                <span>{{ $project->manager?->name ?? $project->manager?->email ?? '—' }}</span>
            </div>
            <div>
                <span class="font-semibold">اعضای پروژه:</span>
                @forelse($project->members as $member)
                    <span class="inline-flex items-center bg-gray-100 rounded px-2 py-0.5 ml-1 mb-1">
                        {{ $member->name ?? $member->email }}
                        @can('manageMembers', $project)
                            @if($member->id !== $project->manager_id)
                                <form action="{{ route('projects.members.remove', [$project, $member]) }}" method="POST"
                                      onsubmit="return confirm('حذف این کاربر از پروژه؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ml-1 text-red-600 hover:text-red-700 font-bold">×</button>
                                </form>
                            @endif
                        @endcan
                    </span>
                @empty
                    <span>—</span>
                @endforelse
            </div>
            {{-- فقط مسئول پروژه امکان مدیریت اعضا را ببیند --}}
            @can('manageMembers', $project)
            <div class="mt-4 border-t pt-4">
                {{-- افزودن عضو --}}
                <form action="{{ route('projects.members.add', $project) }}" method="POST" class="flex items-center gap-2 mb-3">
                    @csrf
                    <select name="user_id" class="border rounded p-2">
                        <option value="">-- افزودن کاربر جدید به پروژه --</option>
                        @foreach($nonMembers as $u)
                            <option value="{{ $u->id }}">{{ $u->name ?? $u->email }}</option>
                        @endforeach
                    </select>
                    <button class="px-3 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">افزودن</button>
                </form>
            </div>
            @endcan

            

        </div>

        @if($project->description)
            <p class="text-gray-700">{{ $project->description }}</p>
        @endif
    </div>


    {{-- ایجاد تسک در مودال --}}
    <div class="bg-white rounded shadow p-6 mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold">ایجاد تسک جدید</h2>
            <p class="text-sm text-gray-600 mt-1">برای ثبت تسک جدید روی دکمه زیر کلیک کنید.</p>
        </div>
        <button type="button" id="openTaskModal" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
            ایجاد تسک
        </button>
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
        $taskErrorFields = [
            'title','priority','assigned_to','description',
            'start_at','due_at','related_type','related_id'
        ];
        $hasTaskErrors = $errors->hasAny($taskErrorFields);
    @endphp

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
                    <select name="assigned_to" class="w-full border rounded p-2 focus:outline-none focus:ring">
                        <option value="">-- انتخاب کاربر --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ (string)old('assigned_to')===(string)$user->id ? 'selected' : '' }}>
                                {{ $user->name ?? $user->email }}
                            </option>
                        @endforeach
                    </select>
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

    {{-- فیلتر اولویت --}}
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-xl font-semibold">تسک‌ها</h2>
        <form method="GET" action="{{ route('projects.show', $project) }}" class="flex items-center gap-2">
            <label class="text-sm text-gray-700">فیلتر اولویت:</label>
            <select name="priority" class="border rounded p-1" onchange="this.form.submit()">
                <option value=""      {{ $priority===null ? 'selected' : '' }}>همه</option>
                <option value="urgent" {{ $priority==='urgent' ? 'selected' : '' }}>اضطراری</option>
                <option value="normal" {{ $priority==='normal' ? 'selected' : '' }}>عادی</option>
            </select>
        </form>
    </div>

    @php use Illuminate\Support\Str; @endphp

{{-- لیست تسک‌ها --}}
<div class="bg-white rounded shadow">
    <table class="min-w-full text-right">
        <thead class="border-b">
            <tr class="text-gray-600">
                <th class="py-3 px-4">#</th>
                <th class="py-3 px-4">عنوان</th>
                <th class="py-3 px-4">اولویت</th>
                <th class="py-3 px-4">وضعیت</th>
                <th class="py-3 px-4">اقدامات</th>
                <th class="py-3 px-4">مسئول</th>
            </tr>
        </thead>
        <tbody>
        @forelse($tasks as $task)
            <tr class="border-b hover:bg-gray-50">
                <td class="py-3 px-4">{{ $task->id }}</td>

                {{-- عنوان + لینک + شمارنده یادداشت‌ها --}}
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

                {{-- اولویت --}}
                <td class="py-3 px-4">
                    @if($task->priority === 'urgent')
                        <span class="px-2 py-1 rounded text-white bg-red-600 text-xs">اضطراری</span>
                    @else
                        <span class="px-2 py-1 rounded bg-gray-200 text-xs">عادی</span>
                    @endif
                </td>

                {{-- وضعیت --}}
                <td class="py-3 px-4">
                    @if($task->status === 'done')
                        <span class="px-2 py-1 rounded text-white bg-green-600 text-xs">انجام شد</span>
                    @else
                        <span class="px-2 py-1 rounded bg-yellow-100 text-yellow-800 text-xs">در انتظار</span>
                    @endif
                </td>

                <!-- {{-- توضیحات کوتاه --}}
                <td class="py-3 px-4 text-gray-700">
                    {{ Str::limit($task->description, 120) }}
                </td> -->

                {{-- اقدامات --}}
                <td class="py-3 px-4">
                    <div class="flex flex-wrap items-center gap-2">
                        {{-- مشاهده --}}
                        <a href="{{ route('projects.tasks.show', [$project, $task]) }}"
                           class="px-3 py-1 rounded bg-gray-100 text-gray-800 hover:bg-gray-200 text-sm">مشاهده</a>

                        {{-- ویرایش --}}
                        <a href="{{ route('projects.tasks.edit', [$project, $task]) }}"
                           class="px-3 py-1 rounded bg-blue-600 text-white hover:bg-blue-700 text-sm">ویرایش</a>

                        {{-- تایید انجام --}}
                        @if($task->status !== 'done')
                            <form action="{{ route('projects.tasks.done', [$project, $task]) }}" method="POST"
                                  onsubmit="return confirm('تسک انجام شد؟');">
                                @csrf
                                <button type="submit"
                                        class="px-3 py-1 rounded bg-green-600 text-white hover:bg-green-700 text-sm">
                                    تایید انجام ✅
                                </button>
                            </form>
                        @endif

                        {{-- حذف --}}
                        <form action="{{ route('projects.tasks.destroy', [$project, $task]) }}" method="POST"
                              onsubmit="return confirm('حذف این تسک؟');">
                            @csrf @method('DELETE')
                            <button class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700 text-sm">
                                حذف
                            </button>
                        </form>
                    </div>
                </td>

                {{-- مسئول --}}
                <td class="py-3 px-4 text-gray-700">
                    {{ optional($task->assignee)->name ?? optional($task->assignee)->email ?? '—' }}
                </td>
            </tr>
        @empty
            <tr>
                <td class="py-6 px-4 text-gray-500" colspan="7">هنوز تسکی ثبت نشده است.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>


    <div class="mt-4">
        {{ $tasks->links() }}
    </div>
</div>

@include('activities.modals')
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
            if (e.key === 'Escape') toggleTask(false);
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

        $(function () {
            makeLiveFilter({ inputId: 'contactSearchInput', tbodyId: 'contactTableBody', noResId: 'contactNoResults' });
            makeLiveFilter({ inputId: 'organizationSearchInput', tbodyId: 'organizationTableBody', noResId: 'organizationNoResults' });
            initDateTimePicker('#start_at_display');
            initDateTimePicker('#due_at_display');
        });

        @if ($hasTaskErrors)
            toggleTask(true);
        @endif
    })();
</script>
@endpush
