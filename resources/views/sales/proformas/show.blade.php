@extends('layouts.app')

@section('content')
@php
    $proformaSubject = trim((string) ($proforma->subject ?? ''));
    $proformaTitle = $proformaSubject !== ''
        ? $proformaSubject
        : ($proforma->proforma_number ?: ('#' . $proforma->id));
    $breadcrumb = [
        [
            'title' => 'پیش‌فاکتورها',
            'url'   => route('sales.proformas.index'),
        ],
        [
            'title' => $proformaTitle,
        ],
    ];
@endphp

@php use Morilog\Jalali\Jalalian; @endphp

@if(session('alert_error'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.Swal) {
                Swal.fire({
                icon: 'warning',
                title: 'توجه',
                text: "{{ session('alert_error') }}",
                confirmButtonText: 'باشه'
                });
            } else {
                window.confirm(@json(session('alert_error')));
            }
        });
    </script>
@endif

@php
    $items = $proforma->items ?? collect();
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += (float) ($item->total_price ?? 0);
    }
    $discType = $proforma->global_discount_type ?? null;
    $discVal  = (float) ($proforma->global_discount_value ?? 0);
    $taxType  = $proforma->global_tax_type ?? null;
    $taxVal   = (float) ($proforma->global_tax_value ?? 0);

    if (isset($proforma->global_discount_amount)) {
        $discount = (float) $proforma->global_discount_amount;
    } else {
        $discount = $discType === 'percentage'
            ? ($subtotal * $discVal) / 100
            : ($discType === 'fixed' ? $discVal : 0);
    }

    $discount = min($discount, $subtotal);
    $afterDiscount = $subtotal - $discount;

    if (isset($proforma->global_tax_amount)) {
        $tax = (float) $proforma->global_tax_amount;
    } else {
        $tax = $taxType === 'percentage'
            ? ($afterDiscount * $taxVal) / 100
            : ($taxType === 'fixed' ? $taxVal : 0);
    }

    $tax = max($tax, 0);

    $grand = isset($proforma->total_amount)
        ? (float) $proforma->total_amount
        : ($afterDiscount + $tax);
@endphp

<div class="bg-gray-100" dir="rtl">
    {{-- هدر موبایل --}}
    <div class="flex items-center justify-between px-4 py-3 md:hidden">
        <h1 class="text-lg font-bold text-gray-800 truncate">
            پیش‌فاکتور: {{ $proformaTitle }}
        </h1>
        <div class="flex items-center gap-2">
            <a href="{{ route('sales.proformas.index') }}"
               class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-gray-300 bg-white hover:bg-gray-50"
               title="بازگشت">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <button id="mobileMenuBtn"
                    class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-gray-300 bg-white"
                    aria-label="باز کردن منوی تب‌ها"
                    aria-controls="mobileSidebar"
                    aria-expanded="false">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </div>

    <div class="flex">
        <div id="mobileOverlay" class="fixed inset-0 bg-black/40 z-40 hidden md:hidden"></div>

        {{-- سایدبار تب‌ها (موبایل + دسکتاپ) --}}
        <aside id="mobileSidebar"
               class="fixed right-0 top-[105px] h-[calc(100vh-115px)] w-72 bg-white shadow-lg z-40 border-l
                      transform translate-x-full transition-transform duration-200 ease-out
                      md:translate-x-0 md:w-64 md:overflow-y-auto">
            <div class="p-4">
                <div class="flex items-center justify-between mb-2 md:mb-4">
                    <h2 class="text-m font-bold text-gray-600">
                        {{ $proformaTitle }}
                    </h2>
                    <button id="closeSidebarBtn"
                            class="md:hidden inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-gray-100"
                            aria-label="بستن منو">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <nav class="space-y-1">
                    <a href="#"
                       data-tab="info"
                       class="load-tab flex items-center justify-between px-3 py-2 rounded bg-blue-100 text-blue-800 font-semibold">
                        <span class="flex items-center space-x-2 rtl:space-x-reverse">
                            <i class="fas fa-info-circle"></i>
                            <span>اطلاعات پایه</span>
                        </span>
                    </a>
                    <a href="#"
                       data-tab="items"
                       class="load-tab flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-list text-gray-500"></i>
                        <span>آیتم‌ها</span>
                    </a>
                    <a href="#"
                       data-tab="updates"
                       class="load-tab flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-sync-alt text-gray-500"></i>
                        <span>به‌روزرسانی‌ها</span>
                    </a>
                    <a href="#"
                       data-tab="notes"
                       class="load-tab flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-sticky-note text-gray-500"></i>
                        <span>یادداشت‌ها</span>
                    </a>
                    <a href="#"
                       data-tab="documents"
                       class="load-tab flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-folder text-gray-500"></i>
                        <span>اسناد</span>
                    </a>
                    <a href="#"
                       data-tab="approvals"
                       class="load-tab flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-sync-alt text-gray-500"></i>
                        <span>فرآیند تأیید </span>
                    </a>
                </nav>
            </div>
        </aside>

        {{-- محتوای اصلی --}}
        <main class="flex-1 px-4 md:px-8 pb-8 mr-0 md:mr-64">
            {{-- هدر دسکتاپ --}}
            <div class="hidden md:flex justify-between items-center mb-6 mt-8">
                <h1 class="text-2xl font-bold text-gray-800">
                    پیش‌فاکتور: {{ $proformaTitle }}
                </h1>
                <div class="flex items-center gap-2">
                    <a href="{{ route('sales.proformas.preview', $proforma) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-gray-200 shadow-sm hover:bg-gray-50 text-gray-700 text-sm">
                        مشاهده نسخه چاپی
                    </a>

                    @can('update', $proforma)
                        @if(method_exists($proforma, 'isLockedForEditing') && $proforma->isLockedForEditing())
                            <button type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-gray-100 border border-gray-200 shadow-sm text-gray-400 text-sm cursor-not-allowed"
                                    disabled>
                                ویرایش
                            </button>
                        @else
                            <a href="{{ route('sales.proformas.edit', $proforma) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-gray-200 shadow-sm hover:bg-gray-50 text-gray-700 text-sm">
                                ویرایش
                            </a>
                        @endif
                    @endcan

                    <a href="{{ route('sales.proformas.index') }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-gray-200 shadow-sm hover:bg-gray-50 text-gray-700 text-sm">
                        بازگشت به لیست
                    </a>
                </div>
            </div>

                <div id="po-tab-content" class="bg-white rounded-lg shadow  relative">
                    {{-- تب اطلاعات پایه --}}
                    <div id="tab-info" class="tab-pane space-y-4">
                        @include('sales.proformas.tabs.info')
                    </div>

                    {{-- تب آیتم‌ها --}}
                    <div id="tab-items" class="tab-pane hidden">
                        @include('sales.proformas.tabs.items')
                    </div>

                    {{-- تب بروزرسانی‌ها --}}
                    <div id="tab-updates" class="tab-pane hidden space-y-4">
                        @include('sales.proformas.tabs.updates')
                    </div>

                    {{-- تب یادداشت‌ها --}}
                    <div id="tab-notes" class="tab-pane hidden">
                        @include('sales.proformas.tabs.notes')
                    </div>

                    {{-- تب اسناد --}}
                    <div id="tab-documents" class="tab-pane hidden space-y-4">
                        @include('sales.proformas.tabs.documents')
                    </div>

                    {{-- تب تأییدها --}}
                    <div id="tab-approvals" class="tab-pane hidden space-y-4">
                        @include('sales.proformas.tabs.approvals')
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    const links = document.querySelectorAll('.load-tab');
    const activeClasses = ['bg-blue-100', 'text-blue-800', 'font-semibold'];
    const tabPanes = {
        info: document.getElementById('tab-info'),
        items: document.getElementById('tab-items'),
        updates: document.getElementById('tab-updates'),
        notes: document.getElementById('tab-notes'),
        documents: document.getElementById('tab-documents'),
        approvals: document.getElementById('tab-approvals'),
    };

    function setActiveTab(el) {
        links.forEach(l => l.classList.remove(...activeClasses));
        el.classList.add(...activeClasses);
    }

    function showTab(key) {
        Object.values(tabPanes).forEach(pane => pane?.classList.add('hidden'));
        tabPanes[key]?.classList.remove('hidden');
    }

    links.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const key = this.getAttribute('data-tab');
            if (key) {
                showTab(key);
                setActiveTab(this);
                closeSidebar();
            }
        });
    });

    const defaultLink = document.querySelector('.load-tab');
    if (defaultLink) {
        setActiveTab(defaultLink);
        showTab(defaultLink.getAttribute('data-tab'));
    }

    const openBtn  = document.getElementById('mobileMenuBtn');
    const closeBtn = document.getElementById('closeSidebarBtn');
    const sidebar  = document.getElementById('mobileSidebar');
    const overlay  = document.getElementById('mobileOverlay');

    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('translate-x-full');
        overlay?.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        openBtn?.setAttribute('aria-expanded', 'false');
    }

    openBtn?.addEventListener('click', () => {
        sidebar?.classList.remove('translate-x-full');
        overlay?.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        openBtn.setAttribute('aria-expanded', 'true');
    });

    closeBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        closeSidebar();
    });

    overlay?.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && window.matchMedia('(max-width: 767px)').matches) {
            closeSidebar();
        }
    });
});
</script>

<script>
// منشن داخل متن یادداشت (مشابه گفتگو)
document.addEventListener('DOMContentLoaded', () => {
    const mentionDataEl = document.getElementById('mentionData');
    const mentionCandidates = mentionDataEl ? JSON.parse(mentionDataEl.dataset.mentionCandidates || '[]') : [];
    const messageBody = document.querySelector('textarea[name="content"]');
    const mentionDropdown = document.getElementById('mentionDropdown');
    const mentionList = document.getElementById('mentionList');
    const selectedMentions = document.getElementById('selectedMentions');

    let mentionMatches = [];
    let mentionActiveIndex = 0;

    function getMentionState(text, caretPos) {
        const upToCaret = text.slice(0, caretPos);
        const atIndex = upToCaret.lastIndexOf('@');
        if (atIndex === -1) return null;
        const charBefore = atIndex > 0 ? upToCaret[atIndex - 1] : ' ';
        if (charBefore && !/\s/.test(charBefore)) return null;
        const query = upToCaret.slice(atIndex + 1);
        if (/\s/.test(query)) return null;
        return { atIndex, query };
    }

    function getFilteredMentions(query) {
        const needle = (query || '').trim().toLowerCase();
        const items = Array.isArray(mentionCandidates) ? mentionCandidates : [];
        return !needle
            ? items
            : items.filter(item => {
                const name = (item.name || '').toLowerCase();
                const username = (item.username || '').toLowerCase();
                return name.includes(needle) || username.includes(needle);
            });
    }

    function buildMentionRow(item, isActive) {
        const li = document.createElement('li');
        li.className = `px-3 py-2 text-xs cursor-pointer flex items-center justify-between gap-2 ${isActive ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50'}`;
        li.dataset.mentionId = item.id;
        li.dataset.mentionName = item.name || '';
        li.dataset.mentionUsername = item.username || '';

        const name = document.createElement('span');
        name.className = 'font-semibold truncate';
        name.textContent = item.name || item.username || '';

        const meta = document.createElement('span');
        meta.className = 'text-[10px] text-gray-400';
        meta.textContent = item.username ? `@${item.username}` : '';

        li.appendChild(name);
        li.appendChild(meta);
        return li;
    }

    function hideMentionDropdown() {
        mentionDropdown?.classList.add('hidden');
        mentionActiveIndex = 0;
        mentionMatches = [];
    }

    function renderMentionDropdown() {
        if (!mentionDropdown || !mentionList) return;
        mentionList.innerHTML = '';
        mentionMatches.forEach((item, index) => {
            mentionList.appendChild(buildMentionRow(item, index === mentionActiveIndex));
        });
        if (mentionMatches.length) {
            mentionDropdown.classList.remove('hidden');
        } else {
            hideMentionDropdown();
        }
    }

    function showMentionDropdown(items) {
        mentionMatches = items;
        mentionActiveIndex = 0;
        renderMentionDropdown();
    }

    function updateMentionDropdown() {
        if (!messageBody) return;
        const caretPos = messageBody.selectionStart || 0;
        const state = getMentionState(messageBody.value, caretPos);
        if (!state) {
            hideMentionDropdown();
            return;
        }
        const results = getFilteredMentions(state.query);
        showMentionDropdown(results);
    }

    function syncMentionsWithBody() {
        if (!messageBody) return;
        const body = messageBody.value || '';
        const inputs = Array.from(document.querySelectorAll('input[name="mentions[]"]'));
        inputs.forEach(input => {
            const name = input.dataset.name || '';
            const username = input.value || '';
            if (!name && !username) return;
            const tokenName = name ? `@${name}` : '';
            const tokenUsername = username ? `@${username}` : '';
            if (tokenName && body.includes(tokenName)) return;
            if (tokenUsername && body.includes(tokenUsername)) return;
            input.remove();
        });

        const remaining = Array.from(document.querySelectorAll('input[name="mentions[]"]')).map(input => ({
            username: input.value,
            name: input.dataset.name || input.value
        }));

        if (selectedMentions) {
            selectedMentions.innerHTML = remaining.map(u => `
                <span class="inline-flex items-center bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full text-xs ml-1 mb-1">
                    ${u.name}
                    <button type="button"
                            class="ml-[5px] text-red-600 hover:text-red-800 font-bold remove-mention"
                            data-username="${u.username}">&times;</button>
                </span>
            `).join(' ');
        }
    }

    function insertMention(item) {
        if (!messageBody) return;
        const caretPos = messageBody.selectionStart || 0;
        const state = getMentionState(messageBody.value, caretPos);
        if (!state) return;
        const mentionName = item.name || item.username || '';
        const token = `@${mentionName}`;
        const before = messageBody.value.slice(0, state.atIndex);
        const after = messageBody.value.slice(caretPos);
        const spacer = after.startsWith(' ') ? '' : ' ';
        const nextValue = `${before}${token}${spacer}${after}`;
        const nextCaret = before.length + token.length + spacer.length;
        messageBody.value = nextValue;
        messageBody.focus();
        messageBody.setSelectionRange(nextCaret, nextCaret);

        if (item.username) {
            const existing = document.querySelector(`input[name="mentions[]"][value="${item.username}"]`);
            if (!existing) {
                const form = document.getElementById('noteForm');
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'mentions[]';
                hiddenInput.value = item.username;
                hiddenInput.dataset.name = item.name || item.username;
                form?.appendChild(hiddenInput);
            }
        }

        syncMentionsWithBody();
        hideMentionDropdown();
    }

    messageBody?.addEventListener('input', () => {
        updateMentionDropdown();
        syncMentionsWithBody();
    });
    messageBody?.addEventListener('click', updateMentionDropdown);
    messageBody?.addEventListener('keydown', (event) => {
        if (!mentionDropdown || mentionDropdown.classList.contains('hidden')) return;
        if (!mentionMatches.length) return;

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            mentionActiveIndex = (mentionActiveIndex + 1) % mentionMatches.length;
            renderMentionDropdown();
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            mentionActiveIndex = (mentionActiveIndex - 1 + mentionMatches.length) % mentionMatches.length;
            renderMentionDropdown();
        } else if (event.key === 'Enter') {
            event.preventDefault();
            const item = mentionMatches[mentionActiveIndex];
            if (item) {
                insertMention(item);
            }
        } else if (event.key === 'Escape') {
            event.preventDefault();
            hideMentionDropdown();
        }
    });

    mentionList?.addEventListener('click', (event) => {
        const target = event.target instanceof HTMLElement ? event.target.closest('li') : null;
        if (!target) return;
        const id = Number(target.dataset.mentionId || 0);
        const name = target.dataset.mentionName || '';
        const username = target.dataset.mentionUsername || '';
        if (!id) return;
        insertMention({ id, name, username });
    });

    document.addEventListener('click', (event) => {
        if (!mentionDropdown || mentionDropdown.classList.contains('hidden')) return;
        if (mentionDropdown.contains(event.target) || messageBody?.contains(event.target)) return;
        hideMentionDropdown();
    });

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-mention')) {
            const username = e.target.dataset.username;
            document.querySelectorAll('input[name="mentions[]"]').forEach(input => {
                if (input.value === username) input.remove();
            });
            syncMentionsWithBody();
        }
    });
});
</script>
@endpush
