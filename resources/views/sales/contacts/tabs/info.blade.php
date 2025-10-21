<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-6">اطلاعات مخاطب</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm text-gray-700">
        <div><span class="font-semibold">نام:</span> {{ $contact->first_name }} {{ $contact->last_name }}</div>
        <div><span class="font-semibold">ایمیل:</span> {{ $contact->email ?? '-' }}</div>
        <div><span class="font-semibold">تلفن:</span> {{ $contact->phone ?? '-' }}</div>
        <div><span class="font-semibold">موبایل:</span> {{ $contact->mobile ?? '-' }}</div>

        <div><span class="font-semibold">شهر:</span> {{ $contact->city ?? '-' }}</div>
        <div><span class="font-semibold">استان:</span> {{ $contact->state ?? '-' }}</div>

        <div>
            <span class="font-semibold">ارجاع به:</span>
            {{ $contact->assignedUser->name ?? ($contact->assignedUser?->first_name . ' ' . $contact->assignedUser?->last_name) ?? '-' }}
        </div>

        <div>
            <span class="font-semibold">سازمان:</span>
            @if($contact->organization)
                <a href="{{ route('sales.organizations.show', $contact->organization->id) }}" class="text-indigo-600 hover:underline">
                    {{ $contact->organization->name }}
                </a>
            @else
                -
            @endif
        </div>

        <div class="sm:col-span-2"><span class="font-semibold">نشانی:</span> {{ $contact->address ?? '-' }}</div>
    </div>
</div>

