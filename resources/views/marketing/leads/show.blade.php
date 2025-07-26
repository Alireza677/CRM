@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'سرنخ‌های فروش', 'url' => route('marketing.leads.index')],
        ['title' => 'جزئیات سرنخ']
    ];
@endphp

    <div class="flex flex-row-reverse bg-gray-100">
      <!-- Sidebar (Right) -->
<div class="w-64 bg-white shadow-lg fixed right-0 top-[120px] h-[calc(100vh-4rem)] z-40 overflow-y-auto border-l">
    <div class="p-4">
        <h2 class="text-m font-bold text-gray-600 mb-4"> {{ $lead->first_name }} {{ $lead->last_name }}</h2>
        <nav class="space-y-1">
            <a href="#"
                data-url="{{ route('marketing.leads.tab', ['lead' => $lead->id, 'tab' => 'overview']) }}"
                class="load-tab flex items-center justify-between px-3 py-2 rounded bg-blue-100 text-blue-800 font-semibold">
                <span class="flex items-center space-x-2 rtl:space-x-reverse">
                    <i class="fas fa-th-large"></i>
                    <span>خلاصه</span>
                </span>
            </a>

            <a href="#"
               data-url="{{ route('marketing.leads.tab', ['lead' => $lead->id, 'tab' => 'info']) }}"
               class="load-tab flex items-center justify-between px-3 py-2 rounded bg-blue-100 text-blue-800 font-semibold">
                <span class="flex items-center space-x-2 rtl:space-x-reverse">
                    <i class="fas fa-info-circle"></i>
                    <span>اطلاعات</span>
                </span>
            </a>

            <a href="#"
               data-url="{{ route('marketing.leads.tab', ['lead' => $lead->id, 'tab' => 'updates']) }}"
               class="load-tab flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">
                <i class="fas fa-sync-alt text-gray-500"></i>
                <span>بروزرسانی‌ها</span>
            </a>

            <a href="#"
               data-url="{{ route('marketing.leads.tab', ['lead' => $lead->id, 'tab' => 'notes']) }}"
               class="load-tab flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">
                <i class="fas fa-sticky-note text-gray-500"></i>
                <span>یادداشت‌ها</span>
            </a>
        </nav>
    </div>
</div>

        <!-- Main Content -->
        <div class="flex-1 ml-0 md:ml-64 p-8">
    <!-- Header with Title and Actions -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">سرنخ فروش: {{ $lead->full_name }}</h1>
        <div class="flex space-x-4">
            <a href="{{ route('marketing.leads.edit', $lead) }}" 
               class="text-blue-600 hover:text-blue-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </a>
            <form action="{{ route('marketing.leads.destroy', $lead) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" 
                        class="text-red-600 hover:text-red-800"
                        onclick="return confirm('آیا از حذف این سرنخ فروش اطمینان دارید؟')">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

    </div>

    <!-- تب‌ها در این بخش لود می‌شن -->
    <div id="lead-tab-content">
        <div class="text-gray-500 text-sm">در حال بارگذاری...</div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const links = document.querySelectorAll('.load-tab');
        const contentArea = document.getElementById('lead-tab-content');

        links.forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();

                links.forEach(l => l.classList.remove('bg-blue-100', 'text-blue-800', 'font-semibold'));
                this.classList.add('bg-blue-100', 'text-blue-800', 'font-semibold');

                const url = this.dataset.url;
                contentArea.innerHTML = '<div class="text-gray-400 p-4">در حال بارگذاری...</div>';

                fetch(url)
                    .then(res => res.text())
                    .then(html => {
                        contentArea.innerHTML = html;
                    })
                    .catch(() => {
                        contentArea.innerHTML = '<div class="text-red-500 p-4">خطا در بارگذاری محتوا.</div>';
                    });
            });
        });

        // لود پیش‌فرض تب
        document.querySelector('.load-tab[data-url*="overview"]')?.click();
    });
</script>
@endpush
