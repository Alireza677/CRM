@extends('layouts.app')

@section('header')
    <div class="flex flex-col max-w-7xl gap-2 md:flex-row md:items-center md:justify-center">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800">فرم‌های خدمات پس از فروش</h2>
            <p class="text-sm text-gray-500 mt-1">مدیریت درخواست‌های مشتریان برای رسیدگی سریع‌تر.</p>
        </div>
        <a href="{{ route('support.after-sales-services.create') }}"
           class="inline-flex items-center justify-center px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">
            + ثبت فرم جدید
        </a>
    </div>
@endsection

@section('content')
    <div class="py-8" dir="rtl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100">
                <div class="p-6 border-b border-gray-100">
                    <form method="GET" action="{{ route('support.after-sales-services.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="جستجو بر اساس نام مشتری، هماهنگ‌کننده یا شرح مشکل..."
                            class="flex-1 rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        >
                        <button type="submit"
                                class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50">
                            جستجو
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-right">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3">نام مشتری</th>
                                <th class="px-6 py-3">هماهنگ‌کننده</th>
                                <th class="px-6 py-3">شماره تماس</th>
                                <th class="px-6 py-3">تاریخ ثبت</th>
                                <th class="px-6 py-3 text-center">عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                            @forelse ($services as $service)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        {{ $service->customer_name }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $service->coordinator_name }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span dir="ltr" class="font-mono text-sm tracking-wide">{{ $service->coordinator_mobile }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500">
                                        {{ $service->created_at ? jdate($service->created_at)->format('Y/m/d H:i') : '' }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="inline-flex items-center gap-3 text-sm">
                                            <a href="{{ route('support.after-sales-services.show', $service) }}"
                                               class="text-blue-600 hover:text-blue-800">مشاهده</a>
                                            <span class="text-gray-300">|</span>
                                            <a href="{{ route('support.after-sales-services.edit', $service) }}"
                                               class="text-amber-600 hover:text-amber-800">ویرایش</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-6 text-center text-gray-500">
                                        هنوز فرمی ثبت نشده است.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $services->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
