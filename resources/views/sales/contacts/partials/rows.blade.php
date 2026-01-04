@forelse($contacts as $contact)
    <tr>
        <td class="px-4 py-4">
            <input type="checkbox" name="selected_contacts[]" value="{{ $contact->id }}" class="select-contact">
        </td>
        <td class="px-6 py-4 text-sm text-gray-900">
            {{ $contact->contact_number ?? '-' }}
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <a href="{{ route('sales.contacts.show', $contact->id) }}"
               class="text-sm font-medium text-blue-600 hover:underline">
               {{ $contact->first_name }} {{ $contact->last_name }}
            </a>
            @if($contact->is_favorite)
                <i class="fas fa-star text-yellow-400 ml-1"></i>
            @endif
        </td>
        <td class="px-6 py-4 text-sm text-gray-900">{{ $contact->mobile }}</td>
        <td class="px-6 py-4 text-sm text-gray-900">{{ $contact->organization_name ?? '-' }}</td>
        <td class="px-6 py-4 text-sm text-gray-900">{{ $contact->assigned_to_name ?? '-' }}</td>
        <td class="px-6 py-4 text-sm text-gray-500">{{ jdate($contact->created_at)->format('Y/m/d H:i')}}</td>
        <td class="px-6 py-4 text-sm text-blue-600 flex items-center gap-2">
            <button type="button"
                    class="text-indigo-600 hover:text-indigo-800"
                    title="افزودن به لیست پیامک"
                    onclick="openSmsListModal({{ $contact->id }}, '{{ addslashes(trim(($contact->first_name ?? '').' '.($contact->last_name ?? ''))) }}')">
                <i class="fas fa-envelope ml-1"></i>
            </button>
            <a href="{{ route('sales.contacts.edit', $contact->id) }}" class="hover:underline">
                <i class="fas fa-edit ml-1"></i> ویرایش
            </a>
            {{-- فرم حذف تکی کاملاً جدا از فرم bulk-delete --}}
            <form method="POST" action="{{ route('sales.contacts.destroy', $contact->id) }}" onsubmit="return confirm('آیا از حذف این مخاطب مطمئن هستید؟');" class="inline-block">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-600 hover:underline ml-2">
                    <i class="fas fa-trash-alt ml-1"></i> حذف
                </button>
            </form>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="px-6 py-4 text-center text-gray-400">
            هیچ مخاطبی یافت نشد.
        </td>
    </tr>
@endforelse

