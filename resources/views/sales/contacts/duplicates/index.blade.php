@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'مخاطبین', 'url' => route('sales.contacts.index')],
        ['title' => 'یافتن موارد تکراری'],
    ];
@endphp

<div class="py-6">
    <div class="w-full max-w-none px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">مخاطبین تکراری</h2>
                <p class="text-sm text-gray-500 mt-1">تعداد گروه‌های تکراری: {{ $groups->total() }}</p>
            </div>

            <form method="POST" action="{{ route('sales.contacts.duplicates.scan') }}" class="flex flex-wrap items-center gap-4">
                @csrf
                @php
                    $selectedMatchKeys = old('match_keys', []);
                @endphp
                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-700">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="match_keys[]" value="mobile" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            {{ in_array('mobile', (array) $selectedMatchKeys, true) ? 'checked' : '' }}>
                        <span>موبایل</span>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="match_keys[]" value="province" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            {{ in_array('province', (array) $selectedMatchKeys, true) ? 'checked' : '' }}>
                        <span>استان</span>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="match_keys[]" value="organization" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            {{ in_array('organization', (array) $selectedMatchKeys, true) ? 'checked' : '' }}>
                        <span>سازمان</span>
                    </label>
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-sm">
                    اسکن موارد تکراری
                </button>
            </form>
        </div>

        @if(session('success'))
            <div class="mb-4 p-3 rounded bg-green-50 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 text-gray-900 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">کلید تطبیق</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">مقدار تطبیق</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">ارجاع به</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">تعداد موارد</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">آخرین بروزرسانی</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">عملیات</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($groups as $group)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $group->match_key }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $group->match_value }}</td>
                                <td class="px-4 py-3 text-sm {{ !empty($group->assignees_is_multiple) ? 'text-red-600 font-semibold' : 'text-gray-900' }}">
                                    {{ $group->assignees_label ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $group->items_count }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $group->updated_at }}</td>
                                <td class="px-4 py-3 text-sm text-blue-600">
                                    <a href="{{ route('sales.contacts.duplicates.review', $group) }}" class="hover:underline">
                                        بررسی و ادغام
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">
                                    هیچ گروه تکراری پیدا نشد. برای ساخت گروه‌ها، یک بار اسکن انجام دهید.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $groups->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
