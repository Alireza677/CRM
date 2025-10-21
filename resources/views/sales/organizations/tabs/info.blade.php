<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-6">اطلاعات سازمان</h2>

    <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-6 text-sm">
        <div class="space-y-1">
            <dt class="text-gray-600">نام</dt>
            <dd class="font-medium text-gray-900">{{ $organization->name ?: '-' }}</dd>
        </div>

        <div class="space-y-1">
            <dt class="text-gray-600">ایمیل</dt>
            <dd class="text-gray-900">{{ $organization->email ?: '-' }}</dd>
        </div>

        <div class="space-y-1">
            <dt class="text-gray-600">تلفن</dt>
            <dd class="text-gray-900">{{ $organization->phone ?: '-' }}</dd>
        </div>

        <div class="space-y-1">
            <dt class="text-gray-600">نشانی</dt>
            <dd class="text-gray-900">{{ $organization->address ?: '-' }}</dd>
        </div>

        <div class="space-y-1">
            <dt class="text-gray-600">وب‌سایت</dt>
            <dd>
                @if($organization->website)
                    <a href="{{ $organization->website }}" class="text-blue-700 hover:underline" target="_blank">
                        {{ $organization->website }}
                    </a>
                @else
                    <span class="text-gray-600">-</span>
                @endif
            </dd>
        </div>

        <div class="space-y-1">
            <dt class="text-gray-600">صنعت</dt>
            <dd class="text-gray-900">{{ $organization->industry ?: '-' }}</dd>
        </div>

        <div class="space-y-1">
            <dt class="text-gray-600">استان</dt>
            <dd class="text-gray-900">{{ $organization->state ?: '-' }}</dd>
        </div>

        <div class="space-y-1">
            <dt class="text-gray-600">شهر</dt>
            <dd class="text-gray-900">{{ $organization->city ?: '-' }}</dd>
        </div>

        <div class="space-y-1 sm:col-span-2 lg:col-span-3">
            <dt class="text-gray-600">توضیحات</dt>
            <dd class="text-gray-900 leading-relaxed">{{ $organization->notes ?: '-' }}</dd>
        </div>
    </dl>
</div>

