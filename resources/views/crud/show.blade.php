@extends('layouts.app')

@section('content')
@php
    $title = $schema['title'] ?? 'نمایش';
    $tabs = $schema['show']['tabs'] ?? [];
    $activeTab = $tabs[$selectedTab] ?? null;
    $nameCandidate = $model->name ?? null;
    $fullNameCandidate = $model->full_name ?? null;
    $subjectCandidate = $model->subject ?? null;
    $proformaNumberCandidate = $model->proforma_number ?? null;
    $displayName = trim((string) ($nameCandidate ?? '')) !== '' ? $nameCandidate
        : (trim((string) ($fullNameCandidate ?? '')) !== '' ? $fullNameCandidate
        : (trim((string) ($subjectCandidate ?? '')) !== '' ? $subjectCandidate
        : (trim((string) ($proformaNumberCandidate ?? '')) !== '' ? $proformaNumberCandidate
        : ('#' . $model->id))));
    $breadcrumb = $breadcrumb ?? [
        [
            'title' => $title,
            'url' => !empty($schema['routes']['index']) ? route($schema['routes']['index']) : null,
        ],
        [
            'title' => $displayName,
        ],
    ];
@endphp

<div class="bg-gray-100" dir="rtl">
    {{-- هدر و دکمه‌های موبایل --}}
    <div class="flex items-center justify-between px-4 py-3 md:hidden">
        <h1 class="text-lg font-bold text-gray-800 truncate">
            {{ $title }}: {{ $displayName }}
        </h1>
        <div class="flex items-center gap-2">
            @if(!empty($schema['routes']['edit']))
                <a href="{{ route($schema['routes']['edit'], $model) }}"
                   class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-gray-300 bg-white hover:bg-gray-50"
                   title="ویرایش">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </a>
            @endif
            @if(!empty($schema['routes']['destroy']))
                <form action="{{ route($schema['routes']['destroy'], $model) }}" method="POST"
                      onsubmit="return confirm('آیا از حذف این مورد اطمینان دارید؟')">
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
            @endif
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
        <div id="mobileOverlay" class="fixed inset-0 bg-black/40 z-40 hidden md:hidden"></div>
        <aside id="mobileSidebar"
               class="fixed right-0 top-[105px] h-[calc(100vh-115px)] w-72 bg-white shadow-lg z-40 border-l
                      transform translate-x-full transition-transform duration-200 ease-out
                      md:translate-x-0 md:w-64 md:overflow-y-auto">
            <div class="p-4">
                <div class="flex items-center justify-between mb-2 md:mb-4">
                    <h2 class="text-m font-bold text-gray-600">{{ $displayName }}</h2>
                    <button id="closeSidebarBtn"
                            class="md:hidden inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-gray-100"
                            aria-label="بستن منو">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                @include('crud.partials.tabs')
            </div>
        </aside>

        <main class="flex-1 px-4 md:px-8 pb-8 mr-0 md:mr-64">
            <div class="hidden md:flex justify-between items-center mb-6 mt-8">
                <h1 class="text-2xl font-bold text-gray-800">
                    {{ $title }}: {{ $displayName }}
                </h1>
                <div class="flex space-x-4 rtl:space-x-reverse">
                    @if(!empty($schema['routes']['edit']))
                        <a href="{{ route($schema['routes']['edit'], $model) }}"
                           class="text-blue-600 hover:text-blue-800" title="ویرایش">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                    @endif
                    @if(!empty($schema['routes']['destroy']))
                        <form action="{{ route($schema['routes']['destroy'], $model) }}" method="POST" class="inline"
                              onsubmit="return confirm('آیا از حذف این مورد اطمینان دارید؟')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800" title="حذف">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                @if($activeTab)
                    @php $mode = $activeTab['view_mode'] ?? 'cards'; @endphp
                    @if(!empty($activeTab['view']))
                        @include($activeTab['view'], array_merge(['model' => $model, 'opportunity' => $model], $tabData ?? []))
                    @elseif($mode === 'cards')
                        @include('crud.partials.cards', ['blocks' => $activeTab['blocks'] ?? []])
                    @elseif($mode === 'table')
                        @include('crud.partials.cards', ['blocks' => $activeTab['blocks'] ?? []])
                    @else
                        @include('crud.partials.cards', ['blocks' => $activeTab['blocks'] ?? []])
                    @endif
                @else
                    <div class="text-sm text-gray-500">تب مورد نظر یافت نشد.</div>
                @endif
            </div>
        </main>
    </div>
</div>
@endsection

@if(($key ?? null) === 'leads')
    @include('marketing.leads.partials.tab-scripts')
@endif
@if(($key ?? null) === 'proformas')
    @include('sales.proformas.partials.tab-scripts')
@endif

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    window.reloadOpportunityContactTab = function () { window.location.reload(); };
    window.reloadOpportunityProformaTab = function () { window.location.reload(); };

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

    closeBtn?.addEventListener('click', closeSidebar);
    overlay?.addEventListener('click', closeSidebar);

    document.addEventListener('click', function (e) {
        const card = e.target.closest('[data-card-tab]');
        if (!card) return;
        const tab = card.getAttribute('data-card-tab');
        if (!tab) return;
        const link = document.querySelector(`a[href*="tab=${tab}"]`);
        if (link) {
            window.location = link.getAttribute('href');
            return;
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        const card = e.target.closest?.('[data-card-tab]');
        if (!card) return;
        const tab = card.getAttribute('data-card-tab');
        if (!tab) return;
        const link = document.querySelector(`a[href*="tab=${tab}"]`);
        if (link) {
            window.location = link.getAttribute('href');
        }
    });

    function initOpportunityContactTab(scope = document) {
        const modal = scope.querySelector('#opportunityContactModal');
        if (!modal) return;

        const searchInput = modal.querySelector('#opportunityContactSearchInput');
        const rows = Array.from(modal.querySelectorAll('#opportunityContactTableBody tr'));
        const emptyState = modal.querySelector('#opportunityContactNoResults');

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

        window.openOpportunityContactModal = function () {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            if (searchInput) {
                searchInput.value = '';
                applyFilter();
                setTimeout(() => searchInput.focus(), 10);
            }
        };

        window.closeOpportunityContactModal = function () {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        window.handleOpportunityContactSelect = async function (id) {
            const attachUrl = modal.dataset.attachUrl;
            if (!attachUrl) return;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

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

                window.closeOpportunityContactModal?.();
                window.reloadOpportunityContactTab?.();
            } catch (err) {
                console.error('[OpportunityContact] attach failed', err);
                alert(err?.message || 'خطا در اتصال مخاطب.');
            }
        };

        if (searchInput && !searchInput.dataset.bound) {
            searchInput.addEventListener('input', applyFilter);
            searchInput.dataset.bound = '1';
        }

        if (!modal.dataset.bound) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) window.closeOpportunityContactModal?.();
            });
            modal.dataset.bound = '1';
        }

        if (!window._oppContactModalEscapeBound) {
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') window.closeOpportunityContactModal?.();
            });
            window._oppContactModalEscapeBound = true;
        }
    }

    function initOpportunityMentions() {
        const mentionDataEl = document.getElementById('mentionData');
        const mentionCandidates = mentionDataEl ? JSON.parse(mentionDataEl.dataset.mentionCandidates || '[]') : [];
        const messageBody = document.querySelector('textarea[name="content"]');
        const mentionDropdown = document.getElementById('mentionDropdown');
        const mentionList = document.getElementById('mentionList');
        const selectedMentions = document.getElementById('selectedMentions');

        if (!messageBody || !mentionDropdown || !mentionList) return;

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

        messageBody.addEventListener('input', () => {
            updateMentionDropdown();
            syncMentionsWithBody();
        });
        messageBody.addEventListener('click', updateMentionDropdown);
        messageBody.addEventListener('keydown', (event) => {
            if (mentionDropdown.classList.contains('hidden')) return;
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

        mentionList.addEventListener('click', (event) => {
            const target = event.target instanceof HTMLElement ? event.target.closest('li') : null;
            if (!target) return;
            const id = Number(target.dataset.mentionId || 0);
            const name = target.dataset.mentionName || '';
            const username = target.dataset.mentionUsername || '';
            if (!id) return;
            insertMention({ id, name, username });
        });

        document.addEventListener('click', (event) => {
            if (mentionDropdown.classList.contains('hidden')) return;
            if (mentionDropdown.contains(event.target) || messageBody.contains(event.target)) return;
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
    }

    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;

        const action = btn.dataset.action;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (action === 'detach-contact' || action === 'set-primary') {
            const contactId = btn.dataset.contactId;
            const container = btn.closest('[data-detach-url][data-primary-url]');
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

                    window.reloadOpportunityContactTab?.();
                } catch (err) {
                    console.error('[OpportunityContact] detach failed', err);
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

                    window.reloadOpportunityContactTab?.();
                } catch (err) {
                    console.error('[OpportunityContact] set primary failed', err);
                    alert(err?.message || 'خطا در تعیین مخاطب اصلی.');
                }
            }
            return;
        }

        if (action === 'set-primary-proforma') {
            const proformaId = btn.dataset.proformaId;
            const container = btn.closest('[data-primary-proforma-url]');
            if (!proformaId || !container) return;

            const url = container.dataset.primaryProformaUrl;

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                    },
                    body: JSON.stringify({ proforma_id: proformaId }),
                });

                if (!res.ok) {
                    const payload = await res.json().catch(() => ({}));
                    throw new Error(payload.message || 'خطا در تعیین پیش‌فاکتور اصلی.');
                }

                window.reloadOpportunityProformaTab?.();
            } catch (err) {
                console.error('[OpportunityProforma] set primary failed', err);
                alert(err?.message || 'خطا در تعیین پیش‌فاکتور اصلی.');
            }
        }
    });

    initOpportunityContactTab(document);
    initOpportunityMentions();
});
</script>
@endpush
