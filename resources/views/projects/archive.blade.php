@extends('layouts.app')

@section('content')
<div class="container mx-auto">
    @if (session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">بایگانی پروژه‌ها</h1>
        <div class="flex items-center gap-2">
            <a href="{{ route('projects.index') }}" class="px-4 py-2 rounded bg-gray-200">
                پروژه‌های جاری
            </a>
            <button
                id="bulk-delete-button"
                type="submit"
                form="bulk-delete-form"
                class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                disabled
                onclick="return confirm('پروژه‌های انتخاب‌شده حذف شوند؟');"
            >
                حذف گروهی
            </button>
            <a href="{{ route('projects.create') }}" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
                + ایجاد پروژه
            </a>
        </div>
    </div>

    <form id="bulk-delete-form" method="POST" action="{{ route('projects.bulkDestroy') }}">
        @csrf
        @method('DELETE')
        <div class="bg-white rounded shadow">
            <table class="min-w-full text-right">
                <thead class="border-b">
                    <tr class="text-gray-600">
                        <th class="py-3 px-4">
                            <input id="select-all-projects" type="checkbox" class="rounded" />
                        </th>
                        <th class="py-3 px-4">#</th>
                        <th class="py-3 px-4">نام</th>
                        <th class="py-3 px-4">مسئول پروژه</th>
                        <th class="py-3 px-4">تاریخ ایجاد</th>
                        <th class="py-3 px-4">موعد مقرر</th>
                        <th class="py-3 px-4">اعضا</th>
                        <th class="py-3 px-4">پیشرفت تسک ها</th>
                        <th class="py-3 px-4">اقدامات</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($projects as $project)
                    @php
                        $tasksCount = (int) ($project->tasks_count ?? 0);
                        $doneCount = (int) ($project->tasks_done_count ?? 0);
                        $progress = $tasksCount > 0 ? round(($doneCount / $tasksCount) * 100) : 0;
                    @endphp
                    <tr class="border-b">
                        <td class="py-3 px-4">
                            <input
                                type="checkbox"
                                name="ids[]"
                                value="{{ $project->id }}"
                                class="project-checkbox rounded"
                            />
                        </td>
                        <td class="py-3 px-4">{{ $project->id }}</td>
                        <td class="py-3 px-4 font-semibold">{{ $project->name }}</td>
                        <td class="py-3 px-4 text-gray-700">
                            {{ $project->manager->name ?? '-' }}
                        </td>
                        <td class="py-3 px-4 text-gray-700">
                            {{ \Morilog\Jalali\Jalalian::fromDateTime($project->created_at)->format('Y/m/d') }}
                        </td>
                        <td class="py-3 px-4 text-gray-700">
                            {{ $project->due_date ? \Morilog\Jalali\Jalalian::fromDateTime($project->due_date)->format('Y/m/d') : '—' }}
                        </td>
                        <td class="py-3 px-4 text-gray-700">{{ $project->members_count ?? 0 }}</td>
                        <td class="py-3 px-4 text-gray-700">
                            {{ $progress }}٪
                            <span class="text-xs text-gray-500">({{ $doneCount }}/{{ $tasksCount }})</span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('projects.show', $project) }}" class="text-blue-700 hover:underline">
                                    نمایش
                                </a>
                                <button
                                    type="submit"
                                    form="project-delete-{{ $project->id }}"
                                    class="text-red-700 hover:underline"
                                    onclick="return confirm('این پروژه حذف شود؟');"
                                >
                                    حذف
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td class="py-6 px-4 text-gray-500" colspan="8">هیچ پروژه‌ای ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </form>

    @foreach($projects as $project)
        <form id="project-delete-{{ $project->id }}" method="POST" action="{{ route('projects.destroy', $project) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endforeach

    <div class="mt-4">
        {{ $projects->links() }}
    </div>
</div>

<script>
    (() => {
        const selectAll = document.getElementById('select-all-projects');
        const bulkButton = document.getElementById('bulk-delete-button');
        if (!selectAll) return;
        const checkboxes = Array.from(document.querySelectorAll('.project-checkbox'));
        const updateBulkState = () => {
            const anyChecked = checkboxes.some((cb) => cb.checked);
            const allChecked = checkboxes.length > 0 && checkboxes.every((cb) => cb.checked);
            if (bulkButton) {
                bulkButton.disabled = !anyChecked;
            }
            selectAll.indeterminate = anyChecked && !allChecked;
            selectAll.checked = allChecked;
        };
        selectAll.addEventListener('change', () => {
            checkboxes.forEach((cb) => {
                cb.checked = selectAll.checked;
            });
            updateBulkState();
        });
        checkboxes.forEach((cb) => {
            cb.addEventListener('change', updateBulkState);
        });
        updateBulkState();
    })();
</script>
@endsection
