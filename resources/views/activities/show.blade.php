@extends('layouts.app')

@section('title', 'نمایش وظیفه')
@php use Morilog\Jalali\Jalalian; @endphp

@section('content')
<div class="max-w-6xl mx-auto p-4" dir="rtl">

  {{-- سربرگ --}}
  <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-4">
    <div>
      <h1 class="text-2xl font-semibold">
        {{ $activity->subject ?? 'بدون عنوان' }}
      </h1>
      <div class="text-xs text-gray-500 mt-1">شناسه: #{{ $activity->id }}</div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
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
    $progress = max(0, min(100, (int) ($activity->progress ?? 0)));

    $relatedName = null;
    $relatedUrl  = null;

    if (isset($activity->related) && $activity->related) {
        $relatedName = $activity->related->name ?? $activity->related->title ?? ('#'.$activity->related->id);
        if (str_contains(class_basename($activity->related), 'Contact') && Route::has('contacts.show')) {
            $relatedUrl = route('contacts.show', $activity->related->id);
        } elseif (str_contains(class_basename($activity->related), 'Organization') && Route::has('organizations.show')) {
            $relatedUrl = route('organizations.show', $activity->related->id);
        }
    } else {
        $type = strtolower($activity->related_type ?? '');
        if ($type === 'contact' && Route::has('contacts.show')) {
            $relatedUrl = route('contacts.show', $activity->related_id);
        } elseif ($type === 'organization' && Route::has('organizations.show')) {
            $relatedUrl = route('organizations.show', $activity->related_id);
        }
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

    <div>
      <div class="text-sm text-gray-500 mb-2">پیشرفت</div>
      <div class="flex items-center gap-3">
        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
          <div class="h-2 bg-blue-600" style="width: {{ $progress }}%"></div>
        </div>
        <div class="text-sm font-medium">{{ $progress }}%</div>
      </div>
      <form action="{{ route('activities.progress', $activity) }}" method="POST" class="mt-2 flex items-center gap-2">
        @csrf
        <input type="range" name="progress" id="progress_range_show" min="0" max="100" value="{{ $progress }}" class="w-full">
        <button class="px-3 py-1.5 rounded-md bg-blue-600 text-white text-sm">ثبت</button>
      </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
      <div class="flex items-center justify-between md:justify-start md:gap-2">
        <span class="text-gray-500">شروع:</span>
        <span class="font-medium">
          {{ $activity->start_at ? Jalalian::fromCarbon($activity->start_at)->format('Y/m/d H:i') : '—' }}
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
      <div>ایجادکننده: {{ optional($activity->creator)->name ?? '—' }}</div>
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
              } elseif ($r->kind === 'absolute') {
                $typeLabel = 'زمان مشخص';
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
                } elseif ($r->kind === 'absolute') {
                  $scheduledAt = $r->remind_at;
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

  {{-- تب‌ها --}}
  <div class="bg-white rounded-lg shadow mt-6">
    <div class="border-b px-4">
      <div class="flex flex-wrap gap-4 text-sm" id="activityTabs">
        <button type="button" data-tab="notes" class="py-3 border-b-2 border-blue-600 text-blue-600 font-medium">یادداشت‌ها</button>
        <button type="button" data-tab="followups" class="py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700">پیگیری‌ها</button>
        <button type="button" data-tab="documents" class="py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700">اسناد</button>
        <button type="button" data-tab="timeline" class="py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700">تایم‌لاین</button>
      </div>
    </div>

    {{-- Notes Tab --}}
    <div data-tab-panel="notes" class="p-4 space-y-6">
      <form action="{{ route('activities.notes.store', $activity) }}" method="POST" class="grid gap-3 md:grid-cols-3" enctype="multipart/form-data">
        @csrf
        <div class="md:col-span-2">
          <label class="block mb-1 font-medium">متن یادداشت</label>
          <div class="relative">
            <textarea id="note-body" name="body" rows="3" required
                    class="w-full border rounded p-2 focus:outline-none focus:ring"
                    placeholder="برای منشن از علامت @ در متن یادداشت استفاده کنید.">{{ old('body') }}</textarea>
            <div id="mention-suggestions"
                 class="hidden absolute z-10 bottom-full mb-1 w-full max-h-48 overflow-y-auto rounded border bg-white shadow">
            </div>
          </div>
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
          <div class="md:col-span-3">
            @foreach ($errors->all() as $error)
              <div class="text-sm text-red-600">{{ $error }}</div>
            @endforeach
          </div>
        @endif
      </form>

      <div class="space-y-4">
        @forelse($activity->notes as $note)
          <div id="note-{{ $note->id }}" class="border rounded p-3 bg-gray-50">
            <div class="flex items-center justify-between">
              <div class="text-sm text-gray-600">
                توسط <span class="font-medium">{{ $note->author->name ?? $note->author->email }}</span>
                • {{ $note->created_at?->diffForHumans() }}
              </div>
              <form action="{{ route('activities.notes.destroy', [$activity, $note]) }}" method="POST"
                    onsubmit="return confirm('حذف این یادداشت؟')">
                @csrf @method('DELETE')
                <button class="text-red-600 hover:underline text-sm">حذف</button>
              </form>
            </div>

            <div class="mt-2 text-gray-800 whitespace-pre-line">{{ $note->display_body ?? $note->body }}</div>
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
                    </div>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        @empty
          <div class="text-gray-500">هنوز یادداشتی ثبت نشده است.</div>
        @endforelse
      </div>
    </div>

    {{-- Followups Tab --}}
    <div data-tab-panel="followups" class="p-4 space-y-6 hidden">
      <form action="{{ route('activities.followups.store', $activity) }}" method="POST" class="grid gap-3 md:grid-cols-3">
        @csrf
        <div>
          <label class="block mb-1 font-medium">تاریخ پیگیری</label>
          <input type="hidden" id="followup_at" name="followup_at">
          <input type="text" id="followup_at_display" class="persian-datetime w-full rounded-md border p-2" data-alt-field="followup_at" autocomplete="off">
        </div>
        <div>
          <label class="block mb-1 font-medium">عنوان</label>
          <input type="text" name="title" class="w-full rounded-md border p-2" required>
        </div>
        <div>
          <label class="block mb-1 font-medium">ارجاع به</label>
          <select name="assigned_to_id" class="w-full rounded-md border p-2">
            <option value="">—</option>
            @foreach($users as $u)
              <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="md:col-span-3">
          <label class="block mb-1 font-medium">یادداشت</label>
          <textarea name="note" rows="2" class="w-full rounded-md border p-2"></textarea>
        </div>
        <div class="md:col-span-3">
          <button class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">ثبت پیگیری</button>
        </div>
      </form>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-right text-gray-500 border-b">
              <th class="py-2 font-medium">تاریخ</th>
              <th class="py-2 font-medium">عنوان</th>
              <th class="py-2 font-medium">مسئول</th>
              <th class="py-2 font-medium">وضعیت</th>
              <th class="py-2 font-medium">عملیات</th>
            </tr>
          </thead>
          <tbody>
          @forelse($activity->followups as $followup)
            <tr class="border-b last:border-0">
              <td class="py-2 whitespace-nowrap">{{ $followup->followup_at ? Jalalian::fromCarbon($followup->followup_at)->format('Y/m/d H:i') : '—' }}</td>
              <td class="py-2">
                <div class="font-medium">{{ $followup->title }}</div>
                @if($followup->note)
                  <div class="text-xs text-gray-500 mt-1">{{ $followup->note }}</div>
                @endif
              </td>
              <td class="py-2">{{ optional($followup->assignedTo)->name ?? '—' }}</td>
              <td class="py-2">
                @php
                  $fStatus = [
                    'pending' => ['label' => 'باز', 'class' => 'bg-amber-100 text-amber-700'],
                    'done' => ['label' => 'انجام شد', 'class' => 'bg-green-100 text-green-700'],
                    'canceled' => ['label' => 'لغو شد', 'class' => 'bg-gray-100 text-gray-700'],
                  ][$followup->status] ?? ['label' => '—', 'class' => 'bg-gray-100 text-gray-700'];
                @endphp
                <span class="px-2 py-0.5 rounded text-xs {{ $fStatus['class'] }}">{{ $fStatus['label'] }}</span>
              </td>
              <td class="py-2">
                <form action="{{ route('activities.followups.update', [$activity, $followup]) }}" method="POST" class="flex items-center gap-2">
                  @csrf @method('PUT')
                  <select name="status" class="rounded-md border p-1 text-xs">
                    <option value="pending" {{ $followup->status === 'pending' ? 'selected' : '' }}>باز</option>
                    <option value="done" {{ $followup->status === 'done' ? 'selected' : '' }}>انجام شد</option>
                    <option value="canceled" {{ $followup->status === 'canceled' ? 'selected' : '' }}>لغو شد</option>
                  </select>
                  <button class="text-xs px-2 py-1 rounded bg-blue-600 text-white">بروزرسانی</button>
                </form>
                <form action="{{ route('activities.followups.destroy', [$activity, $followup]) }}" method="POST" class="mt-2" onsubmit="return confirm('حذف پیگیری؟')">
                  @csrf @method('DELETE')
                  <button class="text-xs text-red-600 hover:underline">حذف</button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="py-3 text-gray-500">پیگیری‌ای ثبت نشده است.</td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Documents Tab --}}
    <div data-tab-panel="documents" class="p-4 space-y-6 hidden">
      <form action="{{ route('activities.attachments.store', $activity) }}" method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row items-start gap-3">
        @csrf
        <input type="file" name="attachments[]" multiple
               class="block w-full text-sm text-gray-700 border border-gray-300 rounded cursor-pointer focus:outline-none">
        <button class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">بارگذاری</button>
      </form>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        @forelse($activity->attachments as $att)
          @php
            $ext = strtolower(pathinfo($att->file_path, PATHINFO_EXTENSION));
            $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp']);
            $url = asset('storage/' . $att->file_path);
          @endphp
          <div class="border rounded p-3 flex items-center gap-3">
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
              <a href="{{ $url }}" class="text-sm text-blue-600 hover:underline block truncate">{{ $att->file_name }}</a>
              @if($att->file_size)
                <div class="text-xs text-gray-500">{{ number_format($att->file_size / 1024, 1) }} KB</div>
              @endif
            </div>
            <form action="{{ route('activities.attachments.destroy', [$activity, $att]) }}" method="POST" class="ml-auto" onsubmit="return confirm('حذف فایل؟')">
              @csrf @method('DELETE')
              <button class="text-xs text-red-600 hover:underline">حذف</button>
            </form>
          </div>
        @empty
          <div class="text-gray-500">فایلی ثبت نشده است.</div>
        @endforelse
      </div>
    </div>

    {{-- Timeline Tab --}}
    <div data-tab-panel="timeline" class="p-4 space-y-4 hidden">
      @forelse($timeline as $entry)
        <div class="border rounded p-3">
          <div class="flex items-center justify-between text-sm">
            <span class="font-medium text-gray-800">{{ $entry->description }}</span>
            <span class="text-xs text-gray-500">{{ $entry->created_at ? Jalalian::fromCarbon($entry->created_at)->format('Y/m/d H:i') : '' }}</span>
          </div>
          <div class="text-xs text-gray-500 mt-1">{{ optional($entry->causer)->name ?? 'سیستم' }}</div>
        </div>
      @empty
        <div class="text-gray-500">هنوز رویدادی ثبت نشده است.</div>
      @endforelse
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
  (function () {
    const tabs = document.querySelectorAll('#activityTabs [data-tab]');
    const panels = document.querySelectorAll('[data-tab-panel]');
    if (!tabs.length || !panels.length) return;

    function activate(tab) {
      const target = tab.getAttribute('data-tab');
      tabs.forEach(btn => {
        btn.classList.toggle('border-blue-600', btn === tab);
        btn.classList.toggle('text-blue-600', btn === tab);
        btn.classList.toggle('font-medium', btn === tab);
        btn.classList.toggle('border-transparent', btn !== tab);
        btn.classList.toggle('text-gray-500', btn !== tab);
      });
      panels.forEach(panel => {
        panel.classList.toggle('hidden', panel.getAttribute('data-tab-panel') !== target);
      });
    }

    tabs.forEach(btn => btn.addEventListener('click', () => activate(btn)));
  })();

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

  if (textarea && suggestions) {
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

    const insertMention = (label) => {
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
    };

    textarea.addEventListener('input', updateSuggestions);
    textarea.addEventListener('click', updateSuggestions);
    textarea.addEventListener('keyup', updateSuggestions);
    textarea.addEventListener('blur', () => {
        setTimeout(() => suggestions.classList.add('hidden'), 150);
    });

    suggestions.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-label]');
        if (!btn) return;
        const label = btn.getAttribute('data-label');
        insertMention(label);
    });
  }

  (function initDateTimePicker(selector) {
      const $ui = $(selector);
      if (!$ui.length) return;

      const altId = $ui.data('alt-field');
      const $alt = altId ? $('#' + altId) : null;

      try { $ui.persianDatepicker('destroy'); } catch (e) {}

      $ui.persianDatepicker({
        format: 'YYYY/MM/DD HH:mm',
        initialValue: false,
        autoClose: true,
        observer: true,
        calendar: {
          persian:   { locale: 'fa', leapYearMode: 'astronomical' },
          gregorian: { locale: 'en' }
        },
        timePicker: { enabled: true, step: 1, meridiem: { enabled: false } },
        onSelect: function (unix) {
          if (!$alt) return;
          try {
            const g = new persianDate(unix).toCalendar('gregorian');
            const y = g.year();
            const m = ('0' + g.month()).slice(-2);
            const d = ('0' + g.date()).slice(-2);
            const hh = ('0' + g.hour()).slice(-2);
            const mm = ('0' + g.minute()).slice(-2);
            $alt.val(`${y}-${m}-${d} ${hh}:${mm}:00`);
          } catch (e) {}
        }
      });
  })('#followup_at_display');

  (function initLightbox() {
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
    lightboxClose?.addEventListener('click', closeLightbox);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeLightbox();
    });
  })();
</script>
@endpush
