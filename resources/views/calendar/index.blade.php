@extends('layouts.app')

@section('title', 'تقویم')

@section('content')
  @php
    $weekdays = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];
  @endphp

  <div class="max-w-7xl mx-auto px-4 py-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
      <div class="flex items-center gap-2">
        <a href="{{ route('activities.create') }}"
           class="inline-flex items-center px-3 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700">
          + ایجاد فعالیت
        </a>
        @role('admin')
          <a href="{{ route('holidays.index') }}"
             class="inline-flex items-center px-3 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-100">
            مدیریت رویدادها
          </a>
        @endrole
      </div>

      <div class="flex items-center gap-2">
        <a href="{{ route('calendar.index', ['jy' => $grid['prev']['jy'], 'jm' => $grid['prev']['jm']]) }}"
           class="inline-flex items-center px-3 py-2 rounded-md border border-emerald-200 text-emerald-700 bg-white hover:bg-emerald-50">
          ماه قبل
        </a>
        <a href="{{ route('calendar.index', ['jy' => $today['jy'], 'jm' => $today['jm']]) }}"
           class="inline-flex items-center px-3 py-2 rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
          امروز
        </a>
        <a href="{{ route('calendar.index', ['jy' => $grid['next']['jy'], 'jm' => $grid['next']['jm']]) }}"
           class="inline-flex items-center px-3 py-2 rounded-md border border-emerald-200 text-emerald-700 bg-white hover:bg-emerald-50">
          ماه بعد
        </a>
      </div>
    </div>

    <div class="rounded-2xl shadow-md bg-gradient-to-l from-emerald-500 to-teal-600 text-white px-6 py-5 mb-5 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div>
        <div class="text-sm text-emerald-100">تقویم ماهانه</div>
        <div class="text-2xl font-semibold">{{ $current['title'] }}</div>
      </div>
      <div class="text-sm text-emerald-100">هفته از شنبه تا جمعه</div>
    </div>

    <div class="grid grid-cols-7 gap-2 sm:gap-3 text-sm text-slate-600 mb-2">
      @foreach ($weekdays as $weekday)
        <div class="text-center font-medium {{ $loop->last ? 'text-orange-600' : '' }}">{{ $weekday }}</div>
      @endforeach
    </div>

    <div class="grid grid-cols-7 gap-2 sm:gap-3">
      @php
        $weeks = array_chunk($grid['cells'], 7);
      @endphp
      @foreach ($weeks as $week)
        @php
          $hasDay = false;
          foreach ($week as $cell) {
            if ($cell['type'] === 'day') {
              $hasDay = true;
              break;
            }
          }
        @endphp
        @if (!$hasDay)
          @continue
        @endif
        @foreach ($week as $cell)
          @if ($cell['type'] === 'empty')
            <div class="min-h-[96px] rounded-2xl border border-dashed border-slate-200 bg-slate-50/80"></div>
          @else
            @php
              $dayActivities = ($activitiesByDate ?? collect())->get($cell['gregorian'] ?? '', collect());
              $visibleActivities = $dayActivities->take(3);
              $remainingActivities = $dayActivities->count() - $visibleActivities->count();
              $dayHolidays = ($holidaysByDate ?? collect())->get($cell['gregorian'] ?? '', collect());
              $isHoliday = $dayHolidays->contains(fn ($holiday) => (bool) $holiday->is_holiday);
              $cardClasses = 'min-h-[96px] rounded-2xl shadow-sm border border-slate-100 p-3 flex flex-col';
              if ($isHoliday) {
                $cardClasses .= ' bg-red-50/70';
              } else {
                $cardClasses .= ' bg-white';
              }
              if ($cell['is_today']) {
                $cardClasses .= ' ring-2 ring-teal-500';
                if (!$isHoliday) {
                  $cardClasses .= ' bg-teal-50';
                }
              }
            @endphp
            <div class="{{ $cardClasses }}">
              <div class="flex items-start justify-between">
                <span class="text-sm font-semibold {{ $cell['is_friday'] || $isHoliday ? 'text-orange-600' : 'text-slate-700' }}">{{ $cell['day'] }}</span>
                @if ($cell['is_today'])
                  <span class="text-[10px] font-medium text-teal-700 bg-teal-100 rounded-full px-2 py-0.5">امروز</span>
                @endif
              </div>
              @if ($dayActivities->isNotEmpty())
                <div class="mt-2 space-y-1 text-[11px]">
                  @foreach ($visibleActivities as $activity)
                    <div class="flex items-center justify-between gap-2">
                      @if (!empty($activity['url']))
                        <a href="{{ $activity['url'] }}" class="truncate text-slate-700 hover:underline">
                          {{ $activity['subject'] }}
                        </a>
                      @else
                        <span class="truncate text-slate-700">{{ $activity['subject'] }}</span>
                      @endif
                      @if (!empty($activity['start_at']))
                        <span class="shrink-0 text-slate-400">{{ $activity['start_at']->format('H:i') }}</span>
                      @endif
                    </div>
                  @endforeach
                  @if ($remainingActivities > 0)
                    <div class="text-xs font-medium text-teal-700">+{{ $remainingActivities }} more</div>
                  @endif
                </div>
              @endif
            </div>
          @endif
        @endforeach
      @endforeach
    </div>

    @php
      $allHolidays = ($holidaysByDate ?? collect())->flatten(1);
    @endphp
    @if ($allHolidays->isNotEmpty())
      <div class="mt-6 rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
        <div class="text-sm font-semibold text-slate-700 mb-3">مناسبت‌ها</div>
        <div class="space-y-2 text-sm">
          @foreach ($allHolidays as $holiday)
            @php
              $holidayClasses = $holiday->is_holiday ? 'text-red-600 font-semibold' : 'text-slate-500';
            @endphp
            <div class="flex items-center justify-between gap-3">
              <span class="truncate {{ $holidayClasses }}">{{ $holiday->title }}</span>
              @php
                $dateClasses = $holiday->is_holiday ? 'text-red-600 font-semibold' : 'text-slate-400';
              @endphp
              <span class="shrink-0 text-[11px] {{ $dateClasses }}">
                {{ $holiday->jalali_date ?? optional($holiday->date)->format('Y-m-d') }}
              </span>
            </div>
          @endforeach
        </div>
      </div>
    @endif
  </div>
@endsection

@vite([
  'resources/css/calendar.css',
])
