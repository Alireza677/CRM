@extends('layouts.app')

@section('title', 'نمایش وظیفه')
@php use Morilog\Jalali\Jalalian; use Carbon\Carbon; @endphp

@section('content')
<div class="max-w-5xl mx-auto p-4" dir="rtl">

  {{-- سربرگ --}}
  <div class="flex items-start justify-between mb-4">
    <h1 class="text-2xl font-semibold">
      {{ $activity->subject ?? 'بدون عنوان' }}
    </h1>

    <div class="flex items-center gap-2">
      <a href="{{ route('activities.edit', $activity->id) }}"
         class="px-3 py-2 rounded-md bg-amber-500 text-white hover:bg-amber-600">ویرایش</a>

      <form action="{{ route('activities.destroy', $activity->id) }}" method="POST"
            onsubmit="return confirm('حذف شود؟')" class="inline">
        @csrf
        @method('DELETE')
        <button class="px-3 py-2 rounded-md bg-rose-600 text-white hover:bg-rose-700">حذف</button>
      </form>

      <a href="{{ route('activities.index') }}"
         class="px-3 py-2 rounded-md bg-gray-200 text-gray-800 hover:bg-gray-300">بازگشت به فعالیت ها</a>
    </div>
  </div>

  @php
    // نگاشت وضعیت و اولویت به برچسب و رنگ
    $statusMap = [
      'not_started' => ['label' => 'شروع نشده',   'class' => 'bg-gray-100 text-gray-700'],
      'in_progress' => ['label' => 'در حال انجام','class' => 'bg-blue-100 text-blue-700'],
      'completed'   => ['label' => 'تکمیل شده',   'class' => 'bg-green-100 text-green-700'],
      'scheduled'   => ['label' => 'برنامه‌ریزی شده','class' => 'bg-purple-100 text-purple-700'],
    ];
    $priorityMap = [
      'normal' => ['label' => 'معمولی', 'class' => 'bg-gray-100 text-gray-700'],
      'medium' => ['label' => 'متوسط',  'class' => 'bg-amber-100 text-amber-700'],
      'high'   => ['label' => 'زیاد',    'class' => 'bg-red-100 text-red-700'],
    ];

    $st = $statusMap[$activity->status ?? 'not_started'] ?? $statusMap['not_started'];
    $pr = $priorityMap[$activity->priority ?? 'normal'] ?? $priorityMap['normal'];

    // تشخیص آیتم مربوطه (بسته به اینکه شما رابطه‌ها را چگونه داده‌اید)
    $relatedName = null;
    $relatedUrl  = null;

    // اگر کنترلر رابطه‌ها را لود کرده باشد:
    if (isset($activity->related) && $activity->related) {
        $relatedName = $activity->related->name ?? $activity->related->title ?? ('#'.$activity->related->id);
        // در صورت داشتن روت‌های show:
        if (str_contains(class_basename($activity->related), 'Contact') && Route::has('contacts.show')) {
            $relatedUrl = route('contacts.show', $activity->related->id);
        } elseif (str_contains(class_basename($activity->related), 'Organization') && Route::has('organizations.show')) {
            $relatedUrl = route('organizations.show', $activity->related->id);
        }
    } else {
        // اگر فقط related_type / related_id داشته باشیم
        $type = strtolower($activity->related_type ?? '');
        if ($type === 'contact' && Route::has('contacts.show')) {
            $relatedUrl = route('contacts.show', $activity->related_id);
        } elseif ($type === 'organization' && Route::has('organizations.show')) {
            $relatedUrl = route('organizations.show', $activity->related_id);
        }
        // اگر کنترلر name را پاس نداده، نام را خالی می‌گذاریم
        $relatedName = $relatedName ?? ($activity->related_name ?? null);
    }
  @endphp

  {{-- کارت اطلاعات --}}
  <div class="bg-white rounded-lg shadow p-4 space-y-4">

    <div class="flex flex-wrap items-center gap-2">
      <span class="text-sm px-2 py-1 rounded {{ $st['class'] }}">وضعیت: {{ $st['label'] }}</span>
      <span class="text-sm px-2 py-1 rounded {{ $pr['class'] }}">اولویت: {{ $pr['label'] }}</span>

      @if(!empty($activity->is_private))
        <span class="text-sm px-2 py-1 rounded bg-zinc-100 text-zinc-700">خصوصی</span>
      @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
      <div class="flex items-center justify-between md:justify-start md:gap-2">
        <span class="text-gray-500">شروع:</span>
        <span class="font-medium">
          {{ $activity->due_at ? Jalalian::fromCarbon($activity->due_at)->format('Y/m/d H:i') : '—' }}
        </span>
      </div>

      <div class="flex items-center justify-between md:justify-start md:gap-2">
        <span class="text-gray-500">موعد مقرر:</span>
        <span class="font-medium">
          {{ $activity->due_at ? Jalalian::fromCarbon($activity->due_at)->format('Y/m/d H:i') : '—' }}
        </span>
      </div>

      <div class="flex items-center justify-between md:justify-start md:gap-2">
        <span class="text-gray-500">ارجاع به:</span>
        <span class="font-medium">
          {{ optional($activity->assignedTo)->name ?? $activity->assigned_to_name ?? '—' }}
        </span>
      </div>

      <div class="flex items-center justify-between md:justify-start md:gap-2">
        <span class="text-gray-500">مربوط به:</span>
        <span class="font-medium">
          @if($relatedUrl && $relatedName)
            <a href="{{ $relatedUrl }}" class="text-blue-600 hover:underline">{{ $relatedName }}</a>
          @elseif($relatedName)
            {{ $relatedName }}
          @else
            —
          @endif
        </span>
      </div>
    </div>

    @if(!empty($activity->description))
      <div>
        <div class="text-gray-500 text-sm mb-1">توضیحات</div>
        <div class="prose prose-slate max-w-none leading-7">
          {!! nl2br(e($activity->description)) !!}
        </div>
      </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-gray-500">
    <div>ایجاد: {{ $activity->created_at ? Jalalian::fromCarbon($activity->created_at)->format('Y/m/d H:i') : '—' }}</div>
    <div>به‌روزرسانی: {{ $activity->updated_at ? Jalalian::fromCarbon($activity->updated_at)->format('Y/m/d H:i') : '—' }}</div>
      <div>شناسه: #{{ $activity->id }}</div>
    </div>
  </div>

  {{-- یادآوری‌ها --}}
  <div class="bg-white rounded-lg shadow p-4 mt-4">
    <div class="flex items-center justify-between mb-3">
      <h2 class="text-lg font-semibold">یادآوری‌ها</h2>
    </div>

    @php
      $reminders = $activity->reminders ?? collect();
    @endphp

    @if($reminders->isEmpty())
      <div class="text-sm text-gray-500">هیچ یادآوری‌ای برای این وظیفه ثبت نشده است.</div>
    @else
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-right text-gray-500 border-b">
              <th class="py-2 font-medium">نوع</th>
              <th class="py-2 font-medium">زمان‌بندی</th>
              <th class="py-2 font-medium">گیرنده</th>
              <th class="py-2 font-medium">وضعیت ارسال</th>
            </tr>
          </thead>
          <tbody>
          @foreach($reminders as $r)
            @php
              $typeLabel = '—';
              if ($r->kind === 'relative') {
                $mins = (int) ($r->offset_minutes ?? 0);
                $map = [ -30 => '۳۰ دقیقه قبل از موعد', -60 => '۱ ساعت قبل از موعد', -1440 => '۱ روز قبل از موعد' ];
                $typeLabel = $map[$mins] ?? ( ($mins < 0 ? (abs($mins).' دقیقه قبل از موعد') : ($mins.' دقیقه بعد از موعد')) );
              } elseif ($r->kind === 'same_day') {
                $typeLabel = ($r->time_of_day ? ('در همان روز ساعت '.$r->time_of_day) : 'در همان روز');
              }

              $scheduledAt = null;
              try {
                if ($r->kind === 'relative') {
                  if (!empty($activity->due_at)) {
                    $scheduledAt = $activity->due_at->copy()->addMinutes((int) ($r->offset_minutes ?? 0));
                  }
                } elseif ($r->kind === 'same_day') {
                  $time = (string) ($r->time_of_day ?? '');
                  if (preg_match('/^(\d{2}):(\d{2})$/', $time, $m)) {
                    $base = $activity->due_at ?: $activity->start_at;
                    if (!empty($base)) {
                      $scheduledAt = $base->copy()->setTime((int)$m[1], (int)$m[2], 0);
                    }
                  }
                }
              } catch (\Throwable $e) {
                $scheduledAt = null;
              }

              $scheduledText = $scheduledAt ? Jalalian::fromCarbon($scheduledAt)->format('Y/m/d H:i') : '—';
              $receiver = optional($r->notifyUser)->name ?? optional($activity->assignedTo)->name ?? '—';
              $sentText = $r->sent_at ? ('ارسال شد در '.Jalalian::fromCarbon($r->sent_at)->format('Y/m/d H:i')) : 'ارسال نشده';
            @endphp
            <tr class="border-b last:border-0">
              <td class="py-2">{{ $typeLabel }}</td>
              <td class="py-2">{{ $scheduledText }}</td>
              <td class="py-2">{{ $receiver }}</td>
              <td class="py-2">
                <span class="px-2 py-0.5 rounded text-xs {{ $r->sent_at ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ $sentText }}</span>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

</div>
@endsection
