@extends('layouts.app')

@section('content')

    @php
        $breadcrumb = [
            ['title' => 'جزئیات پیش‌فاکتور']
        ];
    @endphp
    @if(session('alert_error'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'warning',
                title: 'توجه',
                text: '{{ session('alert_error') }}',
                confirmButtonText: 'باشه'
            });
        });
    </script>
@endif

<div class="container py-6" dir="rtl">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($proforma->proforma_stage === 'send_for_approval' && $pendingApproverName)
                    <div class="mt-4 p-4 bg-yellow-100 border border-yellow-400 text-yellow-800 rounded">
                        پیش‌فاکتور در انتظار تایید <strong>{{ $pendingApproverName }}</strong> است.
                    </div>
            @endif

        {{-- عنوان و دکمه‌ها --}}
        <div class="flex justify-between items-center mb-6">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                جزئیات پیش‌فاکتور
            </h2>
            <div class="flex space-x-4">
                <a href="{{ route('sales.proformas.edit', $proforma) }}" class="btn btn-primary">✏️ ویرایش</a>
                <a href="{{ route('sales.proformas.index') }}" class="btn btn-secondary">⬅ بازگشت</a>

            </div>
        </div>

        {{-- پیام‌ها --}}
        @if (session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        {{-- محتوای اصلی --}}
        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- اطلاعات پایه --}}
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold mb-4">اطلاعات پایه</h3>
                    <div><strong>موضوع:</strong> {{ $proforma->subject }}</div>
                    @php
                        use Morilog\Jalali\Jalalian;

                        try {
                            $shamsiDate = ($proforma->proforma_date instanceof \Carbon\Carbon)
                                ? Jalalian::fromCarbon($proforma->proforma_date)->format('Y/m/d')
                                : 'تاریخ نامعتبر';
                        } catch (\Throwable $e) {
                            $shamsiDate = 'تاریخ نامعتبر';
                        }
                    @endphp

                    <div><strong>تاریخ پیش فاکتور:</strong> {{ $shamsiDate }}</div>
                    <div><strong>شماره پیش فاکتور:</strong> {{ $proforma->proforma_number }}</div>
                    <div><strong>مرحله:</strong> 
                        {{ config('proforma.stages.' . $proforma->proforma_stage) ?? 'نامشخص' }}
                    </div>

                </div>

                {{-- اطلاعات تماس --}}
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold mb-4">اطلاعات تماس</h3>
                    <div><strong>نام مخاطب:</strong> {{ $proforma->contact_name }}</div>
                    <div><strong>نام سازمان:</strong> {{ $proforma->organization_name }}</div>
                    <div><strong>ارجاع به:</strong> {{ $proforma->assignedTo?->name }}</div>
                </div>

                {{-- اطلاعات آدرس --}}
                <div class="space-y-4 md:col-span-2">
                    <h3 class="text-lg font-semibold mb-4">اطلاعات آدرس</h3>
                    <div><strong>نوع آدرس:</strong> 
                        {{ $proforma->address_type === 'invoice' ? 'آدرس تحویل صورت‌حساب' : 'آدرس تحویل محصول' }}
                    </div>
                    <div><strong>آدرس:</strong> {{ $proforma->customer_address }}</div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div><strong>شهر:</strong> {{ $proforma->city }}</div>
                        <div><strong>استان:</strong> {{ $proforma->state }}</div>
                    </div>
                </div>

                {{-- اطلاعات محصول --}}
                <div class="space-y-4 md:col-span-2">
                    <h3 class="text-lg font-semibold mb-4">اطلاعات محصول</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-center">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th>نام محصول</th>
                                    <th>تعداد</th>
                                    <th>واحد</th>
                                    <th>قیمت واحد</th>
                                    <th>تخفیف</th>
                                    <th>مالیات</th>
                                    <th>مجموع</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $total_discount = 0;
                                    $total_tax = 0;
                                @endphp
                                @foreach($proforma->items as $item)
                                    @php
                                        $total_discount += $item->discount_amount;
                                        $total_tax += $item->tax_amount;
                                    @endphp
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>
                                            @switch($item->unit_of_use)
                                                @case('device') دستگاه @break
                                                @case('piece') عدد @break
                                                @case('meter') متر @break
                                                @default {{ $item->unit_of_use }}
                                            @endswitch
                                        </td>
                                        <td>{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-red-600">{{ number_format($item->discount_amount, 2) }}</td>
                                        <td class="text-green-600">{{ number_format($item->tax_amount, 2) }}</td>
                                        <td>{{ number_format($item->total_price, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="4" class="font-bold text-right">جمع تخفیف:</td>
                                    <td class="font-bold text-red-600">{{ number_format($total_discount, 2) }}</td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="font-bold text-right">جمع مالیات:</td>
                                    <td class="font-bold text-green-600">{{ number_format($total_tax, 2) }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="font-bold text-right">مجموع کل:</td>
                                    <td class="font-bold">{{ number_format($proforma->total_amount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- دکمه برگشت --}}
        <div class="mt-6 flex justify-end">
            <a href="{{ route('sales.proformas.index') }}" class="btn btn-secondary">
                ⬅ بازگشت به لیست
            </a>
            @php
                $canApprove = false;
                $currentStage = $proforma->proforma_stage;

                $condition = \App\Models\AutomationCondition::where('model_type', 'Proforma')
                    ->where('field', 'proforma_stage')
                    ->where('operator', '=')
                    ->where('value', $currentStage)
                    ->first();

                if ($condition && (auth()->id() == $condition->approver1_id || auth()->id() == $condition->approver2_id)) {
                    $canApprove = true;
                }
            @endphp

            @if ($approval)
                <form action="{{ route('sales.proformas.approve', $proforma) }}" method="POST" onsubmit="return confirm('آیا از تایید پیش‌فاکتور مطمئن هستید؟');">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="btn btn-success mt-4 ml-4">
                        ✅ تایید پیش‌فاکتور
                    </button>
                </form>
            @endif

        </div>
    </div>
</div>

<style>
    .btn {
        display: inline-block;
        font-weight: 500;
        text-align: center;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        border-radius: 0.375rem;
        transition: background-color 0.2s ease-in-out;
    }
    .btn-primary {
        color: white;
        background-color: #2563eb;
    }
    .btn-primary:hover {
        background-color: #1d4ed8;
    }
    .btn-secondary {
        color: white;
        background-color: #6b7280;
    }
    .btn-secondary:hover {
        background-color: #4b5563;
    }
    .btn-success {
    color: white;
    background-color: #16a34a;
    }
    .btn-success:hover {
        background-color: #15803d;
    }

</style>

@endsection

