@extends('layouts.app')

@section('content')

<div class="max-w-7xl mx-auto px-4 py-8" dir="rtl">
    @php
        $last = $messages->last();
        $referenceString = $last ? collect($last->references ?? [])->push($last->message_id)->filter()->unique()->implode(' ') : '';
        $mailbox = request()->user()?->mailbox;

        // اگر از کنترلر پاس داده نشده باشد، صفر نشان می‌دهد و خطا نمی‌دهد
        $folderCounts = $folderCounts ?? [
            'inbox' => 0,
            'sent' => 0,
            'archive' => 0,
            'trash' => 0,
        ];

        // اگر از صفحه لیست آمدیم، folder را نگه می‌داریم تا هایلایت درست باشد
        $folderParam = request('folder', 'inbox');
    @endphp

    <div class="flex flex-col lg:flex-row-reverse gap-6">
        

        {{-- Main content --}}
        <div class="flex-1 space-y-6">
            <div class="flex items-center justify-between bg-gradient-to-l from-blue-50 via-white to-indigo-50 border border-blue-100 rounded-2xl px-4 py-3 shadow-sm">
                <div class="flex items-center gap-2 text-blue-800">
                    <span class="fa fa-comments text-lg"></span>
                    <div>
                        <div class="text-lg font-bold">موضوع: {{ $last?->subject ?: '(بدون عنوان)' }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('mail.index', ['folder' => $folderParam]) }}" class="text-sm text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                        <span class="fa fa-arrow-right"></span>
                        <span>بازگشت</span>
                    </a>
                    <button type="button"
                       class="open-reply text-sm text-white bg-blue-600 px-4 py-2 rounded-lg hover:bg-blue-700 inline-flex items-center gap-1"
                       data-action="reply"
                       data-to="{{ $last?->from_email }}"
                       data-subject="{{ $last?->subject }}"
                       data-in-reply-to="{{ $last?->message_id }}"
                       data-references="{{ $referenceString }}">
                        <span class="fa fa-reply"></span>
                        <span>پاسخ</span>
                    </button>
                </div>
            </div>

            <div id="thread-messages" class="space-y-4">
                @foreach($messages as $msg)
                    @include('mail.partials.message_bubble', ['message' => $msg, 'mailbox' => $mailbox])
                @endforeach
            </div>

            {{-- Reply Modal --}}
            <div id="reply-modal" class="hidden fixed inset-0 z-40">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
                <div class="absolute inset-0 flex items-center justify-center px-4">
                    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl relative z-10">
                        <div class="flex items-center justify-between px-4 py-3 border-b">
                            <div class="text-lg font-semibold text-gray-800">ارسال پاسخ</div>
                            <button type="button" class="close-modal text-gray-500 hover:text-gray-700">
                                <span class="fa fa-times"></span>
                            </button>
                        </div>
                        <div class="p-4">
                            <div id="reply-error" class="hidden mb-3 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></div>
                            <form id="reply-form" class="space-y-3" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="in_reply_to" id="reply_in_reply_to">
                                <input type="hidden" name="references" id="reply_references">

                                <div>
                                    <label class="text-sm text-gray-700">به</label>
                                    <input type="text" name="to" id="reply_to" class="mt-1 w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>

                                <div>
                                    <label class="text-sm text-gray-700">موضوع</label>
                                    <input type="text" name="subject" id="reply_subject" class="mt-1 w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>

                                <div>
                                    <label class="text-sm text-gray-700">متن</label>
                                    <textarea name="body" id="reply_body" rows="6" class="mt-1 w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"></textarea>
                                </div>

                                <div>
                                    <label class="text-sm text-gray-700">پیوست (اختیاری)</label>
                                    <input type="file" name="attachments[]" multiple class="mt-1 block w-full text-sm text-gray-700 border border-gray-300 rounded cursor-pointer focus:outline-none">
                                </div>

                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" class="close-modal px-4 py-2 rounded border border-gray-200 text-gray-700 hover:bg-gray-50">انصراف</button>
                                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">ارسال</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        {{-- ✅ Sidebar (Folders) - appears on LEFT on large screens because of row-reverse --}}
        <aside class="lg:w-64 space-y-3">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-semibold text-gray-700">پوشه‌ها</span>
                    <a href="{{ route('settings.mailbox.edit') }}" class="text-xs text-blue-600 hover:text-blue-800">تنظیمات</a>
                </div>

                <nav class="space-y-1">
                    <a href="{{ route('mail.index', ['folder' => 'inbox']) }}"
                       class="flex items-center justify-between px-3 py-2 rounded-lg {{ $folderParam === 'inbox' ? 'bg-blue-50 text-blue-700 font-semibold' : 'hover:bg-gray-50 text-gray-700' }}">
                        <span>Inbox</span>
                        <span class="text-[11px] px-2 py-0.5 bg-white rounded-full border border-blue-100 text-blue-600">
                            {{ $folderCounts['inbox'] ?? 0 }}
                        </span>
                    </a>

                    <a href="{{ route('mail.index', ['folder' => 'sent']) }}"
                       class="flex items-center justify-between px-3 py-2 rounded-lg {{ $folderParam === 'sent' ? 'bg-blue-50 text-blue-700 font-semibold' : 'hover:bg-gray-50 text-gray-700' }}">
                        <span>Sent</span>
                        <span class="text-[11px] px-2 py-0.5 bg-white rounded-full border border-blue-100 text-blue-600">
                            {{ $folderCounts['sent'] ?? 0 }}
                        </span>
                    </a>

                    <a href="{{ route('mail.index', ['folder' => 'archive']) }}"
                       class="flex items-center justify-between px-3 py-2 rounded-lg {{ $folderParam === 'archive' ? 'bg-blue-50 text-blue-700 font-semibold' : 'hover:bg-gray-50 text-gray-700' }}">
                        <span>Archive</span>
                        <span class="text-[11px] px-2 py-0.5 bg-white rounded-full border border-blue-100 text-blue-600">
                            {{ $folderCounts['archive'] ?? 0 }}
                        </span>
                    </a>

                    <a href="{{ route('mail.index', ['folder' => 'trash']) }}"
                       class="flex items-center justify-between px-3 py-2 rounded-lg {{ $folderParam === 'trash' ? 'bg-blue-50 text-blue-700 font-semibold' : 'hover:bg-gray-50 text-gray-700' }}">
                        <span>Trash</span>
                        <span class="text-[11px] px-2 py-0.5 bg-white rounded-full border border-blue-100 text-blue-600">
                            {{ $folderCounts['trash'] ?? 0 }}
                        </span>
                    </a>
                </nav>

                <a href="{{ route('settings.mailbox.edit') }}" class="inline-flex items-center gap-1 text-[12px] text-gray-600 hover:text-blue-700 mt-3">
                    <span class="fa fa-cog text-xs"></span>
                    <span>تنظیمات</span>
                </a>

                @if ($mailbox)
                    <div class="mt-3 text-[11px] text-gray-500">
                        آخرین همگام‌سازی: {{ $mailbox->last_sync_at ? $mailbox->last_sync_at->diffForHumans() : '—' }}
                    </div>
                @endif
            </div>
        </aside>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('reply-modal');
    const form = document.getElementById('reply-form');
    const errorBox = document.getElementById('reply-error');
    const toInput = document.getElementById('reply_to');
    const subjectInput = document.getElementById('reply_subject');
    const bodyInput = document.getElementById('reply_body');
    const inReplyToInput = document.getElementById('reply_in_reply_to');
    const referencesInput = document.getElementById('reply_references');
    const threadContainer = document.getElementById('thread-messages');
    const csrf = form.querySelector('input[name="_token"]').value;

    function openModal(data = {}) {
        errorBox.classList.add('hidden');
        errorBox.textContent = '';
        toInput.value = data.to || '';
        subjectInput.value = data.subject || '';
        bodyInput.value = data.body || '';
        inReplyToInput.value = data.in_reply_to || '';
        referencesInput.value = data.references || '';
        modal.classList.remove('hidden');
    }
    function closeModal() {
        modal.classList.add('hidden');
        form.reset();
    }
    function dedupeRePrefix(subject) {
        if (!subject) return '';
        return subject.replace(/^(re:\\s*)+/i, 'Re: ').trim();
    }
    function scrollToBottom() {
        const last = threadContainer.lastElementChild;
        if (last) last.scrollIntoView({behavior: 'smooth', block: 'end'});
    }

    document.querySelectorAll('.open-reply').forEach(btn => {
        btn.addEventListener('click', () => {
            const subject = dedupeRePrefix(`Re: ${btn.dataset.subject || ''}`);
            openModal({
                to: btn.dataset.to || '',
                subject: subject,
                in_reply_to: btn.dataset.inReplyTo || '',
                references: btn.dataset.references || '',
            });
        });
    });

    document.querySelectorAll('.close-modal').forEach(btn => btn.addEventListener('click', closeModal));

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorBox.classList.add('hidden');
        errorBox.textContent = '';

        const formData = new FormData(form);
        formData.append('_token', csrf);

        try {
            const res = await fetch("{{ route('mail.send') }}", {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            });

            const data = await res.json();
            if (!res.ok || !data.ok) {
                const msg = data.message || 'ارسال ناموفق بود.';
                const trace = data.trace_id ? ` (کد پیگیری: ${data.trace_id})` : '';
                errorBox.textContent = msg + trace;
                errorBox.classList.remove('hidden');
                return;
            }

            if (data.html) {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = data.html;
                const bubble = wrapper.firstElementChild;
                if (bubble) {
                    threadContainer.appendChild(bubble);
                    scrollToBottom();
                }
            }

            closeModal();
        } catch (err) {
            errorBox.textContent = 'ارسال ناموفق بود.';
            errorBox.classList.remove('hidden');
        }
    });
});
</script>
@endpush
@endsection
