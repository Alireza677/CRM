@php
    $contact = $contact ?? $model ?? null;
@endphp

<div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-800">پیش‌فاکتورها</h3>
        <a href="{{ route('sales.proformas.create', ['contact_id' => $contact->id]) }}"
           class="bg-blue-600 text-white text-sm px-4 py-2 rounded hover:bg-blue-700">
            ایجاد پیش‌فاکتور
        </a>
    </div>

    @if($contact->proformas->count())
        <ul class="list-disc pr-5 text-sm text-gray-700">
            @foreach($contact->proformas as $proforma)
                <li>
                    <a href="{{ route('sales.proformas.show', $proforma->id) }}" class="text-blue-600 hover:underline">
                        پیش‌فاکتور شماره {{ $proforma->id }}
                    </a>
                    <span class="text-gray-500">{{ jdate($proforma->created_at)->format('Y/m/d') }}</span>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-sm text-gray-500">پیش‌فاکتوری برای این مخاطب ثبت نشده است.</p>
    @endif
</div>
