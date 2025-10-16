@extends('layouts.app')

@php
    $breadcrumb = [
        ['title' => 'فرصت‌های فروش']
    ];

    // رنگ هر مرحله (پس‌زمینه ملایم + رنگ متن)
    $stageColors = [
        'جدید'            => 'bg-blue-100 text-blue-700',
        'در حال پیگیری'   => 'bg-yellow-100 text-yellow-700',
        'پیگیری در آینده' => 'bg-orange-100 text-orange-700',
        'برنده'           => 'bg-green-100 text-green-700',   // ✅ سبز
        'بازنده'          => 'bg-gray-100 text-gray-700',
        'سرکاری'          => 'bg-red-100 text-red-700',
        'ارسال پیش فاکتور'=> 'bg-purple-100 text-purple-700',
    ];
@endphp

@section('content')
<div class="py-6 px-4 sm:px-6 lg:px-8">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">
        فرصت‌های فروش
    </h2>

    <a href="{{ route('sales.opportunities.create') }}" 
       class="mb-4 inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
        + فرصت جدید
    </a>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 py-2 text-right text-gray-600">عنوان</th>
                        <th class="px-2 py-2 text-right text-gray-600">مخاطب</th>
                        <th class="px-2 py-2 text-right text-gray-600">مرحله فروش</th>
                        <th class="px-2 py-2 text-right text-gray-600">منبع فرصت فروش</th>
                        <th class="px-2 py-2 text-right text-gray-600">ارجاع به</th>
                        <th class="px-2 py-2 text-right text-gray-600">تاریخ ایجاد</th>
                        <th class="px-2 py-2 text-right text-gray-600">عملیات</th>
                    </tr>
                    <tr>
                        <form method="GET" action="{{ route('sales.opportunities.index') }}">
                            <th class="px-2 py-1">
                                <input type="text" name="name" value="{{ request('name') }}"
                                    class="w-full px-2 py-1 border rounded text-sm" placeholder="جستجوی عنوان">
                            </th>
                            <th class="px-2 py-1">
                                <input type="text" name="contact" value="{{ request('contact') }}"
                                    class="w-full px-2 py-1 border rounded text-sm" placeholder="نام مخاطب">
                            </th>
                            <th class="px-2 py-1">
                                <select name="stage" class="w-full px-2 py-1 border rounded text-sm">
                                    <option value="">همه</option>
                                    <option value="در حال پیگیری" {{ request('stage') == 'در حال پیگیری' ? 'selected' : '' }}>در حال پیگیری</option>
                                    <option value="پیگیری در آینده" {{ request('stage') == 'پیگیری در آینده' ? 'selected' : '' }}>پیگیری در آینده</option>
                                    <option value="برنده" {{ request('stage') == 'برنده' ? 'selected' : '' }}>برنده</option>
                                    <option value="بازنده" {{ request('stage') == 'بازنده' ? 'selected' : '' }}>بازنده</option>
                                    <option value="سرکاری" {{ request('stage') == 'سرکاری' ? 'selected' : '' }}>سرکاری</option>
                                    <option value="ارسال پیش فاکتور" {{ request('stage') == 'ارسال پیش فاکتور' ? 'selected' : '' }}>ارسال پیش فاکتور</option>
                                </select>
                            </th>
                            <th class="px-2 py-1">
                                <select name="source" class="w-full px-2 py-1 border rounded text-sm">
                                    <option value="">همه</option>
                                    <option value="وب سایت" {{ request('source') == 'وب سایت' ? 'selected' : '' }}>وب سایت</option>
                                    <option value="مشتریان قدیمی" {{ request('source') == 'مشتریان قدیمی' ? 'selected' : '' }}>مشتریان قدیمی</option>
                                    <option value="نمایشگاه" {{ request('source') == 'نمایشگاه' ? 'selected' : '' }}>نمایشگاه</option>
                                    <option value="بازاریابی حضوری" {{ request('source') == 'بازاریابی حضوری' ? 'selected' : '' }}>بازاریابی حضوری</option>
                                </select>
                            </th>
                            <th class="px-2 py-1">
                                <input type="text" name="assigned_to" value="{{ request('assigned_to') }}"
                                    class="w-full px-2 py-1 border rounded text-sm" placeholder="ارجاع به">
                            </th>
                            <th class="px-2 py-1 text-center">
                                <button type="submit" class="text-sm text-blue-600 hover:underline">جستجو</button>
                            </th>
                            <th class="px-2 py-1 text-center">
                                <a href="{{ route('sales.opportunities.index') }}" class="text-sm text-gray-500 hover:text-red-500">
                                    پاک‌سازی
                                </a>
                            </th>
                        </form>
                    </tr>
                </thead>

                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($opportunities as $opportunity)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('sales.opportunities.show', $opportunity) }}" class="text-blue-600 hover:text-blue-900">
                                    {{ $opportunity->name ?? '-' }}
                                </a>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $opportunity->contact->name ?? '—' }}
                            </td>

                            {{-- ⭐ مرحله فروش به صورت بادج رنگی --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $stage = $opportunity->stage ?? '—';
                                    $badgeClass = $stageColors[$stage] ?? 'bg-gray-100 text-gray-700';
                                @endphp
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $badgeClass }}">
                                    {{ $stage }}
                                </span>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $opportunity->source ?? '—' }}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $opportunity->assignedTo->name ?? '—' }}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ jdate($opportunity->created_at) }}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex gap-4">
                                    <a href="{{ route('sales.opportunities.edit', $opportunity) }}" class="text-indigo-600 hover:text-indigo-900">ویرایش</a>
                                    <form action="{{ route('sales.opportunities.destroy', $opportunity) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="text-red-600 hover:text-red-900"
                                                onclick="return confirm('آیا از حذف این فرصت فروش اطمینان دارید؟')">
                                            حذف
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-400">
                                هیچ فرصتی یافت نشد.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $opportunities->links() }}
    </div>
</div>
@endsection
