@extends('layouts.app')

@section('content')
    @php
        $breadcrumb = [
            ['title' => 'پیش‌فاکتورها']
        ];
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">
                {{ __('پیش‌فاکتورها') }}
            </h2>

            <!-- Create New Proforma Button -->
            <div class="mb-4">
                <a href="{{ route('sales.proformas.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i> ایجاد پیش‌فاکتور
                </a>
            </div>

            <!-- Search Bar -->
            <div class="mb-4">
                <form action="{{ route('sales.proformas.index') }}" method="GET" class="flex items-center gap-2">
                    <input type="text"
                        name="search"
                        class="w-full"
                        placeholder="{{ __('جستجو در موضوع یا نام سازمان...') }}"
                        value="{{ request('search') }}"
                    >
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">{{ __('جستجو') }}</button>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">
                                    <input type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-200">
                                </th>
                                <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">موضوع</th>
                                <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">مرحله</th>
                                <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">سازمان</th>
                                <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">مخاطب</th>
                                <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">مبلغ کل</th>
                                <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">تاریخ پیش‌فاکتور</th>
                                <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">فرصت</th>
                                <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">ارجاع به</th>
                                <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($proformas as $proforma)
                                <tr>
                                    <td class="px-6 py-4">
                                        <input type="checkbox">
                                    </td>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('sales.proformas.show', $proforma) }}" class="text-blue-600 hover:text-blue-900">
                                            {{ $proforma->subject }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $stageColors = [
                                                'created' => 'bg-blue-100 text-blue-800',
                                                'accepted' => 'bg-green-100 text-green-800',
                                                'delivered' => 'bg-purple-100 text-purple-800',
                                                'rejected' => 'bg-red-100 text-red-800',
                                                'expired' => 'bg-gray-100 text-gray-800'
                                            ];
                                            $stageLabels = [
                                                'created' => 'ایجاد شده',
                                                'accepted' => 'تایید شده',
                                                'delivered' => 'تحویل شده',
                                                'rejected' => 'رد شده',
                                                'expired' => 'منقضی شده'
                                            ];
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $stageColors[$proforma->stage] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $stageLabels[$proforma->stage] ?? $proforma->stage }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">{{ $proforma->organization_name }}</td>
                                    <td class="px-6 py-4">{{ $proforma->contact_name }}</td>
                                    <td class="px-6 py-4">{{ number_format($proforma->total_amount) }} تومان</td>
                                    <td class="px-6 py-4">{{ $proforma->proforma_date ? \Carbon\Carbon::parse($proforma->proforma_date)->format('Y/m/d') : '-' }}</td>
                                    <td class="px-6 py-4">{{ $proforma->opportunity_name }}</td>
                                    <td class="px-6 py-4">{{ $proforma->assigned_to_name }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-reverse space-x-3">
                                            <a href="{{ route('sales.proformas.show', $proforma) }}" class="text-blue-600 hover:text-blue-900">مشاهده</a>
                                            <a href="{{ route('sales.proformas.edit', $proforma) }}" class="text-indigo-600 hover:text-indigo-900">ویرایش</a>
                                            <form action="{{ route('sales.proformas.destroy', $proforma) }}" method="POST" onsubmit="return confirm('آیا از حذف این پیش‌فاکتور اطمینان دارید؟')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">حذف</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $proformas->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
