<div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-800">فرصت‌های فروش مرتبط</h3>
        <a href="{{ route('sales.opportunities.create', ['contact_id' => $contact->id]) }}"
           class="bg-blue-600 text-white text-sm px-4 py-2 rounded hover:bg-blue-700">
            ایجاد فرصت فروش
        </a>
    </div>

    @if($contact->opportunities->count())
        <ul class="list-disc pr-5 text-sm text-gray-700">
            @foreach($contact->opportunities as $opportunity)
                <li>
                    <a href="{{ route('sales.opportunities.show', $opportunity->id) }}" class="text-blue-600 hover:underline">
                        {{ $opportunity->name ?? ('فرصت #' . $opportunity->id) }}
                    </a>
                    <span class="text-gray-500">{{ jdate($opportunity->created_at)->format('Y/m/d') }}</span>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-sm text-gray-500">فرصت فروشی برای این مخاطب ثبت نشده است.</p>
    @endif
</div>

