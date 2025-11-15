@extends('layouts.app')

@section('title', 'جزئیات تعطیلی')

@section('content')
  @php
    $breadcrumb = [
      ['title' => 'مدیریت تعطیلات', 'url' => route('holidays.index')],
      ['title' => 'جزئیات تعطیلی']
    ];
  @endphp
  <div class="max-w-4xl mx-auto px-4 py-6" dir="rtl">
    <h1 class="text-2xl font-semibold mb-6">جزئیات تعطیلی</h1>

    <div class="bg-white rounded-md shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
          <div class="text-gray-500">از تاریخ</div>
          <div class="font-medium">{{ $holiday->date ? \Morilog\Jalali\Jalalian::fromCarbon($holiday->date)->format('Y/m/d') : '—' }}</div>
        </div>
        <div>
          <div class="text-gray-500">تا تاریخ</div>
          @php $endDate = $holiday->date_end ?? $holiday->date; @endphp
          <div class="font-medium">{{ $endDate ? \Morilog\Jalali\Jalalian::fromCarbon($endDate)->format('Y/m/d') : '—' }}</div>
        </div>
        <div>
          <div class="text-gray-500">عنوان</div>
          <div class="font-medium">{{ $holiday->title ?: 'تعطیلی شرکت' }}</div>
        </div>
        <div>
          <div class="text-gray-500">ارسال اعلان</div>
          <div class="font-medium">{{ $holiday->notify ? 'بله' : 'خیر' }}</div>
        </div>
        <div>
          <div class="text-gray-500">زمان آخرین ارسال</div>
          <div class="font-medium">{{ $holiday->notify_sent_at ? \Morilog\Jalali\Jalalian::fromCarbon($holiday->notify_sent_at)->format('Y/m/d H:i') : '—' }}</div>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-md shadow p-4 mb-6">
      <div class="font-semibold mb-2">متن‌های پیامک ارسال‌شده</div>
      @if($uniqueMessages->count())
        <ul class="list-disc pr-5 space-y-2 text-sm">
          @foreach($uniqueMessages as $m)
            <li class="whitespace-pre-wrap">{{ $m }}</li>
          @endforeach
        </ul>
      @else
        <div class="text-gray-500 text-sm">پیامکی برای این تعطیلی ثبت نشده است.</div>
      @endif
    </div>

    <div class="bg-white rounded-md shadow">
      <div class="p-4 border-b font-semibold">گیرندگان پیامک</div>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-right px-3 py-2">نام</th>
              <th class="text-right px-3 py-2">موبایل</th>
              <th class="text-right px-3 py-2">وضعیت</th>
              <th class="text-right px-3 py-2">تاریخ ارسال</th>
              <th class="text-right px-3 py-2">پیام</th>
            </tr>
          </thead>
          <tbody>
          @forelse($logs as $log)
            @php $u = $usersByMobile->get($log->to); @endphp
            <tr class="border-t">
              <td class="px-3 py-2">{{ $u?->name ?: '—' }}</td>
              <td class="px-3 py-2">{{ $log->to }}</td>
              <td class="px-3 py-2">
                @php $s = $log->status; @endphp
                @if($s)
                  @php
                    $color = match($s) {
                      'delivered' => 'text-green-700',
                      'failed', 'rejected' => 'text-red-700',
                      'sent','accepted','queued','pending' => 'text-yellow-700',
                      default => 'text-gray-700'
                    };
                    $label = match($s) {
                      'delivered' => 'تحویل شد',
                      'failed' => 'ناموفق',
                      'rejected' => 'رد شد',
                      'sent' => 'ارسال شد',
                      'accepted' => 'پذیرفته شد',
                      'queued' => 'در صف',
                      'pending' => 'در انتظار',
                      default => $s,
                    };
                  @endphp
                  <span class="{{ $color }}">{{ $label }}</span>
                @else
                  {{ $log->status_code === 200 ? 'ارسال شد' : ($log->status_text ?: 'نامشخص') }}
                @endif
              </td>
              <td class="px-3 py-2">{{ \Morilog\Jalali\Jalalian::fromCarbon($log->created_at)->format('Y/m/d H:i') }}</td>
              <td class="px-3 py-2 whitespace-pre-wrap">{{ mb_strimwidth($log->message, 0, 100, '…', 'UTF-8') }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="px-3 py-6 text-center text-gray-500">گیرنده‌ای برای نمایش وجود ندارد.</td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>
      <div class="p-3 text-left">
        <a href="{{ route('holidays.index') }}" class="px-3 py-2 rounded-md border hover:bg-gray-50">بازگشت</a>
      </div>
    </div>
  </div>
@endsection
