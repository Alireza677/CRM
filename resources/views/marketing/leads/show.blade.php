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
                       data-url="{{ route('marketing.leads.tab', ['lead' => $lead->id, 'tab' => 'overview'], false) }}"
                       class="load-tab flex items-center justify-between px-3 py-2 rounded bg-blue-100 text-blue-800 font-semibold">
                        <span class="flex items-center space-x-2 rtl:space-x-reverse">
                            <i class="fas fa-th-large"></i>
                            <span>خلاصه</span>
                        </span>
                    </a>

                    <a href="#"
                    data-tab="info"

                       data-url="{{ route('marketing.leads.tab', ['lead' => $lead->id, 'tab' => 'info'], false) }}"
                       class="load-tab flex items-center justify-between px-3 py-2 rounded text-gray-700 hover:bg-gray-100">
                        <span class="flex items-center space-x-2 rtl:space-x-reverse">
                            <i class="fas fa-info-circle"></i>
                            <span>اطلاعات</span>
                        </span>
                    </a>

                    <a href="#"
                    data-tab="updates"

                       data-url="{{ route('marketing.leads.tab', ['lead' => $lead->id, 'tab' => 'updates'], false) }}"
                       class="load-tab flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-sync-alt text-gray-500"></i>
                        <span>بروزرسانی‌ها</span>
                    </a>

                    <a href="#"
                    data-tab="notes"

                       data-url="{{ route('marketing.leads.tab', ['lead' => $lead->id, 'tab' => 'notes'], false) }}"
                       class="load-tab flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-sticky-note text-gray-500"></i>
                        <span>یادداشت‌ها</span>
                    </a>

                    <a href="#"
                    data-tab="contact"

                       data-url="{{ route('marketing.leads.tab', ['lead' => $lead->id, 'tab' => 'contact'], false) }}"
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

    const activeClasses = ['bg-blue-100', 'text-blue-800', 'font-semibold'];
    const inactiveClasses = ['text-gray-700', 'hover:bg-gray-100'];

    function initReassignTimer(scope = document) {
    const el = scope.querySelector('#reassign-countdown');
    if (!el) return;

    const remainingAttr = el.dataset.remaining;
    const deadlineStr = el.dataset.deadline;
    const deadline = deadlineStr ? new Date(deadlineStr) : null;
    const hasDeadline = deadline && !isNaN(deadline.getTime());
    let remainingSeconds = remainingAttr !== undefined ? parseInt(remainingAttr, 10) : NaN;

    if (!hasDeadline && (Number.isNaN(remainingSeconds) || remainingSeconds < 0)) {
        el.textContent = '--';
        return;
    }

// جلوگیری از چندباره شدن interval
    if (el._reassignInterval) {
        clearInterval(el._reassignInterval);
        el._reassignInterval = null;
    }

    const formatRemaining = (seconds) => {
        if (seconds <= 0) return '00:00:00';
        const h = String(Math.floor(seconds / 3600)).padStart(2, '0');
        const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
        const s = String(seconds % 60).padStart(2, '0');
        return `${h}:${m}:${s}`;
    };

    const tick = () => {
        const currentRemaining = hasDeadline
            ? Math.max(0, Math.floor((deadline - new Date()) / 1000))
            : Math.max(0, remainingSeconds);

        el.textContent = formatRemaining(currentRemaining);

        if (!hasDeadline) {
            remainingSeconds = currentRemaining - 1;
        }

        if (currentRemaining <= 0) {
            el.classList.remove('bg-amber-600');
            el.classList.add('bg-red-600');
            clearInterval(el._reassignInterval);
            el._reassignInterval = null;
        }
    };

tick(); // یک‌بار اجرا برای نمایش سریع قبل از شروع interval
    el._reassignInterval = setInterval(tick, 1000);
}



    function setActiveTab(el) {
        links.forEach(l => {
            l.classList.remove(...activeClasses);
            if (!l.classList.contains('text-gray-700')) {
                l.classList.add('text-gray-700');
            }
        });
        el.classList.add(...activeClasses);
        el.classList.remove('text-gray-700');
    }

    
    function initLeadContactModal(scope = document) {
        const modal = scope.querySelector('#leadContactModal');
        if (!modal) return;

        const searchInput = modal.querySelector('#leadContactSearchInput');
        const rows = Array.from(modal.querySelectorAll('#leadContactTableBody tr'));
        const emptyState = modal.querySelector('#leadContactNoResults');

        function applyFilter() {
            const term = (searchInput?.value || '').trim().toLowerCase();
            let visible = 0;
            rows.forEach((row) => {
                const name = (row.dataset.name || '').toLowerCase();
                const phone = (row.dataset.phone || '').toLowerCase();
                const match = !term || name.includes(term) || phone.includes(term);
                row.classList.toggle('hidden', !match);
                if (match) visible += 1;
            });
            emptyState?.classList.toggle('hidden', visible !== 0);
        }

        window.openLeadContactModal = function () {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            if (searchInput) {
                searchInput.value = '';
                applyFilter();
                setTimeout(() => searchInput.focus(), 10);
            }
        };

        window.closeLeadContactModal = function () {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        window.handleLeadContactSelect = async function (id) {
            const attachUrl = modal.dataset.attachUrl;
            if (!attachUrl) return;
            const csrf = modal.dataset.csrf || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const res = await fetch(attachUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                    },
                    body: JSON.stringify({ contact_id: id }),
                });
                if (!res.ok) {
                    const payload = await res.json().catch(() => ({}));
                    throw new Error(payload.message || 'خطا در اتصال مخاطب.');

                }
                window.closeLeadContactModal?.();
                window.reloadLeadContactTab?.();
            } catch (err) {
                console.error('[LeadContact] attach failed', err);
                alert(err?.message || 'خطا در اتصال مخاطب.');
            }
        };

        if (searchInput && !searchInput.dataset.bound) {
            searchInput.addEventListener('input', applyFilter);
            searchInput.dataset.bound = '1';
        }

        if (!modal.dataset.bound) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) window.closeLeadContactModal?.();
            });
            modal.dataset.bound = '1';
        }

        if (!window._leadContactModalEscapeBound) {
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') window.closeLeadContactModal?.();
            });
            window._leadContactModalEscapeBound = true;
        }
    }

    function loadTab(url, clickedEl = null) { console.log('[LeadTabs] fetching', url); contentArea.innerHTML = '<div class="text-gray-400 p-4 flex items-center gap-2">' + '<svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24">' + '<circle cx="12" cy="12" r="10" stroke="currentColor" fill="none" stroke-width="4" opacity=".25"></circle>' + '<path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" fill="none"></path>' + '</svg>' + 'در حال بارگذاری...' + '</div>'; fetch(url) .then(async (res) => { if (!res.ok) { const body = await res.text(); console.error('[LeadTabs] not ok', res.status, res.statusText, body); throw new Error('http_' + res.status); } return res.text(); }) .then(html => { contentArea.innerHTML = html; initReassignTimer(contentArea); initLeadContactModal(contentArea); if (clickedEl && window.matchMedia('(max-width: 767px)').matches) { closeSidebar(); } }) .catch((err) => { console.error('[LeadTabs] failed', err); contentArea.innerHTML = '<div class="text-red-500 p-4">خطا در بارگذاری اطلاعات.</div>'; }); }


    links.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            setActiveTab(this);
            const url = this.dataset.url;
            loadTab(url, this);
        });
    });

    window.reloadLeadContactTab = function () {
        const contactTab = document.querySelector('.load-tab[data-tab="contact"]');
        if (contactTab) {
            setActiveTab(contactTab);
            loadTab(contactTab.dataset.url, contactTab);
        }
    };

    contentArea?.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;

        const action = btn.dataset.action;
        const contactId = btn.dataset.contactId;
        const container = btn.closest('[data-detach-url][data-primary-url]');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!contactId || !container) return;

        if (action === 'detach-contact') {
            if (!confirm('آیا از جداسازی این مخاطب مطمئن هستید؟')) return;
            const url = container.dataset.detachUrl;
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                    },
                    body: JSON.stringify({ contact_id: contactId }),
                });
                if (!res.ok) {
                    const payload = await res.json().catch(() => ({}));
                    throw new Error(payload.message || 'خطا در جداسازی مخاطب.');
                }
                window.reloadLeadContactTab?.();
            } catch (err) {
                console.error('[LeadContact] detach failed', err);
                alert(err?.message || 'خطا در جداسازی مخاطب.');
            }
            return;
        }

        if (action === 'set-primary') {
            const url = container.dataset.primaryUrl;
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                    },
                    body: JSON.stringify({ contact_id: contactId }),
                });
                if (!res.ok) {
                    const payload = await res.json().catch(() => ({}));
                    throw new Error(payload.message || 'خطا در تعیین مخاطب اصلی.');
                }
                window.reloadLeadContactTab?.();
            } catch (err) {
                console.error('[LeadContact] set primary failed', err);
                alert(err?.message || 'خطا در تعیین مخاطب اصلی.');
            }
        }
    });

    const defaultTab = document.querySelector('.load-tab[data-url*="overview"]');
    if (defaultTab) {
        setActiveTab(defaultTab);
        loadTab(defaultTab.dataset.url);
    }

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
// منشن داخل متن یادداشت (مشابه گفتگو)
document.addEventListener('DOMContentLoaded', () => {
    const mentionDataEl = document.getElementById('mentionData');
    const mentionCandidates = mentionDataEl ? JSON.parse(mentionDataEl.dataset.mentionCandidates || '[]') : [];
    const messageBody = document.querySelector('textarea[name="body"]');
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
