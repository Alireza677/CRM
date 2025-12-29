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

    <div class="mt-8">
        <h3 class="text-md font-semibold text-gray-800 mb-4">مخاطب(های) مرتبط</h3>

        @php
            $contacts = isset($organization->contacts) ? $organization->contacts : [];
        @endphp

        @if(!empty($contacts) && count($contacts))
            <ul class="divide-y divide-gray-200 border border-gray-200 rounded-md">
                @foreach($contacts as $contact)
                    @php
                        $name = $contact->name ?? trim((($contact->first_name ?? '') . ' ' . ($contact->last_name ?? '')));
                        $email = $contact->email ?? null;
                        $phone = $contact->mobile ?? $contact->phone ?? null;
                    @endphp
                    <li class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div class="font-medium text-gray-900">
                            {{ $name !== '' ? $name : '-' }}
                        </div>
                        <div class="font-medium text-gray-900">
                            @if(!empty($contact->position))
                                <span class="inline-block">
                                    <span class="text-gray-600">سمت:</span>
                                    <span class="ml-1">{{ $contact->position }}</span>
                                </span>
                            @else
                                <span class="inline-block text-gray-500">سمت مخاطب ثبت نشده!</span>
                            @endif
                        </div>
                        <div class="font-medium text-gray-900">
                           @if(!empty($phone))
                                <span class="inline-block">
                                    <span class="text-gray-600">تلفن:</span>
                                    <span class="ml-1">{{ $phone }}</span>
                                </span>
                            @endif
                            @if(empty($email) && empty($phone))
                                <span class="text-gray-500">اطلاعات تماس ثبت نشده است</span>
                            @endif
                        </div>
                        <div class="mt-2 sm:mt-0 text-gray-700 space-x-4 space-x-reverse">
                            <a href="{{ route('sales.contacts.show', $contact->id) }}"
                               class="inline-block text-blue-600 hover:text-blue-700 hover:underline">
                                مشاهده
                            </a>
                           
                            @if(!empty($email))
                                <span class="inline-block">
                                    <span class="text-gray-600">ایمیل:</span>
                                    <span class="ml-1">{{ $email }}</span>
                                </span>
                            @endif
                            
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="text-gray-600">مخاطبی ثبت نشده است.</div>
        @endif
    </div>
</div>
