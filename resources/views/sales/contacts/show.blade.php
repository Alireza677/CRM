@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'داشبورد', 'url' => route('dashboard')],
        ['title' => 'مخاطبین', 'url' => route('sales.contacts.index')],
        ['title' => $contact->first_name . ' ' . $contact->last_name],
    ];
@endphp

<div class="bg-gray-100" dir="rtl">
    <div class="flex">
        <div id="mobileOverlay" class="fixed inset-0 bg-black/40 z-40 hidden md:hidden"></div>

        <aside id="mobileSidebar"
                 class="fixed right-0 top-[105px] h-[calc(100vh-115px)] w-72 bg-white shadow-lg z-40 border-l
         transform translate-x-full transition-transform duration-200 ease-out
         md:translate-x-0 md:w-64 md:overflow-y-auto"> 
            <div class="p-4">
                <div class="flex items-center justify-between mb-2 md:mb-4">
                    <h2 class="text-m font-bold text-gray-600">
                        {{ $contact->first_name }} {{ $contact->last_name }}
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
                    <a href="#" data-url="{{ route('sales.contacts.tab', ['contact' => $contact->id, 'tab' => 'info']) }}"
                       class="load-tab flex items-center justify-between px-3 py-2 rounded bg-blue-100 text-blue-800 font-semibold">
                        <span class="flex items-center space-x-2 rtl:space-x-reverse">
                            <i class="fas fa-info-circle"></i>
                            اطلاعات
                        </span>
                    </a>
                    <a href="#" data-url="{{ route('sales.contacts.tab', ['contact' => $contact->id, 'tab' => 'opportunities']) }}"
                       class="load-tab flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-briefcase text-gray-500"></i><span>فرصت‌های مرتبط</span>
                    </a>
                    <a href="#" data-url="{{ route('sales.contacts.tab', ['contact' => $contact->id, 'tab' => 'proformas']) }}"
                       class="load-tab flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-file-invoice text-gray-500"></i><span>پیش‌فاکتور</span>
                    </a>
                    <a href="#" data-url="{{ route('sales.contacts.tab', ['contact' => $contact->id, 'tab' => 'updates']) }}"
                       class="load-tab flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        <i class="fas fa-sync-alt text-gray-500"></i><span>به‌روزرسانی‌ها</span>
                    </a>
                </nav>
            </div>
        </aside>

<main class="flex-1 px-4 md:px-8 pb-8 mr-0 md:mr-64">
            <div class="hidden md:flex justify-between items-center mb-6 mt-8">
                <h1 class="text-2xl font-bold text-gray-800">
                    مخاطب: {{ $contact->first_name }} {{ $contact->last_name }}
                </h1>
                <div class="flex gap-2">
                    <a href="{{ route('sales.contacts.edit', $contact->id) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-gray-200 shadow-sm hover:bg-gray-50 text-gray-700 text-sm"
                       title="ویرایش مخاطب">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span class="hidden sm:inline">ویرایش</span>
                    </a>
                </div>
            </div>

            <div id="contact-tab-content" class="bg-white rounded-lg shadow p-4">
                <div class="text-gray-500 text-sm">درحال بارگذاری...</div>
            </div>
        </main>
    </div>
    
    <!-- Floating open button for mobile -->
    <div class="md:hidden fixed left-4 top-[110px] z-50">
        <button id="mobileMenuBtn" aria-expanded="false"
                class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-gray-300 bg-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    const links = document.querySelectorAll('.load-tab');
    const contentArea = document.getElementById('contact-tab-content');

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

    function openSidebar() {
        const sidebar = document.getElementById('mobileSidebar');
        const overlay = document.getElementById('mobileOverlay');
        const openBtn = document.getElementById('mobileMenuBtn');
        if (!sidebar) return;
        sidebar.classList.remove('translate-x-full');
        overlay?.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        openBtn?.setAttribute('aria-expanded', 'true');
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

    const defaultTab = document.querySelector('.load-tab');
    if (defaultTab) {
        setActiveTab(defaultTab);
        loadTab(defaultTab.dataset.url);
    }

    const sidebar = document.getElementById('mobileSidebar');
    const overlay = document.getElementById('mobileOverlay');
    const openBtn = document.getElementById('mobileMenuBtn');
    const closeBtn = document.getElementById('closeSidebarBtn');

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
@endpush

