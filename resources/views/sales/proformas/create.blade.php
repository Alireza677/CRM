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

                <form action="{{ route('sales.proformas.store') }}" method="POST" class="space-y-6" id="proforma-form">
                    @csrf

                    @include('sales.proformas.partials.form', ['proforma' => null])

                    <div class="flex items-center justify-end mt-6">
                        <input type="hidden" name="submit_mode" id="submit_mode" value="draft">
                        <input type="hidden" name="total_amount" id="total_amount_input" value="0">
                        @php($returnTo = old('return_to', request('return_to')))
                        @if ($returnTo)
                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                        @endif
                        <a href="{{ route('sales.proformas.index') }}" class="btn btn-secondary ml-4">انصراف</a>
                        <button type="button" id="save-btn" class="btn btn-primary" data-modal-open="submitModeModal">ذخیره پیش‌فاکتور</button>
                    </div>

                    @if (request('opportunity_id'))
                        <input type="hidden" name="opportunity_id" value="{{ request('opportunity_id') }}">
                    @endif
                </form>

                @include('sales.proformas.partials.submit-mode-modal')

                <div id="createOpportunityModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
                    <div class="w-full max-w-3xl rounded-xl bg-white shadow-lg">
                        <div class="flex items-center justify-between border-b px-5 py-4">
                            <h3 class="text-base font-semibold text-gray-800">ایجاد فرصت فروش</h3>
                            <button type="button" class="text-gray-500 hover:text-gray-700" onclick="closeOpportunityCreateModal()">&times;</button>
                        </div>
                        <div class="p-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">عنوان</label>
                                    <input type="text" id="quick_opportunity_name" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">سازمان</label>
                                    <select id="quick_opportunity_org" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm">
                                        <option value="">انتخاب کنید</option>
                                        @foreach($organizations as $org)
                                            <option value="{{ $org->id }}">{{ $org->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">مخاطب</label>
                                    <select id="quick_opportunity_contact" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm">
                                        <option value="">انتخاب کنید</option>
                                        @foreach($contacts as $contact)
                                            <option value="{{ $contact->id }}">{{ $contact->full_name ?? $contact->name ?? ('#'.$contact->id) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">نوع کسب‌وکار</label>
                                    <select id="quick_opportunity_type" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm">
                                        <option value="">انتخاب کنید</option>
                                        <option value="کسب و کار موجود">کسب و کار موجود</option>
                                        <option value="کسب و کار جدید">کسب و کار جدید</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">کاربری ساختمان</label>
                                    <select id="quick_opportunity_building_usage" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm">
                                        <option value="">انتخاب کنید...</option>
                                        @foreach([
                                            'کارگاه و یا کارخانه',
                                            'فضای باز و رستوران',
                                            'تعمیرگاه و سالن صنعتی',
                                            'گلخانه و پرورش گیاه',
                                            'مرغداری و پرورش دام و طیور',
                                            'فروشگاه و مراکز خرید',
                                            'سالن و باشگاه‌های ورزشی',
                                            'سالن‌های نمایش',
                                            'مدارس و محیط‌های آموزشی',
                                            'سایر'
                                        ] as $opt)
                                            <option value="{{ $opt }}">{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">منبع فرصت فروش</label>
                                    @php($sources = $sources ?? \App\Helpers\FormOptionsHelper::opportunitySources())
                                    <select id="quick_opportunity_source" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm">
                                        <option value="">انتخاب کنید</option>
                                        @foreach($sources as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">درصد موفقیت</label>
                                    <input type="number" min="0" max="100" id="quick_opportunity_success_rate" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm" value="0">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">تاریخ پیگیری بعدی</label>
                                    <input type="date" id="quick_opportunity_next_follow_up" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">توضیحات</label>
                                    <textarea id="quick_opportunity_description" rows="2" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-2 border-t px-5 py-4">
                            <button type="button" class="px-4 py-2 rounded-md border text-sm" onclick="closeOpportunityCreateModal()">انصراف</button>
                            <button type="button" id="quickOpportunitySubmit" class="px-4 py-2 rounded-md bg-blue-600 text-white text-sm hover:bg-blue-700">ایجاد فرصت فروش</button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const saveBtn = document.getElementById("save-btn");
        const form = document.getElementById("proforma-form");
        const submitModeInput = document.getElementById("submit_mode");
        const submitModeModalId = "submitModeModal";
        const oppIdInput = document.getElementById('opportunity_id');

        function submitWithMode(mode) {
            if (!form || !submitModeInput) return;
            submitModeInput.value = mode;
            calculateInvoiceTotal();
            form.submit();
        }

        form?.addEventListener('submit', function () {
            if (submitModeInput && !submitModeInput.value) {
                submitModeInput.value = 'draft';
            }
        });

        saveBtn?.addEventListener("click", function () {
            if (!oppIdInput || !oppIdInput.value) {
                alert('لطفاً ابتدا فرصت فروش را انتخاب یا ایجاد کنید.');
                return;
            }
            if (typeof window.openModal === 'function') {
                window.openModal(submitModeModalId);
            } else {
                submitWithMode('draft');
            }
        });

        document.getElementById('submit-as-draft')?.addEventListener('click', function () {
            submitWithMode('draft');
        });

        document.getElementById('submit-send-for-approval')?.addEventListener('click', function () {
            submitWithMode('send_for_approval');
        });

        window.openOpportunityCreateModal = function () {
            const modal = document.getElementById('createOpportunityModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        };

        window.closeOpportunityCreateModal = function () {
            const modal = document.getElementById('createOpportunityModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        };

        document.getElementById('quickOpportunitySubmit')?.addEventListener('click', async function () {
            const payload = {
                name: document.getElementById('quick_opportunity_name')?.value?.trim(),
                organization_id: document.getElementById('quick_opportunity_org')?.value || null,
                contact_id: document.getElementById('quick_opportunity_contact')?.value || null,
                type: document.getElementById('quick_opportunity_type')?.value,
                building_usage: document.getElementById('quick_opportunity_building_usage')?.value,
                source: document.getElementById('quick_opportunity_source')?.value,
                success_rate: document.getElementById('quick_opportunity_success_rate')?.value || 0,
                next_follow_up: document.getElementById('quick_opportunity_next_follow_up')?.value || null,
                description: document.getElementById('quick_opportunity_description')?.value?.trim(),
            };

            if (!payload.name || !payload.type || !payload.source || !payload.building_usage) {
                alert('لطفاً فیلدهای الزامی فرصت فروش را تکمیل کنید.');
                return;
            }

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            try {
                const res = await fetch(@json(route('sales.opportunities.quick-store')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                    },
                    body: JSON.stringify(payload),
                });
                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    throw new Error(err.message || 'خطا در ایجاد فرصت فروش.');
                }
                const data = await res.json();
                if (data?.id) {
                    const oppId = document.getElementById('opportunity_id');
                    const oppName = document.getElementById('opportunity_name');
                    if (oppId) oppId.value = data.id;
                    if (oppName) oppName.value = data.name || '';
                    window.closeOpportunityCreateModal();
                }
            } catch (e) {
                alert(e.message || 'خطا در ایجاد فرصت فروش.');
            }
        });
    });
</script>
@endpush
