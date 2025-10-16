@extends('layouts.app')

@section('content')
@php
    $q = request('q', '');

    function hi($text, $q) {
        if (!$q || !$text) return e($text);
        $safe = preg_quote($q, '/');
        return preg_replace('/(' . $safe . ')/iu', '<mark class="px-1 rounded bg-yellow-200">$1</mark>', e($text));
    }

    $icons = [
        'contacts'      => '👤',
        'organizations' => '🏢',
        'opportunities' => '📈',
        'proformas'     => '🧾',
    ];

    $labels = [
        'contacts'      => 'مخاطبین',
        'organizations' => 'سازمان‌ها',
        'opportunities' => 'فرصت‌های فروش',
        'proformas'     => 'پیش‌فاکتورها',
    ];

    $total = collect($results ?? [])->flatten(1)->count();
@endphp

<div class="container py-10" dir="rtl">
  <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-800">نتایج جستجو برای «{{ $q }}»</h1>
      <form action="{{ route('global.search') }}" method="get" class="w-full max-w-md">
        <div class="relative">
          <input name="q" value="{{ $q }}"
                 class="w-full rounded-2xl border border-gray-300 pr-4 pl-12 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                 placeholder="جستجو در کل سامانه..." />
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-2xl">🔎</span>
        </div>
      </form>
    </div>

    <div class="mb-8">
      <div class="rounded-2xl bg-white/70 backdrop-blur border border-gray-200 p-4 shadow-sm flex items-center gap-4">
        <div class="text-3xl">📊</div>
        <div>
          <div class="font-semibold text-gray-800">تعداد نتایج: {{ $total }}</div>
          <div class="text-gray-600 text-sm">نتایج به تفکیک نوع رکورد در باکس‌های زیر نمایش داده شده‌اند.</div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      @forelse(($results ?? []) as $groupKey => $items)
        <div class="rounded-2xl bg-white/80 backdrop-blur border border-gray-200 shadow-md">
          <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <span class="text-2xl">{{ $icons[$groupKey] ?? '🔹' }}</span>
              <h2 class="font-bold text-gray-800">{{ $labels[$groupKey] ?? $groupKey }}</h2>
            </div>
            <span class="text-xs bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full">{{ $items->count() }} نتیجه</span>
          </div>

          @if($items->isEmpty())
            <div class="p-5 text-gray-500">موردی یافت نشد.</div>
          @else
            <ul class="divide-y divide-gray-200">
              @foreach($items as $item)
                <li class="p-5 hover:bg-gray-50 transition">
                  <div class="flex flex-col gap-2">

                    <div class="flex items-center justify-between gap-3">
                      <a href="{{ $item->show_url ?? '#' }}" class="text-indigo-700 hover:text-indigo-900 font-semibold truncate">
                        {!! hi($item->title ?? ($item->name ?? ('#'.$item->id)), $q) !!}
                      </a>
                      @php $chip = $item->status ?? $item->stage ?? $item->approval_stage ?? null; @endphp
                      @if($chip)
                        <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-700 shrink-0">{{ $chip }}</span>
                      @endif
                    </div>

                    @if(!empty($item->summary) || !empty($item->description))
                      <p class="text-sm text-gray-600 line-clamp-2">
                        {!! hi(Str::limit(strip_tags($item->summary ?? $item->description), 180), $q) !!}
                      </p>
                    @endif

                    <div class="flex flex-wrap gap-2 text-xs text-gray-600">
                      @if(isset($item->phone) && $item->phone)
                        <span class="px-2 py-1 bg-gray-100 rounded">📞 {{ $item->phone }}</span>
                      @endif
                      @if(isset($item->email) && $item->email)
                        <span class="px-2 py-1 bg-gray-100 rounded">✉️ {{ $item->email }}</span>
                      @endif
                      @if(isset($item->organization) && $item->organization)
                        <span class="px-2 py-1 bg-gray-100 rounded">🏢 {{ $item->organization->name ?? $item->organization_name }}</span>
                      @endif
                      @if(isset($item->amount))
                        <span class="px-2 py-1 bg-gray-100 rounded">💷 {{ number_format($item->amount) }}</span>
                      @endif
                      @if(isset($item->total))
                        <span class="px-2 py-1 bg-gray-100 rounded">💷 {{ number_format($item->total) }}</span>
                      @endif
                      @if(isset($item->assigned_to) && $item->assigned_to)
                        <span class="px-2 py-1 bg-gray-100 rounded">👤 ارجاع: {{ $item->assigned_to->name ?? $item->assigned_to_name }}</span>
                      @endif
                      @if(isset($item->updated_at))
                        <span class="px-2 py-1 bg-gray-100 rounded">🗓 آخرین بروزرسانی: {{ jdate($item->updated_at)->format('Y/m/d H:i') }}</span>
                      @endif
                    </div>

                    <div class="mt-2 flex items-center gap-2">
                      <a href="{{ $item->show_url ?? '#' }}" class="text-sm px-3 py-1.5 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">مشاهده</a>
                      @if(!empty($item->edit_url))
                        <a href="{{ $item->edit_url }}" class="text-sm px-3 py-1.5 rounded-xl bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">ویرایش</a>
                      @endif
                    </div>

                  </div>
                </li>
              @endforeach
            </ul>
          @endif
        </div>
      @empty
        <div class="rounded-2xl bg-white/80 backdrop-blur border border-gray-200 p-8 text-center text-gray-600">
          هیچ نتیجه‌ای یافت نشد.
        </div>
      @endforelse
    </div>
  </div>
</div>
@endsection
