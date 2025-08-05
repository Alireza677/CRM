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

            <!-- Search and Filters -->
<div class="mb-4">
    <form action="{{ route('sales.proformas.index') }}" method="GET" class="flex flex-wrap items-center gap-2 text-xs">

        <!-- جستجو در موضوع یا سازمان -->
        <input type="text"
            name="search"
            class="border rounded px-3 py-1 w-full sm:w-64"
            placeholder="جستجو در موضوع یا نام سازمان..."
            value="{{ request('search') }}"
        >

        <!-- فیلتر سازمان -->
        <select name="organization_id" class="border rounded px-2 py-1">
            <option value="">همه سازمان‌ها</option>
            @foreach($organizations as $org)
                <option value="{{ $org->id }}" {{ request('organization_id') == $org->id ? 'selected' : '' }}>
                    {{ $org->name }}
                </option>
            @endforeach
        </select>

        <!-- فیلتر مرحله -->
        <select name="stage" class="border rounded px-2 py-1">
            <option value="">همه مراحل</option>
            @foreach(config('proforma.stages') as $key => $label)
                <option value="{{ $key }}" {{ request('stage') == $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>

        <!-- فیلتر ارجاع به -->
        <select name="assigned_to" class="border rounded px-2 py-1">
            <option value="">ارجاع به</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>

        <!-- دکمه جستجو -->
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            جستجو
        </button>

        <!-- دکمه بازنشانی -->
        <a href="{{ route('sales.proformas.index') }}" class="bg-gray-300 text-gray-800 px-3 py-2 rounded hover:bg-gray-400">
            بازنشانی
        </a>

    </form>
</div>


            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 text-xs">
                <table class="min-w-full divide-y divide-gray-200 text-[13px]">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">
                                <input type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-200">
                            </th>
                            <th class="px-6 py-3 text-right text-xs text-gray-500 uppercase">شماره</th>
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

                        @foreach($proformas as $proforma)
                            <tr>
                                <td class="px-6 py-4">
                                    <input type="checkbox">
                                </td>

                                <td class="px-6 py-4 font-mono text-sm text-gray-700">
                                    {{ $proforma->proforma_number ?? '-' }}
                                </td>

                                <td class="px-6 py-4">
                                    <a href="{{ route('sales.proformas.show', $proforma) }}" class="text-blue-600 hover:text-blue-900">
                                        {{ $proforma->subject }}
                                    </a>
                                </td>

                                <td class="px-6 py-4">
                                    @php
                                        $stageKey = $proforma->proforma_stage ?? 'unknown';
                                        $stageClass = $stageColors[$stageKey] ?? 'bg-gray-100 text-gray-800';
                                        $stageLabel = config('proforma.stages.' . $stageKey) ?? $stageKey;
                                    @endphp
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $stageClass }}">
                                        {{ $stageLabel }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">{{ $proforma->organization_name ?? '-' }}</td>
                                <td class="px-6 py-4">{{ $proforma->contact_name ?? '-' }}</td>
                                <td class="px-6 py-4">{{ number_format($proforma->total_amount) }} تومان</td>
                                <td class="px-6 py-4">
                                    {{ $proforma->proforma_date ? \Carbon\Carbon::parse($proforma->proforma_date)->format('Y/m/d') : '-' }}
                                </td>
                                <td class="px-6 py-4">{{ $proforma->opportunity->name ?? '-' }}</td>    
                                <td class="px-6 py-4">{{ $proforma->assignedTo->name ?? '-' }}</td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-reverse space-x-3">
                                        <a href="{{ route('sales.proformas.show', $proforma) }}" class="text-blue-600 hover:text-blue-900">مشاهده</a>

                                        @if($proforma->proforma_stage === 'send_for_approval')
                                            <button onclick="showApprovalAlert()" class="text-gray-500 cursor-not-allowed" disabled>ویرایش</button>
                                            <button onclick="showApprovalAlert()" class="text-gray-500 cursor-not-allowed" disabled>حذف</button>
                                        @else
                                            <a href="{{ route('sales.proformas.edit', $proforma) }}" class="text-indigo-600 hover:text-indigo-900">ویرایش</a>

                                            <form action="{{ route('sales.proformas.destroy', $proforma) }}" method="POST" onsubmit="return confirm('آیا از حذف این پیش‌فاکتور اطمینان دارید؟')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">حذف</button>
                                            </form>
                                        @endif
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

<script>
    function showApprovalAlert() {
        Swal.fire({
            icon: 'warning',
            title: 'عملیات غیرمجاز',
            text: 'پیش‌فاکتور در وضعیت تایید است. ویرایش یا حذف آن امکان‌پذیر نیست.',
            confirmButtonText: 'باشه'
        });
    }
</script>
