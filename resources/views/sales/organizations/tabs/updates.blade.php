@php
    use App\Helpers\UpdateHelper;
@endphp

<div class="space-y-4">
    @forelse(($activities ?? []) as $activity)
        <div class="border rounded p-3 relative bg-white shadow-sm">
            <div class="text-xs text-gray-500 mb-2">
                {{ jdate($activity->created_at)->format('H:i Y/m/d') }} — {{ $activity->causer->name ?? 'سیستم' }}
            </div>

            @php
                $attributes = $activity->properties['attributes'] ?? [];
                $oldProps  = $activity->properties['old'] ?? [];
                $labels = [
                    'name' => 'نام',
                    'email' => 'ایمیل',
                    'phone' => 'تلفن',
                    'address' => 'نشانی',
                    'website' => 'وب‌سایت',
                    'industry' => 'صنعت',
                    'state' => 'استان',
                    'city' => 'شهر',
                    'assigned_to' => 'ارجاع به',
                ];
            @endphp

            @if(!empty($attributes))
                <ul class="text-sm text-gray-800 space-y-1">
                    @foreach($attributes as $key => $newVal)
                        @php
                            $oldVal = $oldProps[$key] ?? null;
                            if ($key === 'assigned_to') {
                                $oldVal = \App\Models\User::find($oldVal)?->name ?? $oldVal;
                                $newVal = \App\Models\User::find($newVal)?->name ?? $newVal;
                            }
                            $oldDisp = UpdateHelper::beautify($oldVal, $key);
                            $newDisp = UpdateHelper::beautify($newVal, $key);
                        @endphp
                        @continue($oldDisp === $newDisp)
                        <li>
                            <strong>{{ $labels[$key] ?? $key }}</strong>
                            تغییر کرد از
                            <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded">{{ $oldDisp ?? '-' }}</span>
                            به
                            <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded">{{ $newDisp ?? '-' }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="text-xs text-gray-500">جزئیاتی برای نمایش وجود ندارد.</div>
            @endif
        </div>
    @empty
        <div class="text-sm text-gray-500">به‌روزرسانی‌ای برای این سازمان ثبت نشده است.</div>
    @endforelse
</div>

