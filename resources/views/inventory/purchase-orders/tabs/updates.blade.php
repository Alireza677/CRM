@php
    use App\Helpers\DateHelper;
    use App\Helpers\UpdateHelper;
@endphp

<div class="space-y-6">
    @forelse(($activities ?? collect()) as $activity)
        <div class="bg-white shadow-sm rounded-lg p-4 border relative">
            <div class="absolute top-4 left-4 text-xs text-gray-400">
                {{ DateHelper::toJalali($activity->created_at, 'H:i Y/m/d') }}
            </div>

            <div class="text-sm mb-2">
                <span class="font-semibold text-blue-700">{{ $activity->causer->name ?? 'سیستم' }}</span>
                تغییری ایجاد کرد.
            </div>

            <ul class="text-sm text-gray-800 space-y-1">
                @foreach($activity->properties['attributes'] ?? [] as $key => $new)
                    @php
                        $old = $activity->properties['old'][$key] ?? null;
                        $oldValue = UpdateHelper::beautify($old, $key);
                        $newValue = UpdateHelper::beautify($new, $key);
                    @endphp
                    @if($oldValue !== $newValue)
                        <li>
                            <strong>{{ __("fields.$key") }}</strong>
                            تغییر یافت از
                            <span class="bg-red-100 text-red-700 px-2 py-1 rounded">{{ $oldValue }}</span>
                            به
                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded">{{ $newValue }}</span>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    @empty
        <div class="text-center text-gray-400">هیچ به‌روزرسانی‌ای ثبت نشده است.</div>
    @endforelse
</div>

