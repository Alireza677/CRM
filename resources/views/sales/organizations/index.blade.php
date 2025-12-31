@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [['title' => 'سازمان‌ها']];
    $sort = request('sort', 'created_at');
    $direction = request('direction', 'desc');
    $opposite = $direction === 'asc' ? 'desc' : 'asc';
@endphp

<div class="py-6 px-4 sm:px-6 lg:px-8">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">لیست سازمان‌ها</h2>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
        <form method="GET" action="{{ route('sales.organizations.index') }}" class="flex items-center gap-2">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="جستجوی نام، تلفن یا ..."
                   class="w-64 px-2 py-2 border rounded text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">جستجو</button>
        </form>

        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route('sales.organizations.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="sort" value="{{ request('sort', 'created_at') }}">
                <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">

                @php($currentPerPage = isset($perPage) ? (int)$perPage : (int)request('per_page', session('orgs_per_page', 10)))
                <label for="per_page" class="text-sm text-gray-700 whitespace-nowrap">تعداد در صفحه</label>
                <select id="per_page" name="per_page" onchange="this.form.submit()"
                        class="border rounded py-1 px-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach([10,25,50,100,250] as $size)
                        <option value="{{ $size }}" {{ $currentPerPage === $size ? 'selected' : '' }}>{{ $size }}</option>
                    @endforeach
                </select>
            </form>

            <a href="{{ route('sales.organizations.create') }}"
               class="inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 text-sm">+ سازمان جدید</a>
            <a href="{{ route('sales.organizations.duplicates.index') }}"
               class="inline-block bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 text-sm">یافتن موارد تکراری</a>

            <a href="{{ route('sales.organizations.import.form') }}"
               class="inline-block bg-emerald-500 text-white px-4 py-2 rounded hover:bg-emerald-600 text-sm">درون‌ریزی از Excel</a>
        </div>
    </div>

    <form method="POST" action="{{ route('sales.organizations.bulkDelete') }}" id="bulk-delete-form" class="mb-0">
        @csrf
        @method('DELETE')

        <div class="mb-3">
            <button type="submit" onclick="return confirm('آیا از حذف گروهی مطمئن هستید؟')"
                    class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-sm">حذف گروهی</button>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm text-right">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2">
                                <input type="checkbox" id="select-all">
                            </th>
                            <th class="px-3 py-2 text-gray-600">
                                <a href="{{ route('sales.organizations.index', ['sort' => 'name', 'direction' => $sort === 'name' ? $opposite : 'asc']) }}" class="hover:text-gray-900">
                                    نام سازمان
                                    @if ($sort === 'name')
                                        {!! $direction === 'asc' ? '&#9650;' : '&#9660;' !!}
                                    @endif
                                </a>
                            </th>
                            <th class="px-3 py-2 text-gray-600"> مخاطب مرتبط </th>
                            <th class="px-3 py-2 text-gray-600">تلفن</th>
                            <th class="px-3 py-2 text-gray-600">شهر</th>
                            <th class="px-3 py-2 text-gray-600">
                                <a href="{{ route('sales.organizations.index', ['sort' => 'assigned_to_name', 'direction' => $sort === 'assigned_to_name' ? $opposite : 'asc']) }}" class="hover:text-gray-900">
                                    مسئول
                                    @if ($sort === 'assigned_to_name')
                                        {!! $direction === 'asc' ? '&#9650;' : '&#9660;' !!}
                                    @endif
                                </a>
                            </th>
                            <th class="px-3 py-2 text-gray-600">اقدامات</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($organizations as $organization)
                            <tr>
                                <td class="px-3 py-2">
                                    <input type="checkbox" name="selected[]" value="{{ $organization->id }}">
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
                                <td colspan="7" class="px-6 py-4 text-center text-gray-400">هیچ سازمانی یافت نشد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    <div class="mt-4">
        {{ $organizations->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function(){
        var sa = document.getElementById('select-all');
        if (sa) {
            sa.addEventListener('change', function () {
                var checkboxes = document.querySelectorAll('input[name="selected[]"]');
                checkboxes.forEach(function(cb){ cb.checked = sa.checked; });
            });
        }
    })();
</script>
@endpush
