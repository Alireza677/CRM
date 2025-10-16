@extends('layouts.app')

@section('content')
<div class="py-12" dir="rtl">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">

                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800">
                        جزئیات محصول: {{ $product->name }}
                    </h2>
                    <div class="flex gap-2">
                        <a href="{{ route('inventory.products.edit', $product) }}" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700">
                            ویرایش
                        </a>
                        <a href="{{ route('inventory.products.index') }}" class="inline-flex items-center px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">
                            بازگشت
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gray-50 p-4 rounded-lg space-y-3">
                        <div>
                            <div class="text-sm text-gray-500">نام محصول</div>
                            <div class="text-gray-900 font-medium">{{ $product->name }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">دسته‌بندی</div>
                            <div class="text-gray-900">{{ optional($product->category)->name ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">تامین‌کننده</div>
                            <div class="text-gray-900">{{ optional($product->supplier)->name ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">قیمت واحد</div>
                            <div class="text-gray-900">{{ number_format($product->unit_price) }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">وضعیت</div>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $product->is_active ? 'فعال' : 'غیرفعال' }}
                            </span>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg space-y-3">
                        <div>
                            <div class="text-sm text-gray-500">وب‌سایت</div>
                            <div class="text-gray-900">
                                @if($product->website)
                                    <a class="text-blue-600 hover:underline" href="{{ $product->website }}" target="_blank" rel="noopener">{{ $product->website }}</a>
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">شماره قطعه</div>
                            <div class="text-gray-900">{{ $product->part_number ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">سری</div>
                            <div class="text-gray-900">{{ $product->series ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">سازنده</div>
                            <div class="text-gray-900">{{ $product->manufacturer ?? '—' }}</div>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <div class="text-sm text-gray-500">طول</div>
                                <div class="text-gray-900">{{ $product->length ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">نوع</div>
                                <div class="text-gray-900">{{ $product->type ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">مالیات</div>
                                <div class="text-gray-900">{{ $product->has_vat ? 'دارد' : 'ندارد' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <div class="bg-gray-50 p-4 rounded-lg grid grid-cols-2 gap-3">
                        <div>
                            <div class="text-sm text-gray-500">تاریخ شروع فروش</div>
                            <div class="text-gray-900">{{ optional($product->sales_start_date)->format('Y-m-d') ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">تاریخ پایان فروش</div>
                            <div class="text-gray-900">{{ optional($product->sales_end_date)->format('Y-m-d') ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">شروع پشتیبانی</div>
                            <div class="text-gray-900">{{ optional($product->support_start_date)->format('Y-m-d') ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">پایان پشتیبانی</div>
                            <div class="text-gray-900">{{ optional($product->support_end_date)->format('Y-m-d') ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg grid grid-cols-2 gap-3">
                        <div>
                            <div class="text-sm text-gray-500">توان حرارتی (kW)</div>
                            <div class="text-gray-900">{{ $product->thermal_power ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">کمیسیون (%)</div>
                            <div class="text-gray-900">{{ $product->commission ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">هزینه خرید</div>
                            <div class="text-gray-900">{{ $product->purchase_cost ?? '—' }}</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    </div>
</div>
@endsection

