@php
    use Illuminate\Support\Str;

    $isSent = isset($mailbox) && $message->from_email === ($mailbox->email_address ?? null);
    $bubbleClasses = $isSent
        ? 'bg-gradient-to-l from-blue-50 to-blue-100 border-blue-200'
        : 'bg-gradient-to-l from-emerald-50 to-gray-50 border-emerald-100';
    $alignClass = $isSent ? 'ml-auto' : 'mr-auto';
    $badgeText = $isSent ? 'ارسال‌شده' : 'دریافتی';
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
                        <span class="text-sm font-semibold text-gray-900">{{ $message->from_name ?: $message->from_email ?: 'کاربر ناشناس' }}</span>
                        @if($message->from_email)
                            <span class="text-xs text-gray-600">({{ $message->from_email }})</span>
                        @endif
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $badgeColor }}">{{ $badgeText }}</span>
                    </div>
                    <div class="text-[12px] text-gray-500">{{ $message->date ? $message->date->format('Y/m/d H:i') : '' }}</div>
                    <div class="text-base font-bold text-gray-900">{{ $message->subject ?: '(بدون موضوع)' }}</div>
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
                    <p class="text-gray-500 text-sm">متن پیام در دسترس نیست.</p>
                @endif
            </div>

            @if($message->attachments && $message->attachments->isNotEmpty())
                @php
                    $formatSize = function ($bytes) {
                        if (!$bytes) return '';
                        if ($bytes >= 1048576) {
                            return number_format($bytes / 1048576, 1).' MB';
                        }
                        return number_format($bytes / 1024, 1).' KB';
                    };
                @endphp
                <div class="mt-2">
                    <h3 class="text-sm font-semibold text-gray-800 mb-2">پیوست‌ها</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                        @foreach($message->attachments as $attach)
                            @php
                                $isImage = Str::startsWith(strtolower((string) $attach->mime), 'image/');
                                $isPdf = strtolower((string) $attach->mime) === 'application/pdf';
                                $previewUrl = route('mail.attachments.preview', ['message' => $message, 'attachment' => $attach]);
                                $downloadUrl = route('mail.attachments.download', $attach);
                            @endphp
                            <div class="group relative overflow-hidden rounded-xl border bg-white shadow-sm hover:shadow-md transition">
                                <div class="aspect-[4/3] bg-gray-50 flex items-center justify-center">
                                    @if($isImage)
                                        <img src="{{ $previewUrl }}"
                                             loading="lazy"
                                             alt="{{ $attach->filename }}"
                                             class="w-full h-full object-cover cursor-pointer image-preview-trigger"
                                             data-full="{{ $previewUrl }}"
                                             data-filename="{{ $attach->filename }}">
                                    @else
                                        <div class="flex flex-col items-center justify-center text-gray-500 gap-2 p-4">
                                            <span class="fa {{ $isPdf ? 'fa-file-pdf-o' : 'fa-file-o' }} text-3xl"></span>
                                            <span class="text-xs text-center px-2">
                                                {{ $attach->filename }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                                <div class="p-3 space-y-1">
                                    <div class="text-sm font-semibold text-gray-800 truncate" title="{{ $attach->filename }}">{{ $attach->filename }}</div>
                                    <div class="text-xs text-gray-500">{{ $formatSize($attach->size) }}</div>
                                    <div class="flex items-center gap-2 text-xs">
                                        @if($isImage)
                                            <button type="button"
                                                    class="text-blue-600 hover:text-blue-800 image-preview-trigger inline-flex items-center gap-1"
                                                    data-full="{{ $previewUrl }}"
                                                    data-filename="{{ $attach->filename }}">
                                                <span class="fa fa-eye"></span>
                                                <span>نمایش</span>
                                            </button>
                                        @elseif($isPdf)
                                            <a href="{{ $previewUrl }}" target="_blank" class="text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                                <span class="fa fa-eye"></span>
                                                <span>باز کردن</span>
                                            </a>
                                        @else
                                            <a href="{{ $downloadUrl }}" class="text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                                <span class="fa fa-download"></span>
                                                <span>دانلود</span>
                                            </a>
                                        @endif
                                        @unless($isImage)
                                            <a href="{{ $downloadUrl }}" class="text-gray-500 hover:text-gray-700 inline-flex items-center gap-1 ml-auto">
                                                <span class="fa fa-paperclip"></span>
                                                <span>ذخیره</span>
                                            </a>
                                        @endunless
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@pushOnce('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    function ensureModal() {
        let modal = document.getElementById('image-lightbox');
        if (modal) return modal;
        modal = document.createElement('div');
        modal.id = 'image-lightbox';
        modal.className = 'fixed inset-0 z-50 hidden';
        modal.innerHTML = `
            <div class="absolute inset-0 bg-black/70"></div>
            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full relative">
                    <button type="button" class="close-lightbox absolute top-2 left-2 text-white bg-black/60 rounded-full w-8 h-8 flex items-center justify-center hover:bg-black/80">&times;</button>
                    <div class="p-4">
                        <img src="" alt="" class="w-full h-auto max-h-[80vh] object-contain" id="lightbox-img">
                        <div class="mt-2 text-sm text-gray-700" id="lightbox-caption"></div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal || e.target.classList.contains('close-lightbox')) {
                modal.classList.add('hidden');
            }
        });
        return modal;
    }

    document.body.addEventListener('click', (e) => {
        const trigger = e.target.closest('.image-preview-trigger');
        if (!trigger) return;
        e.preventDefault();
        const src = trigger.dataset.full;
        const name = trigger.dataset.filename || '';
        const modal = ensureModal();
        modal.querySelector('#lightbox-img').src = src;
        modal.querySelector('#lightbox-caption').textContent = name;
        modal.classList.remove('hidden');
    });
});
</script>
@endPushOnce
