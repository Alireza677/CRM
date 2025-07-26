<div class="bg-white p-6 rounded shadow">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-700">مخاطب مرتبط</h3>
        <a href="{{ route('sales.contacts.create', ['opportunity_id' => $opportunity->id]) }}"
           class="bg-blue-600 text-white px-4 py-2 text-sm rounded hover:bg-blue-700 transition">
            افزودن مخاطب
        </a>
    </div>

    @if ($opportunity->contact)
        <table class="w-full text-sm text-right border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">نام</th>
                    <th class="px-4 py-2">ایمیل</th>
                    <th class="px-4 py-2">تلفن</th>
                    <th class="px-4 py-2">سِمت</th>
                    <th class="px-4 py-2">سازمان</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-t hover:bg-gray-50">
                    <td class="px-4 py-2">
                        <a href="{{ route('sales.contacts.show', $opportunity->contact->id) }}"
                        class="text-blue-600 hover:underline">
                            {{ $opportunity->contact->full_name ?? '-' }}
                        </a>
                    </td>
                    <td class="px-4 py-2">{{ $opportunity->contact->email ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $opportunity->contact->phone ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $opportunity->contact->position ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $opportunity->contact->organization->name ?? '-' }}</td>
                </tr>
            </tbody>
        </table>
    @else
        <p class="text-gray-500 mt-4 text-sm">مخاطبی برای این فرصت ثبت نشده است.</p>
    @endif
</div>
