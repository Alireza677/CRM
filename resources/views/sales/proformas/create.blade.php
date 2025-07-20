@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'پیش‌فاکتورها', 'url' => route('sales.proformas.index')],
        ['title' => 'ایجاد پیش‌فاکتور']
    ];
@endphp

<link rel="stylesheet" href="{{ asset('css/proforma-style.css') }}">

<div class="container py-6 proforma-card" dir="rtl">
    <div class="w-full px-4">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-6">
                    ایجاد پیش‌فاکتور جدید
                </h2>

                {{-- فرم کامل --}}
                <form action="{{ route('sales.proformas.store') }}" method="POST" class="space-y-6" id="proforma-form">
                    @csrf
                    {{-- دسته اول: اطلاعات پیش‌فاکتور --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {{-- subject --}}
                        <div class="form-group">
                            <label for="subject" class="form-label">
                                موضوع <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                            @error('subject')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- تاریخ شمسی و مخفی میلادی --}}
                        <div class="form-group">
                            <label for="proforma_date_shamsi" class="form-label">تاریخ پیش فاکتور</label>
                            <input type="text" class="form-control" id="proforma_date_shamsi" placeholder=" تاریخ را وارد کنید">
                            <input type="hidden" name="proforma_date" id="proforma_date" value="{{ old('proforma_date') }}">
                            @error('proforma_date')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- شماره پیش فاکتور --}}
                        <div class="form-group">
                            <label for="proforma_number" class="form-label">شماره پیش فاکتور</label>
                            <input type="text" class="form-control" id="proforma_number" name="proforma_number">
                            @error('proforma_number')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="proforma_stage" class="block mb-1 font-medium text-gray-700">
                                مرحله پیش‌فاکتور <span class="text-red-600">*</span>
                            </label>
                            <select id="proforma_stage" name="proforma_stage" required class="form-control">
                                <option value="">انتخاب کنید</option>
                                @foreach (\App\Helpers\FormOptionsHelper::proformaStages() as $value => $label)
                                    <option value="{{ $value }}" {{ old('proforma_stage', $proforma->proforma_stage ?? '') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>

                            @error('proforma_stage')
                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    {{-- دسته دوم: مخاطب و فروش --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @foreach([
                            ['contact_name', 'نام مخاطب'],
                            ['organization_name', 'نام سازمان'],
                        ] as [$id, $label])
                            <div class="form-group">
                                <label for="{{ $id }}" class="form-label">{{ $label }}</label>
                                <input type="text" class="form-control" id="{{ $id }}" name="{{ $id }}">
                                @error($id)
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        @endforeach

                        <div class="form-group">
                            <label for="opportunity_id" class="form-label">فرصت فروش</label>
                            <select name="opportunity_id" id="opportunity_id" class="form-control">
                                <option value="">انتخاب کنید</option>
                                @foreach($opportunities as $opportunity)
                                    <option value="{{ $opportunity->id }}">{{ $opportunity->name }}</option>
                                @endforeach
                            </select>
                            @error('opportunity_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="assigned_to" class="form-label">ارجاع به <span class="text-danger">*</span></label>
                            <select class="form-control" id="assigned_to" name="assigned_to" required>
                                <option value="">انتخاب کنید</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            @error('assigned_to')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- دسته سوم: اطلاعات آدرس --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @foreach([
                            ['city', 'شهر'],
                            ['state', 'استان'],
                        ] as [$id, $label])
                            <div class="form-group">
                                <label for="{{ $id }}" class="form-label">{{ $label }}</label>
                                <input type="text" class="form-control" id="{{ $id }}" name="{{ $id }}">
                                @error($id)
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        @endforeach
                    </div>

                    <div class="form-group">
                        <label for="customer_address" class="form-label">آدرس مشتری</label>
                        <textarea class="form-control" id="customer_address" name="customer_address" rows="3"></textarea>
                        @error('customer_address')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">نوع آدرس</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="address_type" id="invoice_address" value="invoice" checked>
                            <label class="form-check-label" for="invoice_address">آدرس تحویل صورت‌حساب</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="address_type" id="product_address" value="product">
                            <label class="form-check-label" for="product_address">آدرس تحویل محصول</label>
                        </div>
                    </div>

                    {{-- اطلاعات محصولات --}}
                    <div class="bg-white p-6 rounded-lg shadow-sm mt-6">
                        <h3 class="text-lg font-semibold mb-4">اطلاعات محصولات</h3>
                        <div id="product-rows-container" class="space-y-6"></div>
                        <div class="flex justify-start mt-4">
                            <button type="button" onclick="openProductModal()" class="btn btn-secondary">انتخاب محصول</button>
                        </div>
                    </div>

                    @include('sales.proformas.partials.product-modal')

                    <div class="mt-6 text-lg font-semibold text-right">
                        جمع کل پیش‌فاکتور: <span id="invoice-total">۰</span> تومان
                    </div>

                    <div class="flex items-center justify-end mt-6">
                        <input type="hidden" name="total_amount" id="total_amount_input" value="0">
                        <a href="{{ route('sales.proformas.index') }}" class="btn btn-secondary ml-4">انصراف</a>
                        <button type="button" id="save-btn" class="btn btn-primary">ذخیره پیش‌فاکتور</button>
                    </div>

                    @if (request('opportunity_id'))
                        <input type="hidden" name="opportunity_id" value="{{ request('opportunity_id') }}">
                    @endif

                    <!-- مودال تأیید ارسال برای تاییدیه -->
                    <div class="modal fade" id="automationConfirmModal" tabindex="-1" aria-labelledby="automationConfirmLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content text-end">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="automationConfirmLabel">تأیید ارسال برای تاییدیه</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                                </div>
                                <div class="modal-body">
                                    مرحله‌ی انتخاب‌شده "ارسال برای تاییدیه" است. آیا مطمئن هستید که می‌خواهید پیش‌فاکتور را ارسال کنید؟
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-success" id="confirm-save">بله، ارسال شود</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">خیر</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')


    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const saveBtn = document.getElementById("save-btn");
            const confirmSave = document.getElementById("confirm-save");
            const form = document.getElementById("proforma-form");
            const stageField = document.getElementById("proforma_stage");

            saveBtn.addEventListener("click", function () {
                const selectedStage = stageField.value;
                if (selectedStage === 'send_for_approval') {
                const modal = new bootstrap.Modal(document.getElementById('automationConfirmModal'));
                modal.show();
                } else {
                    calculateInvoiceTotal();
                    form.submit();
                }
            });

            confirmSave.addEventListener("click", function () {
                calculateInvoiceTotal();
                form.submit();
            });
        });
    </script>

    @include('sales.proformas.partials.product-scripts')
@endpush
