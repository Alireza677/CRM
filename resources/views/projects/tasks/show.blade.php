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

        <form action="{{ route('projects.tasks.notes.store', [$project, $task]) }}" method="POST" class="grid gap-3 md:grid-cols-3 mb-6" enctype="multipart/form-data">
            @csrf
            <div class="md:col-span-2">
                <label class="block mb-1 font-medium">متن یادداشت</label>
                <div class="relative">
                    <textarea id="note-body" name="body" rows="3" required
                            class="w-full border rounded p-2 focus:outline-none focus:ring"
                            placeholder="برای منشن از علامت @ در متن یادداشت استفاده کنید.">{{ old('body') }}</textarea>
                    <div id="mention-suggestions"
                         class="hidden absolute z-10 mt-1 w-full max-h-48 overflow-y-auto rounded border bg-white shadow">
                    </div>
                </div>
                <div id="mention-hidden"></div>
            </div>

            <div>
                <label class="block mb-2 font-medium">پیوست‌ها</label>
                <input type="file" name="attachments[]" multiple
                       class="block w-full text-sm text-gray-700 border border-gray-300 rounded cursor-pointer focus:outline-none">
                <p class="text-xs text-gray-500 mt-1">می‌توانید چند فایل یا عکس پیوست کنید.</p>
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
                <div id="note-{{ $note->id }}" class="border rounded p-3 bg-gray-50">
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
                    @if($note->attachments && $note->attachments->isNotEmpty())
                        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($note->attachments as $att)
                                @php
                                    $ext = strtolower(pathinfo($att->file_path, PATHINFO_EXTENSION));
                                    $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                                    $url = asset('storage/' . $att->file_path);
                                @endphp
                                <div class="border rounded p-2 flex items-center gap-3">
                                    @if($isImage)
                                        <button type="button" class="js-lightbox" data-src="{{ $url }}" data-alt="{{ $att->file_name }}">
                                            <img src="{{ $url }}" alt="{{ $att->file_name }}" class="h-14 w-14 object-cover rounded border">
                                        </button>
                                    @else
                                        <div class="h-12 w-12 flex items-center justify-center bg-gray-100 rounded border text-xs text-gray-600">
                                            {{ strtoupper($ext) ?: 'FILE' }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <span class="text-sm text-gray-800 block truncate">
                                            {{ $att->file_name }}
                                        </span>
                                        @if($att->file_size)
                                            <div class="text-xs text-gray-500">{{ number_format($att->file_size / 1024, 1) }} KB</div>
                                        @endif
                                        <a href="{{ $url }}" download
                                           class="inline-block mt-1 text-xs text-blue-700 hover:underline">
                                            دانلود
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
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
    @php
        $mentionUsers = $users->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ];
        })->values();
    @endphp
    const mentionUsers = @json($mentionUsers);
    const textarea = document.getElementById('note-body');
    const suggestions = document.getElementById('mention-suggestions');
    const mentionHidden = document.getElementById('mention-hidden');
    let selectedMentions = [];

    const renderSuggestions = (items) => {
        if (!items.length) {
            suggestions.classList.add('hidden');
            suggestions.innerHTML = '';
            return;
        }

        suggestions.innerHTML = '';
        items.forEach(u => {
            const label = u.name || u.email;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'block w-full text-right px-3 py-2 text-sm hover:bg-gray-100';
            btn.dataset.id = u.id;
            btn.dataset.label = label;
            btn.textContent = '@' + label;
            suggestions.appendChild(btn);
        });
        suggestions.classList.remove('hidden');
    };

    const getQueryInfo = () => {
        const pos = textarea.selectionStart || 0;
        const text = textarea.value.slice(0, pos);
        const atIndex = text.lastIndexOf('@');
        if (atIndex === -1) return null;
        const before = text.slice(atIndex - 1, atIndex);
        if (before && !/\s/.test(before)) return null;
        const query = text.slice(atIndex + 1);
        if (/\s/.test(query)) return null;
        return { atIndex, query, pos };
    };

    const updateSuggestions = () => {
        const info = getQueryInfo();
        if (!info) {
            suggestions.classList.add('hidden');
            return;
        }
        const q = info.query.trim().toLowerCase();
        const items = mentionUsers.filter(u => {
            const hay = ((u.name || '') + ' ' + (u.email || '')).toLowerCase();
            return hay.includes(q);
        }).slice(0, 8);
        renderSuggestions(items);
    };

    const insertMention = (label, id) => {
        const info = getQueryInfo();
        if (!info) return;
        const before = textarea.value.slice(0, info.atIndex);
        const after = textarea.value.slice(info.pos);
        const mentionText = '@' + label + ' ';
        textarea.value = before + mentionText + after;
        const newPos = (before + mentionText).length;
        textarea.setSelectionRange(newPos, newPos);
        textarea.focus();
        suggestions.classList.add('hidden');

        if (id && !selectedMentions.some(m => m.id === id)) {
            selectedMentions.push({ id, label });
            syncMentionInputs();
        }
    };

    textarea.addEventListener('input', updateSuggestions);
    textarea.addEventListener('click', updateSuggestions);
    textarea.addEventListener('keyup', updateSuggestions);
    textarea.addEventListener('blur', () => {
        setTimeout(() => suggestions.classList.add('hidden'), 150);
    });

    suggestions.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-id]');
        if (!btn) return;
        const userId = parseInt(btn.getAttribute('data-id'), 10);
        const label = btn.getAttribute('data-label');
        insertMention(label, userId);
    });

    const syncMentionInputs = () => {
        if (!mentionHidden) return;
        const body = textarea.value || '';
        selectedMentions = selectedMentions.filter(m => body.includes('@' + m.label));
        mentionHidden.innerHTML = '';
        selectedMentions.forEach(m => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'mentions[]';
            input.value = m.id;
            mentionHidden.appendChild(input);
        });
    };

    textarea.addEventListener('input', syncMentionInputs);

    const lightbox = document.createElement('div');
    lightbox.id = 'lightbox';
    lightbox.className = 'fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4';
    lightbox.innerHTML = `
        <div class="w-full h-full flex items-center justify-center">
            <div class="relative inline-block">
                <button type="button" id="lightbox-close" class="absolute -top-3 -left-3 bg-red-600 text-white rounded-full w-8 h-8 shadow">×</button>
                <img id="lightbox-img" src="" alt="" class="max-h-[85vh] max-w-[90vw] rounded shadow bg-white">
            </div>
        </div>
    `;
    document.body.appendChild(lightbox);

    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxClose = document.getElementById('lightbox-close');
    const closeLightbox = () => {
        lightbox.classList.add('hidden');
        lightboxImg.src = '';
        lightboxImg.alt = '';
    };

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.js-lightbox');
        if (!btn) return;
        const src = btn.getAttribute('data-src');
        const alt = btn.getAttribute('data-alt') || '';
        lightboxImg.src = src;
        lightboxImg.alt = alt;
        lightbox.classList.remove('hidden');
    });

    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) closeLightbox();
    });
    lightboxClose.addEventListener('click', closeLightbox);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeLightbox();
    });
</script>
@endpush
