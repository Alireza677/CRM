@php
    $proforma = $proforma ?? $model ?? null;
    use App\Helpers\DateHelper;
@endphp

@if(!$proforma)
    <div class="text-sm text-gray-500">به‌روزرسانی‌ها در دسترس نیست.</div>
@else
    @php ob_start(); @endphp

<div class="space-y-4">
    @forelse($updates ?? collect() as $activity)
        <div class="p-4 border rounded-lg bg-white shadow-sm flex items-start justify-between">
            <div class="space-y-1">
                <div class="text-sm font-semibold text-gray-800">
                    {{ $activity->description ?? $activity->event ?? '(بدون توضیح)' }}
                </div>
                <div class="text-xs text-gray-500">
                    {{ $activity->causer->name ?? 'سیستم' }}
                </div>
            </div>
            <div class="text-xs text-gray-500">
                {{ DateHelper::toJalali($activity->created_at, 'H:i Y/m/d') }}
            </div>
        </div>
    @empty
        <div class="text-center text-gray-500 text-sm py-8">
            هنوز هیچ به‌روزرسانی در این پیش‌فاکتور انجام نشده است.
        </div>
    @endforelse
</div>
    @php
        $__html = ob_get_clean();
        $blocks = [[
            'type' => 'html',
            'html' => $__html,
            'class' => 'md:col-span-2 lg:col-span-3 p-0 bg-transparent border-0 shadow-none rounded-none',
        ]];
    @endphp
    @include('crud.partials.cards', ['blocks' => $blocks])
@endif
