@php
    $contact = $contact ?? $model ?? null;
@endphp

<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-6">اطلاعات مخاطب</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm text-gray-700">
        <div><span class="font-semibold">نام:</span> {{ $contact->first_name }} {{ $contact->last_name }}</div>
        <div><span class="font-semibold">سمت:</span> {{ $contact->position ?? '-' }}</div>
        <div><span class="font-semibold">ایمیل:</span> {{ $contact->email ?? '-' }}</div>
        <div><span class="font-semibold">تلفن:</span> {{ $contact->phone ?? '-' }}</div>
        <div><span class="font-semibold">موبایل:</span> {{ $contact->mobile ?? '-' }}</div>

        <div><span class="font-semibold">شهر:</span> {{ $contact->city ?? '-' }}</div>
        <div><span class="font-semibold">استان:</span> {{ $contact->state ?? '-' }}</div>

        <div>
            <span class="font-semibold">ارجاع به:</span>
            {{ $contact->assignedUser->name ?? ($contact->assignedUser?->first_name . ' ' . $contact->assignedUser?->last_name) ?? '-' }}
        </div>


        <div class="sm:col-span-2"><span class="font-semibold">نشانی:</span> {{ $contact->address ?? '-' }}</div>
    </div>

    <div class="mt-8">
    <h3 class="text-md font-semibold text-gray-800 mb-4">اطلاعات سازمان</h3>

    @if($contact->organization)
        <div class="border border-gray-200 rounded-md p-4 text-sm text-gray-700">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div class="font-medium text-gray-900">
                    {{ $contact->organization->name ?? '-' }}
                </div>

                <div class="mt-2 sm:mt-0 text-gray-700 space-x-4 space-x-reverse">
                    <a href="{{ route('sales.organizations.show', $contact->organization->id) }}"
                       class="inline-block text-blue-600 hover:text-blue-700 hover:underline">
                        مشاهده
                    </a>

                    @if(!empty($contact->organization->website))
                        <span class="inline-block">
                            <span class="text-gray-600">وب‌سایت:</span>
                            <span class="ml-1">{{ $contact->organization->website }}</span>
                        </span>
                    @endif

                    @if(!empty($contact->organization->phone))
                        <span class="inline-block">
                            <span class="text-gray-600">تلفن:</span>
                            <span class="ml-1">{{ $contact->organization->phone }}</span>
                        </span>
                    @endif
                </div>
            </div>

            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3 text-gray-700">
                <div><span class="text-gray-600">شهر:</span> {{ $contact->organization->city ?? '-' }}</div>
                <div><span class="text-gray-600">استان:</span> {{ $contact->organization->state ?? '-' }}</div>
                <div class="sm:col-span-2"><span class="text-gray-600">آدرس:</span> {{ $contact->organization->address ?? '-' }}</div>
            </div>
        </div>
    @else
        <div class="text-gray-600">برای این مخاطب، سازمانی ثبت نشده است.</div>
    @endif
</div>

</div>

