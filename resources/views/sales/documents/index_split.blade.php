@extends('layouts.app')

@section('content')
    <div class="p-6 bg-white rounded shadow">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-bold">اسناد</h1>
            <a href="{{ route('sales.documents.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                + افزودن سند جدید
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="order-2 md:order-1">
                <h2 class="font-semibold text-gray-800 mb-3">اسناد سفارش‌های خرید</h2>

                @if ($purchaseOrderDocs->count())
                    <ul class="space-y-2">
                        @foreach ($purchaseOrderDocs as $doc)
                            <li class="flex justify-between items-center border rounded p-3 hover:shadow-sm bg-white">
                                <div>
                                    <div class="font-medium text-gray-800">{{ $doc->title ?? 'بدون عنوان' }}</div>
                                    <div class="text-sm text-gray-500 mt-1">
                                        @if ($doc->purchaseOrder)
                                            مربوط به سفارش خرید:
                                            <a href="{{ route('inventory.purchase-orders.show', $doc->purchaseOrder->id) }}" class="text-blue-600 hover:underline">
                                                {{ $doc->purchaseOrder->po_number ?? ('#'.$doc->purchaseOrder->id) }}
                                            </a>
                                        @else
                                            بدون ارتباط با سفارش خرید
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-400 mt-1">
                                        <i class="far fa-calendar-alt mr-1"></i>
                                        تاریخ بارگذاری:
                                        @if (class_exists(\Morilog\Jalali\Jalalian::class))
                                            {{ \Morilog\Jalali\Jalalian::fromCarbon($doc->created_at)->format('Y/m/d H:i') }}
                                        @else
                                            {{ $doc->created_at->format('Y/m/d H:i') }}
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 text-sm">
                                    <a href="{{ route('sales.documents.view', $doc) }}" target="_blank" title="نمایش">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('sales.documents.download', $doc) }}" title="دانلود">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-4">
                        {{ $purchaseOrderDocs->withQueryString()->links() }}
                    </div>
                @else
                    <p class="text-gray-500">سندی برای سفارش‌های خرید یافت نشد.</p>
                @endif
            </div>

            <div class="order-1 md:order-2">
                <h2 class="font-semibold text-gray-800 mb-3">اسناد فرصت‌های فروش</h2>

                @if ($opportunityDocs->count())
                    <ul class="space-y-2">
                        @foreach ($opportunityDocs as $doc)
                            <li class="flex justify-between items-center border rounded p-3 hover:shadow-sm bg-white">
                                <div>
                                    <div class="font-medium text-gray-800">{{ $doc->title ?? 'بدون عنوان' }}</div>
                                    <div class="text-sm text-gray-500 mt-1">
                                        @if ($doc->opportunity)
                                            مربوط به فرصت فروش:
                                            <a href="{{ route('sales.opportunities.show', $doc->opportunity->id) }}" class="text-blue-600 hover:underline">
                                                {{ $doc->opportunity->name ?? $doc->opportunity->title ?? ('#'.$doc->opportunity->id) }}
                                            </a>
                                        @else
                                            بدون ارتباط با فرصت فروش
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-400 mt-1">
                                        <i class="far fa-calendar-alt mr-1"></i>
                                        تاریخ بارگذاری:
                                        @if (class_exists(\Morilog\Jalali\Jalalian::class))
                                            {{ \Morilog\Jalali\Jalalian::fromCarbon($doc->created_at)->format('Y/m/d H:i') }}
                                        @else
                                            {{ $doc->created_at->format('Y/m/d H:i') }}
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 text-sm">
                                    <a href="{{ route('sales.documents.view', $doc) }}" target="_blank" title="نمایش">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('sales.documents.download', $doc) }}" title="دانلود">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-4">
                        {{ $opportunityDocs->withQueryString()->links() }}
                    </div>
                @else
                    <p class="text-gray-500">سندی برای فرصت‌های فروش یافت نشد.</p>
                @endif
            </div>
        </div>
    </div>
@endsection

