@forelse ($organizations as $organization)
    <tr>
        <td class="px-3 py-2">
            <input type="checkbox" name="selected[]" value="{{ $organization->id }}">
        </td>
        <td class="px-3 py-2 whitespace-nowrap">
            {{ $organization->organization_number ?? '-' }}
        </td>
        <td class="px-3 py-2 whitespace-nowrap">
            <a href="{{ route('sales.organizations.show', $organization) }}" class="text-blue-600 hover:text-blue-800">
                {{ $organization->name }}
            </a>
        </td>
        <td class="px-3 py-2 whitespace-nowrap">
            @php($contact = $organization->contacts->first())
            @if($contact)
                <a href="{{ route('sales.contacts.show', $contact->id) }}" class="text-blue-600 hover:text-blue-800">
                    {{ $contact->full_name }}
                </a>
            @else
                -
            @endif
        </td>
        <td class="px-3 py-2">{{ $organization->phone }}</td>
        <td class="px-3 py-2">{{ $organization->city }}</td>
        <td class="px-3 py-2 whitespace-nowrap">{{ $organization->assigned_to_name }}</td>
        <td class="px-3 py-2 whitespace-nowrap text-sm">
            <div class="flex gap-4">
                <a href="{{ route('sales.organizations.edit', $organization) }}" class="text-indigo-600 hover:text-indigo-900">ویرایش</a>
                <form action="{{ route('sales.organizations.destroy', $organization) }}" method="POST" class="inline"
                      onsubmit="return confirm('آیا از حذف این سازمان مطمئن هستید؟')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:text-red-900">حذف</button>
                </form>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="px-6 py-4 text-center text-gray-400">هیچ سازمانی یافت نشد.</td>
    </tr>
@endforelse

