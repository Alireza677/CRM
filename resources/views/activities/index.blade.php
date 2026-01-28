@extends('layouts.app')

@section('title', 'فهرست وظایف')

@section('content')
<div class="max-w-7xl mx-auto p-4" dir="rtl">

  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">فعالیت ها</h1>
    <a href="{{ route('activities.create') }}"
       class="inline-flex items-center px-3 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700">
      + ایجاد فعالیت
    </a>
  </div>

  {{-- فیلتر ساده --}}
<form method="GET" 
      class="bg-white rounded-lg shadow p-3 mb-4 flex flex-wrap items-center gap-3">

  <input type="text" name="q" value="{{ request('q') }}" 
         placeholder="جست‌وجو در موضوع"
         class="rounded-md border p-2 flex-1 min-w-[150px]">

  <select name="status" class="rounded-md border p-2">
    <option value="">همه وضعیت‌ها</option>
    <option value="not_started" @selected(request('status')==='not_started')>شروع نشده</option>
    <option value="in_progress" @selected(request('status')==='in_progress')>در حال انجام</option>
    <option value="completed"   @selected(request('status')==='completed')>تکمیل شده</option>
    <option value="scheduled"   @selected(request('status')==='scheduled')>برنامه‌ریزی شده</option>
  </select>

  <select name="priority" class="rounded-md border p-2">
    <option value="">همه اولویت‌ها</option>
    <option value="normal" @selected(request('priority')==='normal')>معمولی</option>
    <option value="medium" @selected(request('priority')==='medium')>متوسط</option>
    <option value="high"   @selected(request('priority')==='high')>زیاد</option>
  </select>

  <button class="rounded-md bg-gray-800 text-white px-3 py-2">
    اعمال فیلتر
  </button>
</form>


  @php
    $statusMap = [
      'not_started' => ['label' => 'شروع نشده',    'class' => 'bg-gray-100 text-gray-700'],
      'in_progress' => ['label' => 'در حال انجام', 'class' => 'bg-blue-100 text-blue-700'],
      'completed'   => ['label' => 'تکمیل شده',    'class' => 'bg-green-100 text-green-700'],
      'scheduled'   => ['label' => 'برنامه‌ریزی شده','class' => 'bg-purple-100 text-purple-700'],
    ];
    $priorityMap = [
      'normal' => ['label' => 'معمولی', 'class' => 'bg-gray-100 text-gray-700'],
      'medium' => ['label' => 'متوسط',  'class' => 'bg-amber-100 text-amber-700'],
      'high'   => ['label' => 'زیاد',    'class' => 'bg-red-100 text-red-700'],
    ];
  @endphp

  <div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50 text-gray-700">
        <tr>
          <th class="px-3 py-2 text-right">موضوع</th>
          <th class="px-3 py-2 text-right">وضعیت</th>
          <th class="px-3 py-2 text-right">اولویت</th>
          <th class="px-3 py-2 text-right">ارجاع به</th>
          <th class="px-3 py-2 text-right">مربوط به</th>
          <th class="px-3 py-2 text-right">شروع</th>
          <th class="px-3 py-2 text-right">موعد</th>
          <th class="px-3 py-2 text-right">عملیات</th>
        </tr>
      </thead>
      <tbody>
        @forelse($activities as $a)
          @php
            $st = $statusMap[$a->status ?? 'not_started'] ?? $statusMap['not_started'];
            $pr = $priorityMap[$a->priority ?? 'normal'] ?? $priorityMap['normal'];
            $relatedName = $a->related_name ?? optional($a->related)->name ?? optional($a->related)->title;
          @endphp
          <tr class="border-t">
            <td class="px-3 py-2">
              <a href="{{ route('activities.show', $a->id) }}"
                 class="text-blue-600 hover:underline">{{ $a->subject ?? 'بدون عنوان' }}</a>
              @if(!empty($a->is_private))
                <span class="text-[11px] bg-zinc-100 text-zinc-700 rounded px-2 py-0.5 mr-1">خصوصی</span>
              @endif
            </td>
            <td class="px-3 py-2">
              <span class="px-2 py-1 rounded {{ $st['class'] }}">{{ $st['label'] }}</span>
            </td>
            <td class="px-3 py-2">
              <span class="px-2 py-1 rounded {{ $pr['class'] }}">{{ $pr['label'] }}</span>
            </td>
            <td class="px-3 py-2">{{ optional($a->assignedTo)->name ?? $a->assigned_to_name ?? '—' }}</td>
            <td class="px-3 py-2">{{ $relatedName ?? '—' }}</td>
            <td class="px-3 py-2">{{ optional($a->start_at)->format('Y-m-d H:i') ?? '—' }}</td>
            <td class="px-3 py-2">{{ optional($a->due_at)->format('Y-m-d H:i') ?? '—' }}</td>
            <td class="px-3 py-2">
            <div class="flex items-center gap-2">
                <a class="text-blue-600 hover:underline"
                  href="{{ route('activities.show', $a->id) }}">نمایش</a>

                {{-- دکمه تغییر وضعیت به تکمیل شده --}}
                @if($a->status !== 'completed')
                  <form action="{{ route('activities.complete', $a->id) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <button class="text-green-600 hover:underline"
                            onclick="return confirm('وضعیت این وظیفه به تکمیل شده تغییر کند؟')">
                      تکمیل
                    </button>
                  </form>
                @endif

                <a class="text-amber-600 hover:underline"
                  href="{{ route('activities.edit', $a->id) }}">ویرایش</a>

                <form action="{{ route('activities.destroy', $a->id) }}" method="POST"
                      onsubmit="return confirm('حذف شود؟')" class="inline">
                  @csrf
                  @method('DELETE')
                  <button class="text-rose-600 hover:underline">حذف</button>
                </form>
            </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="px-3 py-6 text-center text-gray-500">
              موردی یافت نشد.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- صفحه‌بندی --}}
  @if(method_exists($activities, 'links'))
    <div class="mt-4">{{ $activities->withQueryString()->links() }}</div>
  @endif

</div>
@endsection
