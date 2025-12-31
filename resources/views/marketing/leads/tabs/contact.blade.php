<div class="bg-white p-6 rounded shadow">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-700">مخاطب مرتبط</h3>
        <div class="flex items-center gap-2">
            <button type="button"
                    class="bg-indigo-600 text-white px-4 py-2 text-sm rounded hover:bg-indigo-700 transition"
                    onclick="openLeadContactModal()">
                اتصال به مخاطب موجود
            </button>
            <a href="{{ route('sales.contacts.create', ['lead_id' => $lead->id]) }}"
               class="bg-blue-600 text-white px-4 py-2 text-sm rounded hover:bg-blue-700 transition">
                ایجاد مخاطب
            </a>
        </div>
    </div>

    @php
        $primaryContactId = $lead->contact_id ?? null;
        $leadContacts = $lead->contacts ?? collect();
    @endphp

    @if ($leadContacts->isNotEmpty())
        <div class="overflow-hidden border border-gray-200 rounded"
             data-detach-url="{{ route('marketing.leads.detach-contact', $lead) }}"
             data-primary-url="{{ route('marketing.leads.primary-contact', $lead) }}">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2">نام مخاطب</th>
                        <th class="px-4 py-2">شماره موبایل</th>
                        <th class="px-4 py-2">سمت</th>
                        <th class="px-4 py-2">سازمان</th>
                        <th class="px-4 py-2 text-left">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($leadContacts as $contact)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-2">
                                <a href="{{ route('sales.contacts.show', $contact->id) }}"
                                   class="text-blue-600 hover:underline">
                                    {{ $contact->full_name ?? $contact->name ?? '-' }}
                                </a>

                                @if($primaryContactId && (int) $primaryContactId === (int) $contact->id)
                                    <span class="mr-2 text-xs text-green-700 bg-green-100 px-2 py-0.5 rounded">
                                        مخاطب اصلی
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-2 text-gray-600">{{ $contact->mobile ?? $contact->phone ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $contact->position ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $contact->organization->name ?? '-' }}</td>

                            <td class="px-4 py-2 text-left">
                                <div class="flex items-center gap-2 justify-end">
                                    @if(!$primaryContactId || (int) $primaryContactId !== (int) $contact->id)
                                        <button type="button"
                                                class="text-xs px-2 py-1 rounded bg-amber-100 text-amber-800 hover:bg-amber-200"
                                                data-action="set-primary"
                                                data-contact-id="{{ $contact->id }}">
                                            تعیین به‌عنوان مخاطب اصلی
                                        </button>
                                    @endif

                                    <button type="button"
                                            class="text-xs px-2 py-1 rounded bg-red-100 text-red-700 hover:bg-red-200"
                                            data-action="detach-contact"
                                            data-contact-id="{{ $contact->id }}">
                                        حذف ارتباط
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-gray-500 mt-4 text-sm">هیچ مخاطبی برای این سرنخ ثبت نشده است.</p>
    @endif
</div>

@php
    $attachUrl = route('marketing.leads.attach-contact', $lead);
@endphp

@include('marketing.leads.partials.contact-modal', [
    'contacts' => $contacts ?? collect(),
    'attachUrl' => $attachUrl
])
