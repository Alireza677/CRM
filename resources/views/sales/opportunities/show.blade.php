@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'فرصت‌های فروش', 'url' => route('sales.opportunities.index')],
        ['title' => 'جزئیات: ' . ($opportunity->subject ?? $opportunity->name)]
    ];
@endphp

<div class="bg-gray-100">
    {{-- هدر موبایل: عنوان + اکشن‌ها + همبرگری --}}
    <div class="flex items-center justify-between px-4 py-3 md:hidden">
        <h1 class="text-lg font-bold text-gray-800 truncate">
            فرصت فروش: {{ $opportunity->name ?? $opportunity->subject }}
        </h1>
        <div class="flex items-center gap-2">
            {{-- ویرایش (موبایل) --}}
            <a href="{{ route('sales.opportunities.edit', $opportunity) }}"
               class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-gray-300 bg-white hover:bg-gray-50"
               title="ویرایش">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </a>
            {{-- حذف (موبایل) --}}
            @role('admin')
            <form action="{{ route('sales.opportunities.destroy', $opportunity) }}" method="POST"
                  onsubmit="return confirm('آیا از حذف این فرصت فروش اطمینان دارید؟')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-red-300 bg-white hover:bg-red-50 text-red-600"
                        title="حذف">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </form>
            @endrole
            {{-- همبرگری --}}
            <button id="mobileMenuBtn"
                    class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-gray-300 bg-white"
                    aria-label="باز کردن منو" aria-controls="mobileSidebar" aria-expanded="false">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
    </div>

    <div class="flex ">
        {{-- سایدبار: آف‌کانواس در موبایل / ثابت در دسکتاپ --}}
        <div id="mobileOverlay" class="fixed inset-0 bg-black/40 z-40 hidden md:hidden"></div>

        <aside id="mobileSidebar"
  class="fixed right-0 top-[115px] h-full w-72 bg-white shadow-lg z-50 border-l
         transform translate-x-full transition-transform duration-200 ease-out
         md:translate-x-0 md:sticky md:top-[115px] md:h-[calc(100vh-115px)] md:w-64 md:z-40 md:overflow-y-auto">

            <div class="p-4">
                <div class="flex items-center justify-between mb-2 md:mb-4">
                    <h2 class="text-m font-bold text-gray-600">
                        {{ $opportunity->name ?? $opportunity->subject }}
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
                    {{-- فقط یکی پیش‌فرض فعال باشد (summary) --}}
                    <a href="#"
                       data-url="{{ route('sales.opportunities.tab', ['opportunity' => $opportunity->id, 'tab' => 'summary']) }}"
                       class="load-tab flex items-center justify-between px-3 py-2 rounded bg-blue-100 text-blue-800 font-semibold">
                        <span class="flex items-center space-x-2 rtl:space-x-reverse">
                            <i class="fas fa-th-large"></i>
                            <span>خلاصه</span>
                        </span>
                    </a>

                    <a href="#"
                       data-url="{{ route('sales.opportunities.tab', ['opportunity' => $opportunity->id, 'tab' => 'info']) }}"
                       class="load-tab flex items-center justify-between px-3 py-2 rounded text-gray-700 hover:bg-gray-100">
                        <span class="flex items-center space-x-2 rtl:space-x-reverse">
                            <i class="fas fa-info-circle"></i>
                            <span>اطلاعات</span>
                        </span>
                    </a>

                    <a href="#"
                       data-url="{{ route('sales.opportunities.tab', ['opportunity' => $opportunity->id, 'tab' => 'updates']) }}"
                       class="load-tab flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-sync-alt text-gray-500"></i><span>بروزرسانی‌ها</span>
                    </a>

                    <a href="#"
                       data-url="{{ route('sales.opportunities.tab', ['opportunity' => $opportunity->id, 'tab' => 'notes']) }}"
                       class="load-tab flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-sticky-note text-gray-500"></i><span>یادداشت‌ها</span>
                    </a>

                    <a href="#"
                       data-url="{{ route('sales.opportunities.tab', ['opportunity' => $opportunity->id, 'tab' => 'contacts']) }}"
                       class="load-tab flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-user-friends text-gray-500"></i><span>مخاطبین</span>
                    </a>

                    <a href="#"
                       data-url="{{ route('sales.opportunities.tab', ['opportunity' => $opportunity->id, 'tab' => 'proformas']) }}"
                       class="load-tab flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-file-invoice text-gray-500"></i><span>پیش فاکتور</span>
                    </a>

                    <a href="#"
                       data-url="{{ route('sales.opportunities.tab', ['opportunity' => $opportunity->id, 'tab' => 'documents']) }}"
                       class="load-tab flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-receipt text-gray-500"></i><span>اسناد</span>
                    </a>

                    <a href="#"
                       data-url="{{ route('sales.opportunities.tab', ['opportunity' => $opportunity->id, 'tab' => 'approvals']) }}"
                       class="load-tab flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-check-circle text-gray-500"></i><span>تأییدیه‌ها</span>
                    </a>

                    <a href="#"
                       data-url="{{ route('sales.opportunities.tab', ['opportunity' => $opportunity->id, 'tab' => 'calls']) }}"
                       class="load-tab flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-phone-alt text-gray-500"></i><span>تماس‌های تلفنی</span>
                    </a>
                </nav>
            </div>
        </aside>

        {{-- محتوای اصلی --}}
        <main class="flex-1 px-4 md:px-8 pb-8 md:mr-64 md:ml-0">

            {{-- هدر دسکتاپ: فقط عنوان --}}
            <div class="hidden md:flex justify-between items-center mb-6 mt-8">
                <h1 class="text-2xl font-bold text-gray-800">
                    فرصت فروش: {{ $opportunity->name ?? $opportunity->subject }}
                </h1>
                {{-- اکشن‌ها اینجا حذف شدند؛ نوار اکشن شناور استفاده می‌شود --}}
            </div>

            {{-- نوار اکشن شناور بالای تب‌ها --}}
            <div class="relative">
                <div class="absolute -top-4 left-2 md:-top-6 md:left-0 z-20 flex items-center gap-2">
                    {{-- ویرایش --}}
                    <a href="{{ route('sales.opportunities.edit', $opportunity) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-gray-200 shadow-sm hover:bg-gray-50 text-gray-700 text-sm"
                       title="ویرایش فرصت">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span class="hidden sm:inline">ویرایش</span>
                    </a>
                    {{-- حذف --}}
                    @role('admin')
                    <form action="{{ route('sales.opportunities.destroy', $opportunity) }}" method="POST"
                          onsubmit="return confirm('آیا از حذف این فرصت فروش اطمینان دارید؟')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-red-200 shadow-sm hover:bg-red-50 text-red-600 text-sm"
                                title="حذف فرصت">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span class="hidden sm:inline">حذف</span>
                        </button>
                    </form>
                    @endrole
                </div>
            </div>

            {{-- محتوای تب‌ها --}}
            <div id="opportunity-tab-content" class="bg-white rounded-lg shadow p-4">
                <div class="text-gray-500 text-sm">در حال بارگذاری...</div>
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    const links = document.querySelectorAll('.load-tab');
    const contentArea = document.getElementById('opportunity-tab-content');

    const activeClasses = ['bg-blue-100', 'text-blue-800', 'font-semibold'];

    function setActiveTab(el) {
        links.forEach(l => l.classList.remove(...activeClasses));
        el.classList.add(...activeClasses);
    }

    function closeSidebar() {
        const sidebar = document.getElementById('mobileSidebar');
        const overlay = document.getElementById('mobileOverlay');
        const openBtn = document.getElementById('mobileMenuBtn');
        if (!sidebar) return;
        sidebar.classList.add('translate-x-full');
        overlay?.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        openBtn?.setAttribute('aria-expanded', 'false');
    }

    function loadTab(url, clickedEl = null) {
        contentArea.innerHTML =
          '<div class="text-gray-400 p-4 flex items-center gap-2">' +
          '<svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" fill="none" stroke-width="4" opacity=".25"></circle><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" fill="none"></path></svg>' +
          'در حال بارگذاری...</div>';

        fetch(url)
            .then(res => res.text())
            .then(html => {
                contentArea.innerHTML = html;
                // در موبایل، پس از انتخاب تب، سایدبار را ببند
                if (clickedEl && window.matchMedia('(max-width: 767px)').matches) {
                    closeSidebar();
                }
            })
            .catch(() => {
                contentArea.innerHTML = '<div class="text-red-500 p-4">خطا در بارگذاری محتوا.</div>';
            });
    }

    links.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            setActiveTab(this);
            loadTab(this.dataset.url, this);
        });
    });

    // تب پیش‌فرض: summary
    const defaultTab = document.querySelector('.load-tab[data-url*="summary"]');
    if (defaultTab) {
        setActiveTab(defaultTab);
        loadTab(defaultTab.dataset.url);
    }

    // کنترل سایدبار موبایل
    const sidebar = document.getElementById('mobileSidebar');
    const overlay = document.getElementById('mobileOverlay');
    const openBtn = document.getElementById('mobileMenuBtn');
    const closeBtn = document.getElementById('closeSidebarBtn');

    function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('translate-x-full');
        overlay?.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        openBtn?.setAttribute('aria-expanded', 'true');
    }

    openBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        const expanded = openBtn.getAttribute('aria-expanded') === 'true';
        expanded ? closeSidebar() : openSidebar();
    });
    closeBtn?.addEventListener('click', (e) => { e.preventDefault(); closeSidebar(); });
    overlay?.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && window.matchMedia('(max-width: 767px)').matches) closeSidebar();
    });
});
</script>

{{-- اسکریپت منشن‌ها (استاندارد با data-username) --}}
<script>
document.addEventListener('click', function (e) {
    const openBtn = e.target.closest('#openMentionBtn');
    if (openBtn) {
        const modal = document.getElementById('mentionModal');
        if (modal) { modal.classList.remove('hidden'); modal.classList.add('flex'); }

        const currentMentions = Array.from(document.querySelectorAll('input[name="mentions[]"]'))
                                     .map(input => input.value);
        document.querySelectorAll('.mention-checkbox').forEach(cb => {
            cb.checked = currentMentions.includes(cb.value);
        });
    }

    const cancelBtn = e.target.closest('#cancelMentionBtn');
    if (cancelBtn) {
        const modal = document.getElementById('mentionModal');
        if (modal) { modal.classList.remove('flex'); modal.classList.add('hidden'); }
    }

    const applyBtn = e.target.closest('#applyMentionBtn');
    if (applyBtn) {
        const checkboxes = document.querySelectorAll('.mention-checkbox:checked');
        const selectedUsers = Array.from(checkboxes).map(cb => ({
            username: cb.value,
            name: cb.dataset.name || cb.value
        }));

        const textarea = document.querySelector('textarea[name="content"]');
        if (textarea) {
            const mentionsText = selectedUsers.map(u => '@' + u.name).join(' ');
            textarea.value = (textarea.value.trim() + '\n' + mentionsText).trim();
        }

        document.querySelectorAll('input[name="mentions[]"]').forEach(input => input.remove());
        const form = document.getElementById('noteForm');
        selectedUsers.forEach(u => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'mentions[]';
            hiddenInput.value = u.username;
            form?.appendChild(hiddenInput);
        });

        const selectedMentions = document.getElementById('selectedMentions');
        if (selectedMentions) {
            selectedMentions.innerHTML = selectedUsers.map(u => `
                <span class="inline-flex items-center bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full text-xs ml-1 mb-1">
                    ${u.name}
                    <button type="button" class="ml-[5px] text-red-600 hover:text-red-800 font-bold remove-mention" data-username="${u.username}">&times;</button>
                </span>
            `).join(' ');
        }

        const modal = document.getElementById('mentionModal');
        if (modal) { modal.classList.remove('flex'); modal.classList.add('hidden'); }
    }

    if (e.target.classList.contains('remove-mention')) {
        const username = e.target.dataset.username;
        document.querySelectorAll('input[name="mentions[]"]').forEach(input => {
            if (input.value === username) input.remove();
        });

        const remainingInputs = Array.from(document.querySelectorAll('input[name="mentions[]"]'));
        const updatedUsers = remainingInputs.map(input => {
            const cb = document.querySelector(`.mention-checkbox[value="${input.value}"]`);
            return { username: input.value, name: cb?.dataset.name || input.value };
        });

        const selectedMentions = document.getElementById('selectedMentions');
        if (selectedMentions) {
            selectedMentions.innerHTML = updatedUsers.map(u => `
                <span class="inline-flex items-center bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full text-xs ml-1 mb-1">
                    ${u.name}
                    <button type="button" class="ml-[5px] text-red-600 hover:text-red-800 font-bold remove-mention" data-username="${u.username}">&times;</button>
                </span>
            `).join(' ');
        }
    }
});
</script>
@endpush
