@extends('layouts.app')

@section('content')
<div class="container mx-auto">
    @if (session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800">{{ session('success') }}</div>
    @endif

    <div class="mb-4">
        <a href="{{ route('projects.show', $project) }}" class="text-blue-700 hover:underline">← بازگشت به پروژه</a>
    </div>

    <div class="bg-white rounded shadow p-6 mb-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold mb-2">{{ $task->title }}</h1>
                <div class="text-sm text-gray-600 mb-1">
                    اولویت:
                    @if($task->priority==='urgent')
                        <span class="px-2 py-1 rounded text-white bg-red-600 text-xs">اضطراری</span>
                    @else
                        <span class="px-2 py-1 rounded bg-gray-200 text-xs">عادی</span>
                    @endif
                </div>
                <div class="text-sm text-gray-600">وضعیت:
                    @if($task->status==='done')
                        <span class="px-2 py-1 rounded text-white bg-green-600 text-xs">انجام شد</span>
                    @else
                        <span class="px-2 py-1 rounded bg-yellow-100 text-yellow-800 text-xs">در انتظار</span>
                    @endif
                </div>
                <div class="text-sm text-gray-600 mt-1">
                    مسئول: {{ optional($task->assignee)->name ?? optional($task->assignee)->email ?? '—' }}
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('projects.tasks.edit', [$project, $task]) }}"
                   class="px-3 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">ویرایش</a>
                <form action="{{ route('projects.tasks.destroy', [$project, $task]) }}" method="POST"
                      onsubmit="return confirm('حذف این تسک؟')">
                    @csrf @method('DELETE')
                    <button class="px-3 py-2 rounded bg-red-600 text-white hover:bg-red-700">حذف</button>
                </form>
            </div>
        </div>

        @if($task->description)
            <p class="text-gray-700 mt-4">{{ $task->description }}</p>
        @endif
    </div>

    {{-- یادداشت‌ها --}}
    <div class="bg-white rounded shadow p-6">
        <h2 class="text-xl font-semibold mb-4">یادداشت‌ها</h2>

        <form action="{{ route('projects.tasks.notes.store', [$project, $task]) }}" method="POST" class="grid gap-3 md:grid-cols-3 mb-6">
            @csrf
            <div class="md:col-span-2">
                <label class="block mb-1 font-medium">متن یادداشت</label>
                <textarea name="body" rows="3" required
                        class="w-full border rounded p-2 focus:outline-none focus:ring"
                        placeholder="مثلاً: مشکل کابل‌کشی مسیر B برطرف شد.">{{ old('body') }}</textarea>
            </div>

            <div>
                <label class="block mb-2 font-medium">منشن کاربران</label>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm text-gray-600">انتخاب کاربران</span>
                    <button type="button" id="select-all" class="text-blue-600 text-xs hover:underline">
                        منشن همه
                    </button>
                    <button type="button" id="deselect-all" class="text-gray-500 text-xs hover:underline">
                        حذف انتخاب همه
                    </button>
                </div>
                <div id="mentions-list" class="max-h-[150px] overflow-y-auto border rounded p-2 space-y-1">
                    @foreach($users as $u)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="mentions[]" value="{{ $u->id }}"
                                @checked(collect(old('mentions', []))->contains($u->id))
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mention-checkbox">
                            <span>{{ $u->name ?? $u->email }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500 mt-1">به کاربران انتخاب‌شده اعلان ارسال می‌شود.</p>
            </div>

            <div class="md:col-span-3">
                <button class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
                    ثبت یادداشت
                </button>
            </div>
            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif
        </form>

        

        <div class="space-y-4">
            @forelse($task->notes as $note)
                <div id="note-{{ $note->id }}" class="border rounded p-3">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            توسط <span class="font-medium">{{ $note->author->name ?? $note->author->email }}</span>
                            • {{ $note->created_at->diffForHumans() }}
                        </div>
                        @can('delete', $note)
                        <form action="{{ route('projects.tasks.notes.destroy', [$project, $task, $note]) }}" method="POST"
                              onsubmit="return confirm('حذف این یادداشت؟')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:underline text-sm">حذف</button>
                        </form>
                        @endcan
                    </div>

                    <div class="mt-2 text-gray-800 whitespace-pre-line">{{ $note->body }}</div>
                    @php
                        // اگر رابطه لود نشده بود، یک‌بار از DB بگیر
                        $mentions = $note->relationLoaded('mentions')
                            ? $note->mentions
                            : $note->mentions()->select('users.id','users.name','users.email')->get();
                    @endphp

                    @if($mentions->isNotEmpty())
                        <div class="mt-2 text-[12px] text-gray-500">
                            <span class="font-medium">کاربران منشن‌شده:</span>
                            @foreach($mentions as $m)
                                <span class="inline-block bg-gray-100 rounded px-2 py-0.5 ml-1 mb-1">
                                    {{ $m->name ?? $m->email }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                    
                </div>
            @empty
                <div class="text-gray-500">هنوز یادداشتی ثبت نشده است.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
    document.getElementById('select-all').addEventListener('click', () => {
        document.querySelectorAll('.mention-checkbox').forEach(cb => cb.checked = true);
    });

    document.getElementById('deselect-all').addEventListener('click', () => {
        document.querySelectorAll('.mention-checkbox').forEach(cb => cb.checked = false);
    });
</script>
@endpush