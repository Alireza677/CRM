<div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-800">مخاطبین مرتبط</h3>
        <a href="{{ route('sales.contacts.create', ['organization_id' => $organization->id]) }}"
           class="bg-blue-600 text-white text-sm px-4 py-2 rounded hover:bg-blue-700">
            ایجاد مخاطب
        </a>
    </div>

    @php($contacts = $organization->contacts ?? collect())
    @if($contacts->count())
        <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($contacts as $contact)
                <li class="rounded-xl border border-neutral-300 bg-white hover:bg-indigo-50 transition p-4">
                    <a class="block font-medium text-blue-700 hover:underline"
                       href="{{ route('sales.contacts.show', $contact->id) }}">
                        {{ $contact->first_name }} {{ $contact->last_name }}
                    </a>
                    <div class="text-sm text-neutral-600 mt-1">
                        {{ $contact->mobile ?: $contact->phone ?: '-' }}
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-sm text-gray-500">مخاطبی برای این سازمان ثبت نشده است.</p>
    @endif
</div>

