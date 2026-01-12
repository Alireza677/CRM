@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8" dir="rtl">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-bold text-gray-800">صندوق ایمیل</h1>
        @php
            $needsMailboxSetup = !$mailbox || !$mailbox->is_active || empty($mailbox->smtp_host) || empty($mailbox->username) || empty($mailbox->password);
        @endphp
        <div class="flex items-center gap-2">
            <form action="{{ route('mail.index') }}" method="GET" class="hidden">
                <input type="hidden" name="folder" value="{{ $selectedFolder }}">
            </form>
            <a href="{{ route('settings.mailbox.edit') }}"
               class="relative inline-flex items-center justify-center w-10 h-10 rounded-full border border-gray-200 text-gray-600 hover:text-blue-700 hover:border-blue-200 bg-white transition"
               title="{{ $needsMailboxSetup ? 'تنظیمات ایمیل کامل نیست / همگام‌سازی غیرفعال است' : 'تنظیمات صندوق ایمیل' }}">
                <span class="fa fa-cog text-base"></span>
                @if($needsMailboxSetup)
                    <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-amber-500 shadow-sm" title="تنظیمات ایمیل کامل نیست / همگام‌سازی غیرفعال است"></span>
                @endif
            </a>
            <a href="{{ route('mail.compose') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <span class="fa fa-pen"></span>
                <span>ایجاد ایمیل جدید</span>
            </a>
            <a href="https://akhgartabesh.com/webmail"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <span class="fa fa-pen"></span>
                <span>ایمیل سازمانی</span>
            </a>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
        <aside class="lg:w-64 space-y-3">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-semibold text-gray-700">پوشه‌ها</span>
                    <a href="{{ route('settings.mailbox.edit') }}" class="text-xs text-blue-600 hover:text-blue-800">تنظیمات</a>
                </div>
                @php $folderParam = $selectedFolder; @endphp
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
                    <div class="mt-3 text-[11px] text-gray-500">آخرین همگام‌سازی: {{ $mailbox->last_sync_at ? $mailbox->last_sync_at->diffForHumans() : '—' }}</div>
                @endif
            </div>
        </aside>

        <div class="flex-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-3">
                    <form action="{{ route('mail.index') }}" method="GET" class="flex-1 flex items-center gap-2">
                        <input type="hidden" name="folder" value="{{ $selectedFolder }}">
                        <input type="text" name="q" value="{{ $search }}" placeholder="جستجو در موضوع/فرستنده/متن"
                               class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <button class="px-3 py-2 bg-gray-100 rounded text-sm">جستجو</button>
                    </form>
                        <div class="text-xs text-gray-500">
                            {{ method_exists($threads, 'total') ? $threads->total() : $threads->count() }} گفتگو
                        </div>
                </div>

                <form action="{{ route('mail.bulk') }}" method="POST">
                    @csrf
                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-100 flex flex-wrap items-center gap-2 text-sm">
                        <select name="action" class="border-gray-300 rounded">
                            <option value="mark_read">علامت خوانده</option>
                            <option value="mark_unread">علامت خوانده‌نشده</option>
                            <option value="star">ستاره‌دار</option>
                            <option value="unstar">حذف ستاره</option>
                            <option value="archive">آرشیو</option>
                            <option value="unarchive">بازگردانی از آرشیو</option>
                            <option value="delete">حذف</option>
                        </select>
                        <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">اعمال</button>
                    </div>

                    @if($threads && $threads->count())
                        <div class="divide-y divide-gray-100">
                            @foreach ($threads as $thread)
                                @php
                                    $last = $thread->last_message;
                                    $isUnread = ($thread->unread_count ?? 0) > 0;
                                @endphp
                                <label class="block px-4 py-3 hover:bg-blue-50 transition cursor-pointer {{ $isUnread ? 'bg-blue-50/50' : 'bg-white' }}">
                                    <div class="flex items-start gap-3">
                                        <input type="checkbox" name="threads[]" value="{{ $thread->thread_key }}" class="mt-2">
                                        <a href="{{ route('mail.thread', $thread->thread_key) }}" class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full {{ $isUnread ? 'bg-blue-500' : 'bg-gray-300' }}"></span>
                                                    <div class="text-sm {{ $isUnread ? 'font-semibold text-gray-900' : 'text-gray-800' }}">
                                                        {{ $last?->from_name ?: $last?->from_email ?: 'نامشخص' }}
                                                    </div>
                                                    @if($last?->is_starred)
                                                        <span class="text-yellow-500 fa fa-star text-xs"></span>
                                                    @endif
                                                </div>
                                                <div class="text-xs text-gray-500 whitespace-nowrap">{{ $last?->date ? $last->date->format('Y/m/d H:i') : '—' }}</div>
                                            </div>
                                            <div class="flex items-center justify-between gap-3 mt-1">
                                                <div class="text-sm {{ $isUnread ? 'font-semibold text-gray-900' : 'text-gray-800' }}">
                                                    {{ $last?->subject ?: '(بدون عنوان)' }}
                                                    <span class="text-xs text-gray-500">• {{ $thread->messages_count }} پیام</span>
                                                </div>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1 overflow-hidden text-ellipsis">
                                                {{ $last?->snippet ?? '...' }}
                                            </div>
                                        </a>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        <div class="px-4 py-3">
                            {{ $threads->withQueryString()->onEachSide(1)->links() }}
                        </div>
                    @else
                        <div class="p-8 text-center text-gray-500">ایمیلی برای نمایش وجود ندارد.</div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
