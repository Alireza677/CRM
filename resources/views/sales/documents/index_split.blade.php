@extends('layouts.app')

@section('content')


    <div class="p-6 bg-white rounded shadow">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h1 class="text-xl font-bold">اسناد</h1>
            <div class="flex items-center gap-3">
                <form method="GET" action="{{ route('sales.documents.index') }}" class="flex items-center gap-2 text-sm">
                    <span class="text-gray-600">فیلتر:</span>
                    <div class="flex border border-gray-200 rounded-full overflow-hidden">
                        <button type="submit" name="status" value="all"
                                class="px-3 py-1 {{ $statusFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
                            همه
                        </button>
                        <button type="submit" name="status" value="active"
                                class="px-3 py-1 border-l border-gray-200 {{ $statusFilter === 'active' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
                            فقط فعال
                        </button>
                        <button type="submit" name="status" value="voided"
                                class="px-3 py-1 border-l border-gray-200 {{ $statusFilter === 'voided' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
                            فقط باطل شده
                        </button>
                    </div>
                </form>
                <a href="{{ route('sales.documents.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                    + افزودن سند جدید
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="order-2 md:order-1">
                <h2 class="font-semibold text-gray-800 mb-3">اسناد سفارش‌های خرید</h2>

                @if ($purchaseOrderDocs->count())
                    <ul class="space-y-2">
                        @foreach ($purchaseOrderDocs as $doc)
                            @php
                                $isAdmin = auth()->check() && (
                                    (method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('admin'))
                                    || (auth()->user()->is_admin ?? false)
                                );
                                $isOwner = auth()->check() && (int) ($doc->user_id ?? 0) === (int) auth()->id();
                                $canManage = $isAdmin || $isOwner;
                                $isVoided = (bool) ($doc->is_voided ?? false);

                                $confirmVoidMsg = $isVoided
                                    ? 'ابطال این سند لغو شود؟'
                                    : 'آیا مطمئن هستید این سند باطل شود؟ سند حذف نخواهد شد.';

                                $confirmDeleteMsg = 'آیا از حذف این سند اطمینان دارید؟';
                            @endphp

                            <li class="flex justify-between items-start border rounded p-3 hover:shadow-sm bg-white {{ $isVoided ? 'border-dashed border-red-200 bg-gray-50 opacity-80' : '' }}">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <div class="font-medium text-gray-800">{{ $doc->title ?? 'بدون عنوان' }}</div>

                                        @if($isVoided)
                                            <span class="text-xs px-2 py-0.5 rounded-full bg-red-50 text-red-600 border border-red-200">باطل شده</span>
                                        @endif
                                    </div>

                                    <div class="text-sm text-gray-500">
                                        @if ($doc->purchaseOrder)
                                            مربوط به سفارش خرید:
                                            <a href="{{ route('inventory.purchase-orders.show', $doc->purchaseOrder->id) }}" class="text-blue-600 hover:underline">
                                                {{ $doc->purchaseOrder->po_number ?? ('#'.$doc->purchaseOrder->id) }}
                                            </a>
                                        @else
                                            بدون ارتباط با سفارش خرید
                                        @endif
                                    </div>

                                    <div class="text-xs text-gray-400">
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

                                    @if($canManage)
                                        <form action="{{ route('sales.documents.toggle-void', $doc) }}"
                                            method="POST"
                                            class="inline"
                                            onsubmit="return confirm(@js($confirmVoidMsg));">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit"
                                                    class="{{ $isVoided ? 'text-amber-600 hover:text-amber-700' : 'text-gray-600 hover:text-gray-800' }}"
                                                    title="{{ $isVoided ? 'رفع ابطال' : 'باطل کردن' }}">
                                                <i class="fas {{ $isVoided ? 'fa-undo-alt' : 'fa-ban' }}"></i>
                                            </button>
                                        </form>

                                        <form action="{{ route('sales.documents.destroy', $doc) }}"
                                            method="POST"
                                            class="inline"
                                            onsubmit="return confirm(@js($confirmDeleteMsg));">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="text-red-600 hover:text-red-900" title="حذف">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    @endif
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
                @php
                    $isAdmin = auth()->check() && (
                        (method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('admin'))
                        || (auth()->user()->is_admin ?? false)
                    );

                    $isOwner = auth()->check() && (int) ($doc->user_id ?? 0) === (int) auth()->id();
                    $canManage = $isAdmin || $isOwner;
                    $isVoided = (bool) ($doc->is_voided ?? false);

                    $confirmVoidMsg = $isVoided
                        ? 'ابطال این سند لغو شود؟'
                        : 'آیا مطمئن هستید این سند باطل شود؟ سند حذف نخواهد شد.';

                    $confirmDeleteMsg = 'آیا از حذف این سند اطمینان دارید؟';
                @endphp

                <li class="flex justify-between items-start border rounded p-3 hover:shadow-sm bg-white {{ $isVoided ? 'border-dashed border-red-200 bg-gray-50 opacity-80' : '' }}">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <div class="font-medium text-gray-800">{{ $doc->title ?? 'بدون عنوان' }}</div>

                            @if($isVoided)
                                <span class="text-xs px-2 py-0.5 rounded-full bg-red-50 text-red-600 border border-red-200">باطل شده</span>
                            @endif
                        </div>

                        <div class="text-sm text-gray-500">
                            @if ($doc->opportunity)
                                مربوط به فرصت فروش:
                                <a href="{{ route('sales.opportunities.show', $doc->opportunity->id) }}" class="text-blue-600 hover:underline">
                                    {{ $doc->opportunity->name ?? $doc->opportunity->title ?? ('#'.$doc->opportunity->id) }}
                                </a>
                            @else
                                بدون ارتباط با فرصت فروش
                            @endif
                        </div>

                        <div class="text-xs text-gray-400">
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

                        @if($canManage)
                            <form action="{{ route('sales.documents.toggle-void', $doc) }}"
                                  method="POST"
                                  class="inline"
                                  onsubmit="return confirm(@js($confirmVoidMsg));">
                                @csrf
                                @method('PATCH')

                                <button type="submit"
                                        class="{{ $isVoided ? 'text-amber-600 hover:text-amber-700' : 'text-gray-600 hover:text-gray-800' }}"
                                        title="{{ $isVoided ? 'رفع ابطال' : 'باطل کردن' }}">
                                    <i class="fas {{ $isVoided ? 'fa-undo-alt' : 'fa-ban' }}"></i>
                                </button>
                            </form>

                            <form action="{{ route('sales.documents.destroy', $doc) }}"
                                  method="POST"
                                  class="inline"
                                  onsubmit="return confirm(@js($confirmDeleteMsg));">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="text-red-600 hover:text-red-900" title="حذف">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        @endif
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
