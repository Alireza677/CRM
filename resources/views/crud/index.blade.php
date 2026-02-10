@extends('layouts.app')

@section('content')
@php
    $title = $schema['title'] ?? 'لیست';
    $isLeadListing = ($schema['key'] ?? null) === 'leads';
    $isOpportunityListing = ($schema['key'] ?? null) === 'opportunities';
    $favoriteLeadIds = $favoriteLeadIds ?? [];
    $favoriteOpportunityIds = $favoriteOpportunityIds ?? [];
    $leadTabCounts = $leadTabCounts ?? [];
    $opportunityTabCounts = $opportunityTabCounts ?? [];
    $leadPoolRules = $leadPoolRules ?? [];
    $leadPoolFirstActivity = $leadPoolRules['first_activity_deadline_label'] ?? '24 ساعت';
    $leadPoolMaxReassignments = $leadPoolRules['max_reassignments'] ?? 3;
    $leadPoolFinalDecisionDays = $leadPoolRules['final_decision_days'] ?? 14;
@endphp

<div class="px-6 h-[calc(100vh-140px)] flex flex-col gap-4 overflow-hidden" dir="rtl">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3">
            <div class="text-right">
                <h1 class="text-lg font-bold text-gray-800">{{ $title }}</h1>
                <div class="text-xs text-gray-500">{{ $rows->total() }} مورد</div>
            </div>
            @if($isLeadListing)
                <div class="relative group">
                    <button
                        type="button"
                        id="lead-rules-trigger"
                        class="w-7 h-7 inline-flex items-center justify-center rounded-full bg-white border border-gray-200 shadow-sm text-blue-600 hover:bg-blue-50 hover:text-blue-700 transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        aria-label="قوانین سرنخ‌ها"
                    >
                        <img src="{{ asset('images/info.svg') }}" alt="" class="w-5 h-5">
                    </button>
                    <div class="absolute right-0 mt-2 w-44 px-3 py-2 bg-gray-800 text-white text-xs rounded-md shadow-lg opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition pointer-events-none">
                        قوانین استخر سرنخ‌ها
                    </div>
                </div>
            @endif
        </div>
        <div class="flex flex-wrap justify-end gap-2">
            @include('crud.partials.actions', ['context' => 'header'])
            @if(($schema['key'] ?? null) === 'contacts')
                <a href="{{ route('sales.contacts.duplicates.index') }}"
                   class="inline-flex items-center h-9 px-4 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    <i class="fas fa-copy ml-2"></i>
                    یافتن تکراری‌ها
                </a>
                @role('admin')
                    <a href="{{ route('sales.contacts.import.form') }}"
                       class="inline-flex items-center h-9 px-4 bg-emerald-600 text-white rounded-md hover:bg-emerald-700">
                        <i class="fas fa-arrow-down ml-2"></i>
                        ایمپورت مخاطبین
                    </a>
                    <a href="{{ route('sales.contacts.export.format', ['format' => 'csv']) }}"
                       class="inline-flex items-center h-9 px-4 bg-green-600 text-white rounded-md hover:bg-green-700">
                        <i class="fas fa-file-csv ml-2"></i>
                        اکسپورت (CSV)
                    </a>
                    <a href="{{ route('sales.contacts.export.format', ['format' => 'xlsx']) }}"
                       class="inline-flex items-center h-9 px-4 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        <i class="fas fa-file-excel ml-2"></i>
                        اکسپورت (XLSX)
                    </a>
                @endrole
            @endif
            @if(($schema['key'] ?? null) === 'organizations')
                <a href="{{ route('sales.organizations.create') }}"
                   class="inline-flex items-center h-9 px-4 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                    <i class="fas fa-plus ml-2"></i>
                    سازمان جدید
                </a>
                <a href="{{ route('sales.organizations.duplicates.index') }}"
                   class="inline-flex items-center h-9 px-4 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    <i class="fas fa-copy ml-2"></i>
                    یافتن موارد تکراری
                </a>
                <a href="{{ route('sales.organizations.import.form') }}"
                   class="inline-flex items-center h-9 px-4 bg-emerald-500 text-white rounded-md hover:bg-emerald-600">
                    <i class="fas fa-arrow-down ml-2"></i>
                    درون‌ریزی از Excel
                </a>
            @endif
            @if(($schema['key'] ?? null) === 'projects')
                <a href="{{ route('projects.archive') }}"
                   class="inline-flex items-center h-9 px-4 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">
                    بایگانی
                </a>
            @endif
            @if(($schema['key'] ?? null) === 'projects_archive')
                <a href="{{ route('projects.index') }}"
                   class="inline-flex items-center h-9 px-4 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">
                    پروژه‌های جاری
                </a>
            @endif
            @if($isLeadListing)
                @role('admin')
                    @include('marketing.leads.partials.export-dropdown')
                    <button
                        type="submit"
                        id="bulk-delete-btn"
                        form="lead-bulk-form"
                        onclick="return confirm('آیا مطمئنید؟')"
                        class="inline-flex items-center h-9 px-4 bg-red-600 text-white rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-red-700"
                        disabled>
                        <i class="fas fa-trash ml-2"></i>
                        حذف انتخاب‌شده‌ها
                        <span id="selected-count-badge"
                            class="ml-2 hidden px-2 py-0.5 text-xs rounded-full bg-white/20">0</span>
                    </button>
                @endrole
            @endif
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        @if($isLeadListing)
            @include('marketing.leads.partials.listing-tabs')
        @endif
        @if($isOpportunityListing)
            @include('sales.opportunities.partials.listing-tabs')
        @endif
        <div class="flex flex-wrap items-center gap-3">
            <form id="filters-form" method="GET" action="{{ route($schema['routes']['index']) }}">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="dir" value="{{ $dir }}">
            </form>
            <form id="per-page-form" method="GET" action="{{ route($schema['routes']['index']) }}" class="flex items-center gap-2">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="dir" value="{{ $dir }}">
                <label class="text-xs text-gray-500">تعداد در صفحه</label>
                <select name="per_page" class="rounded-md border border-gray-200 bg-white px-2 py-1 text-xs">
                    @foreach(($schema['per_page_options'] ?? [15, 30, 50]) as $size)
                        <option value="{{ $size }}" @selected($perPage == $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </form>
            <div class="text-xs text-gray-500">صفحه {{ $rows->currentPage() }} از {{ $rows->lastPage() }}</div>
        </div>
    </div>

    <div class="flex-1 min-h-0 overflow-auto">
        @if($isLeadListing)
            <form id="lead-bulk-form" method="POST" action="{{ route('marketing.leads.bulk-delete') }}">
                @csrf
                @include('crud.partials.table', ['showPagination' => false])
            </form>
        @else
            @include('crud.partials.table', ['showPagination' => false])
        @endif
    </div>

    <div class="shrink-0 border-t border-gray-200 bg-white py-2">
        {{ $rows->links() }}
    </div>
</div>

@if($isLeadListing)
    <!-- Lead convert modal -->
    <div id="lead-convert-modal" class="fixed inset-0 z-50 hidden items-center justify-center px-4" dir="rtl">
        <div id="lead-convert-backdrop" class="absolute inset-0 bg-black/40 backdrop-blur-sm" data-lead-convert-close></div>
        <div class="relative bg-white rounded-lg shadow-xl border border-gray-200 max-w-lg w-full mx-auto p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="text-sm text-gray-500 mb-1">تبدیل سرنخ به فرصت فروش</p>
                    <h3 class="text-lg font-semibold text-gray-800">
                        اطلاعات نقش‌ها برای
                        <span id="lead-convert-name" class="text-gray-900">—</span>
                    </h3>
                </div>
                <button type="button" class="text-gray-500 hover:text-gray-700 transition" aria-label="بستن" data-lead-convert-close>
                    <span class="text-xl leading-none">&times;</span>
                </button>
            </div>

            <form id="lead-convert-form" method="POST" action="" class="space-y-4">
                @csrf
                <div class="text-sm text-gray-700 bg-gray-50 border border-gray-200 rounded-md px-3 py-2">
                    مالک اصلی فرصت: <span id="lead-convert-owner-name" class="font-semibold text-gray-800">—</span>
                </div>

                <div>
                    <label for="lead-convert-acquirer" class="block text-sm font-medium text-gray-700">جذب‌کننده</label>
                    <select id="lead-convert-acquirer" name="acquirer_user_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">انتخاب کنید</option>
                        @foreach(($users ?? []) as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" id="lead-convert-acquirer-locked" name="acquirer_user_id" value="" disabled>
                    <p id="lead-convert-acquirer-note" class="mt-1 text-xs text-amber-600 hidden"></p>
                </div>

                <div>
                    <label for="lead-convert-closer" class="block text-sm font-medium text-gray-700">نهایی‌کننده</label>
                    <select id="lead-convert-closer" name="closer_user_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">انتخاب کنید</option>
                        @foreach(($users ?? []) as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="lead-convert-execution" class="block text-sm font-medium text-gray-700"> پشتیبان فنی</label>
                    <select id="lead-convert-execution" name="execution_owner_user_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">انتخاب کنید</option>
                        @foreach(($users ?? []) as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" class="px-4 py-2 text-sm rounded-md border border-gray-200 text-gray-700 hover:bg-gray-50" data-lead-convert-close>
                        انصراف
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                        تایید و تبدیل
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

@if($isLeadListing)
    <!-- Lead rules modal -->
    <div id="lead-rules-modal" class="fixed inset-0 z-40 hidden items-center justify-center px-4">
        <div id="lead-rules-backdrop" class="absolute inset-0 bg-black/40 backdrop-blur-sm" data-lead-rules-close></div>
        <div class="relative bg-white rounded-lg shadow-xl border border-gray-200 max-w-2xl w-full mx-auto p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="text-sm text-gray-500 mb-1">راهنمای استخر سرنخ‌ها</p>
                    <h3 class="text-lg font-semibold text-gray-800">قوانین سرنخ‌ها</h3>
                </div>
                <button type="button" class="text-gray-500 hover:text-gray-700 transition" aria-label="بستن" data-lead-rules-close>
                    <span class="text-xl leading-none">&times;</span>
                </button>
            </div>
            <div class="space-y-3 text-sm text-gray-700 leading-6">
                <ul class="list-disc list-inside space-y-2 pr-2">
                    <li>Round-robin assignment: سرنخ جدید نوبتی بین مسئول‌ها تقسیم می‌شود.</li>
                    <li>مهلت اولین فعالیت: کاربر تا {{ $leadPoolFirstActivity }} فرصت دارد فعالیت معتبر ثبت کند؛ در غیر این صورت ارجاع به نفر بعد.</li>
                    <li>فعالیت معتبر: ثبت تماس/پیگیری، ثبت یادداشت، یا هر Activity ثبت‌شده.</li>
                    <li>سقف ارجاع: تا {{ $leadPoolMaxReassignments }} بار قابل جابه‌جایی است.</li>
                    <li>تعیین تکلیف نهایی: نهایتاً تا {{ $leadPoolFinalDecisionDays }} روز باید یا تبدیل به فرصت شود یا برود به «سرکاری‌ها».</li>
                    <li>سرنخ‌های دستی هم مشمول همین قوانین هستند.</li>
                    <li>اعلان‌ها: با هر تغییر مسئول، به مسئول جدید نوتیفیکیشن ارسال می‌شود.</li>
                </ul>
            </div>
        </div>
    </div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const isLeadListing = @json($isLeadListing);
        const perPageSelect = document.querySelector('select[name="per_page"]');
        if (perPageSelect && perPageSelect.form) {
            perPageSelect.addEventListener('change', () => perPageSelect.form.submit());
        }

        const filtersForm = document.getElementById('filters-form');
        if (filtersForm) {
            const filterFields = document.querySelectorAll(
                'select[form="filters-form"], input[form="filters-form"]'
            );
            const dateFields = Array.from(filtersForm.querySelectorAll('input.persian-datepicker[form="filters-form"]'));

            dateFields.forEach((field) => {
                field.dataset.initialEmpty = field.value ? '0' : '1';
                field.addEventListener('change', () => {
                    field.dataset.userSet = '1';
                });
                field.addEventListener('focus', () => {
                    field.dataset.userSet = '1';
                });
            });

            const clearAutoDates = () => {
                dateFields.forEach((field) => {
                    if (field.dataset.initialEmpty === '1' && field.dataset.userSet !== '1') {
                        field.value = '';
                    }
                });
            };

            filterFields.forEach((field) => {
                if (field.tagName === 'SELECT') {
                    field.addEventListener('change', () => {
                        clearAutoDates();
                        filtersForm.submit();
                    });
                    return;
                }

                let timer = null;
                const submitLater = () => {
                    clearTimeout(timer);
                    timer = setTimeout(() => {
                        clearAutoDates();
                        filtersForm.submit();
                    }, 400);
                };

                field.addEventListener('input', submitLater);
                field.addEventListener('change', submitLater);
                field.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        clearAutoDates();
                        filtersForm.submit();
                    }
                });
            });
        }

        const selectAll = document.getElementById('select-all');
        if (selectAll) {
            selectAll.addEventListener('change', () => {
                const selector = isLeadListing ? '.row-checkbox' : 'input[name="ids[]"]';
                document.querySelectorAll(selector).forEach((cb) => {
                    cb.checked = selectAll.checked;
                });
                if (isLeadListing) {
                    refreshLeadBulkState();
                }
            });
        }

        const refreshLeadBulkState = () => {
            if (!isLeadListing) return;
            const bulkBtn = document.getElementById('bulk-delete-btn');
            const countBadge = document.getElementById('selected-count-badge');
            const boxes = Array.from(document.querySelectorAll('.row-checkbox'));
            const count = boxes.filter(b => b.checked).length;

            if (bulkBtn) bulkBtn.disabled = (count === 0);
            if (countBadge) {
                countBadge.textContent = count;
                countBadge.classList.toggle('hidden', count === 0);
            }
        };

        if (isLeadListing) {
            document.querySelectorAll('.row-checkbox').forEach(cb => {
                cb.addEventListener('change', refreshLeadBulkState);
            });
            refreshLeadBulkState();
        }

        if (isLeadListing) {
            const companyAcquirerId = @json($companyAcquirerUserId ?? null);
            const companyAcquirerName = @json($companyAcquirerUserName ?? null);
            const companyAcquirerSettingsUrl = @json(route('settings.sales.leads.edit'));
            const convertModal = document.getElementById('lead-convert-modal');
            const convertBackdrop = document.getElementById('lead-convert-backdrop');
            const convertCloseButtons = document.querySelectorAll('[data-lead-convert-close]');
            const convertForm = document.getElementById('lead-convert-form');
            const convertLeadName = document.getElementById('lead-convert-name');
            const convertOwnerName = document.getElementById('lead-convert-owner-name');
            const convertAcquirer = document.getElementById('lead-convert-acquirer');
            const convertAcquirerLocked = document.getElementById('lead-convert-acquirer-locked');
            const convertAcquirerNote = document.getElementById('lead-convert-acquirer-note');
            const convertCloser = document.getElementById('lead-convert-closer');
            const convertExecution = document.getElementById('lead-convert-execution');
            const rulesTrigger = document.getElementById('lead-rules-trigger');
            const rulesModal = document.getElementById('lead-rules-modal');
            const rulesBackdrop = document.getElementById('lead-rules-backdrop');
            const rulesCloseButtons = document.querySelectorAll('[data-lead-rules-close]');

            const openConvertModal = (button) => {
                if (!convertModal || !convertForm) return;
                const action = button.getAttribute('data-convert-action');
                const leadName = button.getAttribute('data-lead-name') || '—';
                const ownerName = button.getAttribute('data-assigned-user-name') || '—';
                const sourceOwner = button.getAttribute('data-lead-source-owner') || 'agent';
                const defaultAcquirerId = button.getAttribute('data-default-acquirer-id') || '';

                if (action) convertForm.action = action;
                if (convertLeadName) convertLeadName.textContent = leadName;
                if (convertOwnerName) convertOwnerName.textContent = ownerName || '—';

                if (convertAcquirer) convertAcquirer.value = '';
                if (convertCloser) convertCloser.value = '';
                if (convertExecution) convertExecution.value = '';

                if (convertAcquirerNote) {
                    convertAcquirerNote.textContent = '';
                    convertAcquirerNote.classList.add('hidden');
                }

                if (convertAcquirerLocked) {
                    convertAcquirerLocked.value = '';
                    convertAcquirerLocked.disabled = true;
                }

                if (convertAcquirer) {
                    convertAcquirer.disabled = false;
                }

                if (sourceOwner === 'company') {
                    const companyId = companyAcquirerId ? String(companyAcquirerId) : '';
                    if (convertAcquirer) {
                        convertAcquirer.value = companyId;
                        convertAcquirer.disabled = true;
                    }
                    if (convertAcquirerLocked) {
                        convertAcquirerLocked.value = companyId;
                        convertAcquirerLocked.disabled = false;
                    }
                    if (convertAcquirerNote) {
                        if (companyId) {
                            const label = companyAcquirerName || 'شرکت';
                            convertAcquirerNote.textContent = `منبع سازمانی است؛ جذب‌کننده به ${label} اختصاص داده می‌شود.`;
                        } else {
                            convertAcquirerNote.innerHTML = `کاربر جذب‌کننده شرکتی تنظیم نشده است. <a class="underline" href="${companyAcquirerSettingsUrl}">تنظیمات</a>`;
                        }
                        convertAcquirerNote.classList.remove('hidden');
                    }
                } else if (defaultAcquirerId && convertAcquirer) {
                    convertAcquirer.value = String(defaultAcquirerId);
                }

                convertModal.classList.remove('hidden');
                convertModal.classList.add('flex');
            };

            const closeConvertModal = () => {
                if (!convertModal) return;
                convertModal.classList.add('hidden');
                convertModal.classList.remove('flex');
            };

            document.addEventListener('click', function (event) {
                const button = event.target.closest('.js-open-convert-modal');
                if (!button) return;
                event.preventDefault();
                openConvertModal(button);
            });

            convertCloseButtons.forEach(btn => btn.addEventListener('click', closeConvertModal));

            if (convertBackdrop) {
                convertBackdrop.addEventListener('click', closeConvertModal);
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    if (rulesModal && !rulesModal.classList.contains('hidden')) {
                        closeRulesModal();
                    }
                    if (convertModal && !convertModal.classList.contains('hidden')) {
                        closeConvertModal();
                    }
                }
            });

            const openRulesModal = () => {
                if (!rulesModal) return;
                rulesModal.classList.remove('hidden');
                rulesModal.classList.add('flex');
            };

            const closeRulesModal = () => {
                if (!rulesModal) return;
                rulesModal.classList.add('hidden');
                rulesModal.classList.remove('flex');
            };

            if (rulesTrigger && rulesModal) {
                rulesTrigger.addEventListener('click', function (event) {
                    event.preventDefault();
                    openRulesModal();
                });
            }

            rulesCloseButtons.forEach(btn => btn.addEventListener('click', closeRulesModal));

            if (rulesBackdrop) {
                rulesBackdrop.addEventListener('click', closeRulesModal);
            }
        }
    });
</script>
@endsection
