@extends('layouts.app')

@section('content')
<div class="px-6 h-[calc(100vh-140px)] flex flex-col gap-4 overflow-hidden" dir="rtl">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="text-right">
      <h1 class="text-lg font-bold text-gray-800">فعالیت‌ها</h1>
      <div class="text-xs text-gray-500">{{ $activities->total() }} مورد</div>
    </div>
    <div class="flex flex-wrap justify-end gap-2">
      <a href="{{ route('activities.create') }}"
         class="inline-flex items-center h-9 px-4 bg-blue-600 text-white rounded-md hover:bg-blue-700">
        + ایجاد فعالیت
      </a>
    </div>
  </div>

  <div class="flex flex-wrap items-center justify-between gap-3">
    {{-- فیلتر ساده --}}
    <form id="filters-form" method="GET" class="bg-white rounded-lg shadow p-3 flex flex-wrap items-center gap-3">
      <input type="hidden" name="per_page" value="{{ request('per_page', 15) }}">

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

    <form id="per-page-form" method="GET" class="flex items-center gap-2">
      <input type="hidden" name="q" value="{{ request('q') }}">
      <input type="hidden" name="status" value="{{ request('status') }}">
      <input type="hidden" name="priority" value="{{ request('priority') }}">
      <label class="text-xs text-gray-500">تعداد در صفحه</label>
      <select name="per_page" class="rounded-md border border-gray-200 bg-white px-2 py-1 text-xs">
        @foreach([15, 30, 50] as $size)
          <option value="{{ $size }}" @selected((int) request('per_page', 15) === $size)>{{ $size }}</option>
        @endforeach
      </select>
      <div class="text-xs text-gray-500">صفحه {{ $activities->currentPage() }} از {{ $activities->lastPage() }}</div>
    </form>
  </div>


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

  <div class="flex-1 min-h-0 overflow-auto">
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
  </div>

  {{-- صفحه‌بندی --}}
  @if(method_exists($activities, 'links'))
    <div class="shrink-0 border-t border-gray-200 bg-white py-2">
      {{ $activities->withQueryString()->links() }}
    </div>
  @endif

</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const perPageSelect = document.querySelector('#per-page-form select[name="per_page"]');
    if (perPageSelect && perPageSelect.form) {
      perPageSelect.addEventListener('change', () => perPageSelect.form.submit());
    }

    const filtersForm = document.getElementById('filters-form');
    if (filtersForm) {
      const filterFields = filtersForm.querySelectorAll('select, input[type="text"]');
      filterFields.forEach((field) => {
        if (field.tagName === 'SELECT') {
          field.addEventListener('change', () => filtersForm.submit());
          return;
        }

        let timer = null;
        const submitLater = () => {
          clearTimeout(timer);
          timer = setTimeout(() => filtersForm.submit(), 400);
        };

        field.addEventListener('input', submitLater);
        field.addEventListener('change', submitLater);
        field.addEventListener('keydown', (event) => {
          if (event.key === 'Enter') {
            event.preventDefault();
            filtersForm.submit();
          }
        });
      });
    }
  });
</script>
@endsection
