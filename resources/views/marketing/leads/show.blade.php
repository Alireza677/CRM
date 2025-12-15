@extends('layouts.app')

@php
    $showReengagedBadge = (bool) ($lead->is_reengaged ?? false);
    $isWebsiteSource = $lead->lead_source === 'website';
@endphp

@section('content')

<div class="bg-gray-100">
{{-- هدر و دکمه‌های موبایل (یک‌پارچه) --}}
<div class="flex items-center justify-between px-4 py-3 md:hidden">
  <h1 class="text-lg font-bold text-gray-800 truncate flex items-center gap-2">
    <span>سرنخ فروش: {{ $lead->full_name }}</span>
    @if($showReengagedBadge)
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium {{ $isWebsiteSource ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-700' }}">
            بازگشتی از وب‌سایت
        </span>
    @endif
  </h1>
  <div class="flex items-center gap-2">
    {{-- ویرایش --}}
    <a href="{{ route('marketing.leads.edit', $lead) }}"
       class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-gray-300 bg-white hover:bg-gray-50"
       title="ویرایش">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
      </svg>
    </a>

    {{-- حذف --}}
    <form action="{{ route('marketing.leads.destroy', $lead) }}" method="POST"
          onsubmit="return confirm('آیا از حذف این سرنخ فروش اطمینان دارید؟')">
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

    {{-- همبرگری (فقط یک ID) --}}
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


    <div class="flex">
        {{-- سایدبار (راست) - ثابت در دسکتاپ، آف‌کانواس در موبایل --}}
        <div id="mobileOverlay" class="fixed inset-0 bg-black/40 z-40 hidden md:hidden"></div>

        <aside id="mobileSidebar"
  class="fixed right-0 top-[105px] h-[calc(100vh-115px)] w-72 bg-white shadow-lg z-40 border-l
         transform translate-x-full transition-transform duration-200 ease-out
         md:translate-x-0 md:w-64 md:overflow-y-auto">

            <div class="p-4">
                <div class="flex items-center justify-between mb-2 md:mb-4">
                    <h2 class="text-m font-bold text-gray-600">{{ $lead->first_name }} {{ $lead->last_name }}</h2>
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
                    {{-- فقط تب خلاصه پیش‌فرض انتخاب باشد --}}
                    <a href="#"
                    data-tab="overview"       
                       data-url="{{ route('marketing.leads.tab', ['lead' => $lead->id, 'tab' => 'overview']) }}"
                       class="load-tab flex items-center justify-between px-3 py-2 rounded bg-blue-100 text-blue-800 font-semibold">
                        <span class="flex items-center space-x-2 rtl:space-x-reverse">
                            <i class="fas fa-th-large"></i>
                            <span>خلاصه</span>
                        </span>
                    </a>

                    <a href="#"
                    data-tab="info"

                       data-url="{{ route('marketing.leads.tab', ['lead' => $lead->id, 'tab' => 'info']) }}"
                       class="load-tab flex items-center justify-between px-3 py-2 rounded text-gray-700 hover:bg-gray-100">
                        <span class="flex items-center space-x-2 rtl:space-x-reverse">
                            <i class="fas fa-info-circle"></i>
                            <span>اطلاعات</span>
                        </span>
                    </a>

                    <a href="#"
                    data-tab="updates"

                       data-url="{{ route('marketing.leads.tab', ['lead' => $lead->id, 'tab' => 'updates']) }}"
                       class="load-tab flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-sync-alt text-gray-500"></i>
                        <span>بروزرسانی‌ها</span>
                    </a>

                    <a href="#"
                    data-tab="notes"

                       data-url="{{ route('marketing.leads.tab', ['lead' => $lead->id, 'tab' => 'notes']) }}"
                       class="load-tab flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-sticky-note text-gray-500"></i>
                        <span>یادداشت‌ها</span>
                    </a>

                    <a href="#"
                    data-tab="contact"

                       data-url="{{ route('marketing.leads.tab', ['lead' => $lead->id, 'tab' => 'contact']) }}"
                       class="load-tab flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-user-friends text-gray-500"></i>
                        <span>مخاطب مرتبط</span>
                    </a>
                </nav>
            </div>
        </aside>

        {{-- محتوای اصلی --}}
<main class="flex-1 px-4 md:px-8 pb-8 mr-0 md:mr-64">
        {{-- هدر دسکتاپ --}}
            <div class="hidden md:flex justify-between items-center mb-6 mt-8">
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                    <span>سرنخ فروش: {{ $lead->full_name }}</span>
                    @if($showReengagedBadge)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium {{ $isWebsiteSource ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-700' }}">
                            بازگشتی از وب‌سایت
                        </span>
                    @endif
                </h1>
                <div class="flex space-x-4 rtl:space-x-reverse">
                    <a href="{{ route('marketing.leads.edit', $lead) }}"
                       class="text-blue-600 hover:text-blue-800" title="ویرایش">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </a>
                    <form action="{{ route('marketing.leads.destroy', $lead) }}" method="POST" class="inline"
                          onsubmit="return confirm('آیا از حذف این سرنخ فروش اطمینان دارید؟')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800" title="حذف">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
                  
            {{-- محتوای تب‌ها --}}
            <div id="lead-tab-content" class="bg-white rounded-lg shadow p-4">
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
    const contentArea = document.getElementById('lead-tab-content');

    // کلاس‌های انتخاب تب
    const activeClasses = ['bg-blue-100', 'text-blue-800', 'font-semibold'];
    const inactiveClasses = ['text-gray-700', 'hover:bg-gray-100'];

    function setActiveTab(el) {
        links.forEach(l => {
            l.classList.remove(...activeClasses);
            // فقط اگر لازم بود کلاس‌های غیرفعال را اضافه کنیم
            if (!l.classList.contains('text-gray-700')) {
                l.classList.add('text-gray-700');
            }
        });
        el.classList.add(...activeClasses);
        el.classList.remove('text-gray-700');
    }

    // لود تب با fetch
    function loadTab(url, clickedEl = null) {
        contentArea.innerHTML = '<div class="text-gray-400 p-4 flex items-center gap-2"><svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" fill="none" stroke-width="4" opacity=".25"></circle><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" fill="none"></path></svg>در حال بارگذاری...</div>';

        fetch(url)
            .then(res => res.text())
            .then(html => {
                contentArea.innerHTML = html;

                // اگر از موبایل باز شده بود، بعد از انتخاب تب، منو بسته شود
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
            const url = this.dataset.url;
            loadTab(url, this);
        });
    });

    // لود پیش‌فرض تب overview
    const defaultTab = document.querySelector('.load-tab[data-url*="overview"]');
    if (defaultTab) {
        setActiveTab(defaultTab);
        loadTab(defaultTab.dataset.url);
    }

    // --- کنترل سایدبار موبایل (آف‌کانواس) ---
    const sidebar = document.getElementById('mobileSidebar');
    const overlay = document.getElementById('mobileOverlay');
    const openBtn = document.getElementById('mobileMenuBtn');
    const closeBtn = document.getElementById('closeSidebarBtn');

    function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('translate-x-full');
        overlay?.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        if (openBtn) openBtn.setAttribute('aria-expanded', 'true');
    }

    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('translate-x-full');
        overlay?.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        if (openBtn) openBtn.setAttribute('aria-expanded', 'false');
    }

    openBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        const expanded = openBtn.getAttribute('aria-expanded') === 'true';
        expanded ? closeSidebar() : openSidebar();
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

{{-- اسکریپت منشن‌ها (اصلاح ناهمگونی data-*) --}}
<script>
document.addEventListener('click', function (e) {
    const openBtn = e.target.closest('#openMentionBtn');
    if (openBtn) {
        const modal = document.getElementById('mentionModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // بازگردانی انتخاب‌های قبلی (بر اساس username)
        const currentMentions = Array.from(document.querySelectorAll('input[name="mentions[]"]'))
                                     .map(input => input.value);
        document.querySelectorAll('.mention-checkbox').forEach(cb => {
            cb.checked = currentMentions.includes(cb.value);
        });
    }

    const cancelBtn = e.target.closest('#cancelMentionBtn');
    if (cancelBtn) {
        const modal = document.getElementById('mentionModal');
        if (modal) {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
    }

    const applyBtn = e.target.closest('#applyMentionBtn');
    if (applyBtn) {
        const checkboxes = document.querySelectorAll('.mention-checkbox:checked');
        const selectedUsers = Array.from(checkboxes).map(cb => ({
            username: cb.value,                 // مقدار input چک‌باکس = username
            name: cb.dataset.name || cb.value   // نام نمایشی
        }));

        // افزودن @mentions به متن
        const textarea = document.querySelector('textarea[name="content"]');
        if (textarea) {
            const mentionsText = selectedUsers.map(u => '@' + u.name).join(' ');
            textarea.value = (textarea.value.trim() + '\n' + mentionsText).trim();
        }

        // پاک کردن hidden های قبلی و افزودن جدید
        document.querySelectorAll('input[name="mentions[]"]').forEach(input => input.remove());
        const form = document.getElementById('noteForm');
        selectedUsers.forEach(u => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'mentions[]';
            hiddenInput.value = u.username;
            form?.appendChild(hiddenInput);
        });

        // نمایش بصری منشن‌ها
        const selectedMentions = document.getElementById('selectedMentions');
        if (selectedMentions) {
            selectedMentions.innerHTML = selectedUsers.map(u => `
                <span class="inline-flex items-center bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full text-xs ml-1 mb-1">
                    ${u.name}
                    <button type="button" class="ml-[5px] text-red-600 hover:text-red-800 font-bold remove-mention"
                            data-username="${u.username}">&times;</button>
                </span>
            `).join(' ');
        }

        const modal = document.getElementById('mentionModal');
        if (modal) {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
    }

    // حذف یک منشن (بر اساس data-username)
    if (e.target.classList.contains('remove-mention')) {
        const username = e.target.dataset.username;

        // حذف hidden input مربوط به این username
        document.querySelectorAll('input[name="mentions[]"]').forEach(input => {
            if (input.value === username) {
                input.remove();
            }
        });

        // بازسازی نمایش
        const remainingInputs = Array.from(document.querySelectorAll('input[name="mentions[]"]'));
        const updatedUsers = remainingInputs.map(input => {
            const cb = document.querySelector(`.mention-checkbox[value="${input.value}"]`);
            return {
                username: input.value,
                name: cb?.dataset.name || input.value
            };
        });

        const selectedMentions = document.getElementById('selectedMentions');
        if (selectedMentions) {
            selectedMentions.innerHTML = updatedUsers.map(u => `
                <span class="inline-flex items-center bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full text-xs ml-1 mb-1">
                    ${u.name}
                    <button type="button" class="ml-[5px] text-red-600 hover:text-red-800 font-bold remove-mention"
                            data-username="${u.username}">&times;</button>
                </span>
            `).join(' ');
        }
    }
});
</script>
@endpush
