@php
    use App\Helpers\DateHelper;
@endphp

<div class="space-y-4">
    @forelse($updates ?? collect() as $activity)
        <div class="p-4 border rounded-lg bg-white shadow-sm flex items-start justify-between">
            <div class="space-y-1">
                <div class="text-sm font-semibold text-gray-800">
                    {{ $activity->description ?? $activity->event ?? '???????????' }}
                </div>
                <div class="text-xs text-gray-500">
                    {{ $activity->causer->name ?? '?????' }}
                </div>
            </div>
            <div class="text-xs text-gray-500">
                {{ DateHelper::toJalali($activity->created_at, 'H:i Y/m/d') }}
            </div>
        </div>
    @empty
        <div class="text-center text-gray-500 text-sm py-8">
هنوز هیچ به روزرسانی در این پیش فاکتور انجام نشده است.        </div>
    @endforelse
</div>
