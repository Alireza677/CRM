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

                        {{-- سازمان --}}
                        <div>
                            <label for="organization_id" class="block font-medium text-sm text-gray-700">سازمان</label>
                            <div class="flex items-center gap-2">
                                <input type="text" id="organization_name" name="organization_name"
                                    class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm bg-gray-50 cursor-pointer focus:ring focus:ring-blue-200 focus:border-blue-400"
                                    placeholder="انتخاب سازمان" readonly>
                                <input type="hidden" id="organization_id" name="organization_id">
                                <button type="button" onclick="openOrganizationModal()" class="text-blue-600 text-xl hover:text-blue-800 transition">🔍</button>
                            </div>
                            @error('organization_id')
                                <div class="text-red-500 text-xs mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- مخاطب --}}
                        <div>
                            <label for="contact_id" class="block font-medium text-sm text-gray-700">مخاطب</label>
                            <div class="flex items-center gap-2">
                                <input type="text" id="contact_name" name="contact_name"
                                    class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm bg-gray-50 cursor-pointer focus:ring focus:ring-blue-200 focus:border-blue-400"
                                    placeholder="انتخاب مخاطب" readonly>
                                <input type="hidden" id="contact_id" name="contact_id">
                                <button type="button" onclick="openContactModal()" class="text-blue-600 text-xl hover:text-blue-800 transition">🔍</button>
                            </div>
                            @error('contact_id')
                                <div class="text-red-500 text-xs mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- فرصت فروش --}}
                        <div>
                            <label for="opportunity_id" class="block font-medium text-sm text-gray-700">فرصت فروش</label>
                            <div class="flex items-center gap-2">
                                <input type="text" id="opportunity_name" name="opportunity_name"
                                    class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm bg-gray-50 cursor-pointer focus:ring focus:ring-blue-200 focus:border-blue-400"
                                    placeholder="انتخاب فرصت فروش" readonly>
                                <input type="hidden" id="opportunity_id" name="opportunity_id">
                                <button type="button" onclick="openOpportunityModal()" class="text-blue-600 text-xl hover:text-blue-800 transition">🔍</button>
                            </div>
                            @error('opportunity_id')
                                <div class="text-red-500 text-xs mt-2">{{ $message }}</div>
                            @enderror
                        </div>


                        {{-- ارجاع به --}}
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

    {{-- مودال انتخاب مخاطب --}}
    <div id="contactModal"
     class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center"
     style="display: none;">
        <div class="bg-white w-3/4 max-h-[80vh] overflow-y-auto p-4 rounded shadow">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">انتخاب مخاطب</h3>
                <button onclick="closeContactModal()" class="text-gray-500 hover:text-red-500 text-lg">&times;</button>
            </div>

            <table class="w-full text-sm text-right border border-gray-200">
                <thead>
                    <tr class="bg-gray-100 text-gray-700">
                        <th class="px-4 py-2 border-b border-gray-300">نام مخاطب</th>
                        <th class="px-4 py-2 border-b border-gray-300">شماره موبایل</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contacts as $c)
                        <tr class="cursor-pointer hover:bg-gray-50"
                            onclick="selectContact({{ $c->id }}, '{{ $c->full_name }}')">
                            <td class="px-4 py-2 border-b border-gray-200">{{ $c->full_name }}</td>
                            <td class="px-4 py-2 border-b border-gray-200 text-gray-500">{{ $c->mobile ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>


    <!-- Organization Modal -->
    <div id="organizationModal"
     class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center"
     style="display: none;">
             <div class="bg-white w-3/4 max-h-[80vh] overflow-y-auto p-4 rounded shadow">
            <h2 class="text-lg font-bold mb-4">انتخاب سازمان</h2>
            <table class="w-full text-right border">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-2 border">نام سازمان</th>
                        <th class="p-2 border">شماره تماس</th>
                        <th class="p-2 border">انتخاب</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($organizations as $org)
                        <tr class="border-b">
                            <td class="p-2">{{ $org->name }}</td>
                            <td class="p-2">{{ $org->phone ?? '---' }}</td>
                            <td class="p-2">
                                <button class="text-blue-600 hover:underline" 
                                        onclick="selectOrganization({{ $org->id }}, '{{ $org->name }}')">
                                    انتخاب
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4 text-left">
                <button onclick="closeOrganizationModal()" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">بستن</button>
            </div>
        </div>
    </div>

    <!-- Opportunity Modal -->
    <div id="opportunityModal"
        class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center"
        style="display: none;">
        <div class="bg-white w-3/4 max-h-[80vh] overflow-y-auto p-4 rounded shadow">
            <h2 class="text-lg font-bold mb-4">انتخاب فرصت فروش</h2>
            <table class="w-full text-right border">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-2 border">نام فرصت</th>
                            <th class="p-2 border">مشتری</th>
                            <th class="p-2 border">وضعیت</th>
                            <th class="p-2 border">انتخاب</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($opportunities as $opp)
                            <tr class="border-b">
                                <td class="p-2">{{ $opp->name }}</td>
                                <td class="p-2">{{ $opp->contact->full_name ?? '---' }}</td>
                                <td class="p-2">{{ $opp->status_label ?? '---' }}</td>
                                <td class="p-2">
                                    <button class="text-blue-600 hover:underline"
                                            onclick="selectOpportunity({{ $opp->id }}, '{{ $opp->name }}')">
                                        انتخاب
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            <div class="mt-4 text-left">
                <button onclick="closeOpportunityModal()" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">بستن</button>
            </div>
        </div>
    </div>


@push('scripts')
    <script>
        function openContactModal() {
            const modal = document.getElementById('contactModal');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }

        function closeContactModal() {
            const modal = document.getElementById('contactModal');
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }
    </script>

    <script>
        function openOrganizationModal() {
            const modal = document.getElementById('organizationModal');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }

        function closeOrganizationModal() {
            const modal = document.getElementById('organizationModal');
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }
    </script>

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

    <script>
        function openOpportunityModal() {
            const modal = document.getElementById('opportunityModal');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }

        function closeOpportunityModal() {
            const modal = document.getElementById('opportunityModal');
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }

        function selectOpportunity(id, name) {
            document.getElementById('opportunity_id').value = id;
            document.getElementById('opportunity_name').value = name;
            closeOpportunityModal();
        }
    </script>



    @include('sales.proformas.partials.product-scripts')
@endpush
