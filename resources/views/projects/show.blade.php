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
                    <span class="inline-block bg-gray-100 rounded px-2 py-0.5 ml-1 mb-1">
                        {{ $member->name ?? $member->email }}
                    </span>
                @empty
                    <span>—</span>
                @endforelse
            </div>

            
            {{-- فقط مسئول پروژه امکان مدیریت اعضا را ببیند --}}
            @can('manageMembers', $project)
            <div class="mt-4 border-t pt-4">
                <h3 class="font-semibold mb-2">مدیریت دسترسی به پروژه</h3>

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

                {{-- حذف عضو --}}
                <div class="space-y-2">
                    @foreach($project->members as $member)
                        <div class="flex items-center justify-between bg-gray-50 rounded p-2">
                            <div>
                                <span class="font-medium">{{ $member->name ?? $member->email }}</span>
                                @if($member->id === $project->manager_id)
                                    <span class="text-xs text-gray-600">(مسئول پروژه)</span>
                                @endif
                            </div>
                            @if($member->id !== $project->manager_id)
                                <form action="{{ route('projects.members.remove', [$project, $member]) }}" method="POST" onsubmit="return confirm('حذف این کاربر از پروژه؟')">
                                    @csrf @method('DELETE')
                                    <button class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700">حذف</button>
                                </form>
                            @else
                                <span class="text-gray-400 text-sm">—</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endcan

        </div>

        @if($project->description)
            <p class="text-gray-700">{{ $project->description }}</p>
        @endif
    </div>


    {{-- فرم ایجاد تسک --}}
    <div class="bg-white rounded shadow p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">ایجاد تسک جدید</h2>

        @if ($errors->any())
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
                <input type="text" name="title" value="{{ old('title') }}" required
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


            <div class="md:col-span-2">
                <label class="block mb-1 font-medium">توضیحات</label>
                <textarea name="description" rows="3"
                          class="w-full border rounded p-2 focus:outline-none focus:ring"
                          placeholder="توضیح کوتاه درباره کار...">{{ old('description') }}</textarea>
            </div>

            <div class="md:col-span-2">
                <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
                    ذخیره تسک
                </button>
            </div>
        </form>
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
                <th class="py-3 px-4">توضیحات</th>
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

                {{-- توضیحات کوتاه --}}
                <td class="py-3 px-4 text-gray-700">
                    {{ Str::limit($task->description, 120) }}
                </td>

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
@endsection
