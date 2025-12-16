@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8" dir="rtl">
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('mail.index') }}" class="text-sm text-blue-600 hover:text-blue-800">بازگشت به صندوق ورودی</a>
        <span class="text-xs text-gray-500">ایمیل: {{ $mailbox->email_address }}</span>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 space-y-3">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs text-gray-500 mb-1">{{ $message->date ? $message->date->format('Y/m/d H:i') : 'تاریخ نامشخص' }}</p>
                    <h1 class="text-xl font-bold text-gray-900">{{ $message->subject ?: '(بدون عنوان)' }}</h1>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('mail.compose', ['to' => $message->from_email, 'subject' => 'Re: '.$message->subject, 'body' => $message->snippet, 'in_reply_to' => $message->message_id]) }}"
                       class="px-3 py-1 text-xs rounded bg-blue-50 text-blue-700 hover:bg-blue-100">Reply</a>
                    <a href="{{ route('mail.compose', ['to' => $message->from_email, 'cc' => collect($message->to)->pluck('email')->implode(','), 'subject' => 'Re: '.$message->subject, 'body' => $message->snippet, 'in_reply_to' => $message->message_id]) }}"
                       class="px-3 py-1 text-xs rounded bg-blue-50 text-blue-700 hover:bg-blue-100">Reply All</a>
                    <a href="{{ route('mail.compose', ['subject' => 'Fwd: '.$message->subject, 'body' => $message->body_text ?? $message->snippet]) }}"
                       class="px-3 py-1 text-xs rounded bg-blue-50 text-blue-700 hover:bg-blue-100">Forward</a>
                </div>
            </div>
            <div class="text-sm text-gray-700 space-y-1">
                <div>
                    <span class="font-semibold">از:</span>
                    <span>{{ $message->from_name ?: $message->from_email ?: 'نامشخص' }}</span>
                    @if($message->from_email)
                        <span class="text-gray-500">({{ $message->from_email }})</span>
                    @endif
                </div>
                @if($message->to)
                    <div class="flex flex-wrap gap-1 text-xs text-gray-600">
                        <span class="font-semibold text-sm text-gray-700">به:</span>
                        @foreach($message->to as $addr)
                            <span class="px-2 py-0.5 bg-gray-100 rounded-full">{{ $addr['name'] ?? $addr['email'] ?? '' }}</span>
                        @endforeach
                    </div>
                @endif
                @if($message->cc)
                    <div class="flex flex-wrap gap-1 text-xs text-gray-600">
                        <span class="font-semibold text-sm text-gray-700">Cc:</span>
                        @foreach($message->cc as $addr)
                            <span class="px-2 py-0.5 bg-gray-100 rounded-full">{{ $addr['name'] ?? $addr['email'] ?? '' }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="px-6 py-5">
            @if($message->body_text)
                <pre class="whitespace-pre-wrap text-gray-800 leading-7">{{ $message->body_text }}</pre>
            @elseif($message->body_html)
                <div class="leading-7 text-gray-800 whitespace-pre-wrap break-words">
                    {!! nl2br(e(strip_tags($message->body_html))) !!}
                </div>
            @else
                <p class="text-gray-500 text-sm">متن ایمیل هنوز دریافت نشده است.</p>
            @endif

            @if($message->attachments && $message->attachments->isNotEmpty())
                <div class="mt-6">
                    <h3 class="text-sm font-semibold text-gray-800 mb-2">پیوست‌ها</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($message->attachments as $attach)
                            <a href="{{ route('mail.attachments.download', $attach) }}"
                               class="px-3 py-2 bg-gray-100 rounded text-sm text-gray-700 hover:bg-gray-200">
                                {{ $attach->filename }}
                                @if($attach->size)
                                    ({{ number_format($attach->size / 1024, 1) }} KB)
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
