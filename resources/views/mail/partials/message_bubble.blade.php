@php
    $isSent = isset($mailbox) && $message->from_email === ($mailbox->email_address ?? null);
    $bubbleClasses = $isSent
        ? 'bg-gradient-to-l from-blue-50 to-blue-100 border-blue-200'
        : 'bg-gradient-to-l from-emerald-50 to-gray-50 border-emerald-100';
    $alignClass = $isSent ? 'ml-auto' : 'mr-auto';
    $badgeText = $isSent ? 'ارسالی' : 'دریافتی';
    $badgeColor = $isSent ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700';
    $bodyText = trim($message->body_text ?? '');
    $bodyHtml = trim(strip_tags($message->body_html ?? ''));
    $textDir = 'auto';
    $refStr = collect($message->references ?? [])->push($message->message_id)->filter()->unique()->implode(' ');
@endphp
<div class="flex {{ $isSent ? 'justify-end' : 'justify-start' }}">
    <div class="w-full md:w-4/5 {{ $alignClass }}">
        <div class="rounded-2xl border shadow-sm {{ $bubbleClasses }} p-4 space-y-3" data-message-id="{{ $message->id }}">
            <div class="flex items-start justify-between gap-2">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-gray-900">{{ $message->from_name ?: $message->from_email ?: 'نامشخص' }}</span>
                        @if($message->from_email)
                            <span class="text-xs text-gray-600">({{ $message->from_email }})</span>
                        @endif
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $badgeColor }}">{{ $badgeText }}</span>
                    </div>
                    <div class="text-[12px] text-gray-500">{{ $message->date ? $message->date->format('Y/m/d H:i') : '' }}</div>
                    <div class="text-base font-bold text-gray-900">{{ $message->subject ?: '(بدون عنوان)' }}</div>
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <button type="button"
                            class="open-reply text-blue-700 hover:text-blue-900 inline-flex items-center gap-1"
                            data-action="reply"
                            data-to="{{ $message->from_email }}"
                            data-subject="{{ $message->subject }}"
                            data-in-reply-to="{{ $message->message_id }}"
                            data-references="{{ $refStr }}">
                        <span class="fa fa-reply"></span>
                        <span>Reply</span>
                    </button>
                    <button type="button"
                            class="open-reply text-blue-700 hover:text-blue-900 inline-flex items-center gap-1"
                            data-action="reply-all"
                            data-to="{{ $message->from_email }}"
                            data-subject="{{ $message->subject }}"
                            data-in-reply-to="{{ $message->message_id }}"
                            data-references="{{ $refStr }}"
                            data-cc="{{ collect($message->to ?? [])->pluck('email')->filter()->implode(',') }}">
                        <span class="fa fa-reply-all"></span>
                        <span>Reply All</span>
                    </button>
                </div>
            </div>

            <div class="rounded-xl bg-white/70 border border-white/80 p-3 shadow-inner" dir="{{ $textDir }}" style="white-space: pre-wrap; unicode-bidi: plaintext;">
                @if($bodyText !== '')
                    <div class="leading-7 text-gray-900">{{ $bodyText }}</div>
                @elseif($bodyHtml !== '')
                    <div class="leading-7 text-gray-900 whitespace-pre-wrap break-words">{!! nl2br(e($bodyHtml)) !!}</div>
                @else
                    <p class="text-gray-500 text-sm">متن ایمیل دریافت نشده است.</p>
                @endif
            </div>

            @if($message->attachments && $message->attachments->isNotEmpty())
                <div class="mt-2">
                    <h3 class="text-sm font-semibold text-gray-800 mb-2">پیوست‌ها</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($message->attachments as $attach)
                            <a href="{{ route('mail.attachments.download', $attach) }}"
                               class="px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-100 inline-flex items-center gap-2 shadow-sm">
                                <span class="fa fa-paperclip text-gray-500"></span>
                                <span>{{ $attach->filename }}</span>
                                @if($attach->size)
                                    <span class="text-[11px] text-gray-500">({{ number_format($attach->size / 1024, 1) }} KB)</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
