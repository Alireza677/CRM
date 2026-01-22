@extends('layouts.app')

@php
    $breadcrumb = [
        ['title' => 'داشبورد گزارش‌ها'],
    ];
@endphp

@section('content')
<div class="py-6" dir="rtl">
    @include('components.toast')
    <h1 class="text-2xl font-bold mb-4">داشبورد گزارش‌ها</h1>

    <!-- Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-6">
        <div class="p-4 rounded text-white" style="background:#2563eb">
            <div class="text-sm">گزارش‌های قابل مشاهده</div>
            <div class="text-2xl font-semibold">{{ number_format($totalVisible) }}</div>
        </div>
        <div class="p-4 rounded text-white" style="background:#10b981">
            <div class="text-sm">گزارش‌های خصوصی</div>
            <div class="text-2xl font-semibold">{{ number_format($privateCount) }}</div>
        </div>
        <div class="p-4 rounded text-white" style="background:#f59e0b">
            <div class="text-sm">گزارش‌های عمومی</div>
            <div class="text-2xl font-semibold">{{ number_format($publicCount) }}</div>
        </div>
        <div class="p-4 rounded text-white" style="background:#8b5cf6">
            <div class="text-sm">گزارش‌های اشتراکی</div>
            <div class="text-2xl font-semibold">{{ number_format($sharedCount) }}</div>
        </div>
        <div class="p-4 rounded text-white" style="background:#ef4444">
            <div class="text-sm">کل اجراها</div>
            <div class="text-2xl font-semibold">{{ number_format($totalRuns) }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Last runs -->
        <div class="bg-white rounded shadow p-4">
            <div class="font-semibold mb-2">آخرین اجراها</div>
            <div class="overflow-auto">
                <table class="min-w-full text-right">
                    <thead>
                        <tr>
                            <th class="px-2 py-1 bg-gray-50 border">عنوان گزارش</th>
                            <th class="px-2 py-1 bg-gray-50 border">تاریخ اجرا</th>
                            <th class="px-2 py-1 bg-gray-50 border">زمان اجرا (ms)</th>
                            <th class="px-2 py-1 bg-gray-50 border">استفاده از کش</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lastRuns as $run)
                            <tr class="border-b">
                                <td class="px-2 py-1">
                                    <a class="text-blue-700 hover:underline" href="{{ route('reports.run', $run->report_id) }}">
                                        {{ $run->report->title ?? '—' }}
                                    </a>
                                </td>
                                <td class="px-2 py-1">{{ jdate($run->executed_at)->format('Y/m/d H:i') }}</td>
                                <td class="px-2 py-1">{{ number_format($run->exec_ms) }} ms</td>
                                <td class="px-2 py-1">{{ $run->cache_used ? 'بله (از کش)' : 'خیر' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-2 py-4 text-gray-500 text-center">موردی یافت نشد</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pinned reports -->
        <div class="bg-white rounded shadow p-4">
            <div class="font-semibold mb-2">گزارش‌های سنجاق‌شده</div>
            <div class="overflow-auto">
                <table class="min-w-full text-right">
                    <thead>
                        <tr>
                            <th class="px-2 py-1 bg-gray-50 border">عنوان</th>
                            <th class="px-2 py-1 bg-gray-50 border">ایجادکننده</th>
                            <th class="px-2 py-1 bg-gray-50 border">سطح دسترسی</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pinned as $r)
                            <tr class="border-b">
                                <td class="px-2 py-1"><a class="text-blue-700 hover:underline" href="{{ route('reports.show',$r) }}">{{ $r->title }}</a></td>
                                <td class="px-2 py-1">{{ $r->creator->name ?? '—' }}</td>
                                <td class="px-2 py-1">
                                    {{ $r->visibility==='public' ? 'عمومی' : ($r->visibility==='shared' ? 'اشتراکی' : 'خصوصی') }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-2 py-4 text-gray-500 text-center">موردی یافت نشد</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Popular reports -->
        <div class="bg-white rounded shadow p-4">
            <div class="font-semibold mb-2">گزارش‌های پرطرفدار</div>
            <div class="overflow-auto">
                <table class="min-w-full text-right">
                    <thead>
                        <tr>
                            <th class="px-2 py-1 bg-gray-50 border">عنوان</th>
                            <th class="px-2 py-1 bg-gray-50 border">تعداد اجرا</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($popularReports as $pr)
                            <tr class="border-b">
                                <td class="px-2 py-1"><a class="text-blue-700 hover:underline" href="{{ route('reports.show',$pr['report']) }}">{{ $pr['report']->title }}</a></td>
                                <td class="px-2 py-1">{{ number_format($pr['runs']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="px-2 py-4 text-gray-500 text-center">موردی یافت نشد</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Line chart last 30 days -->
        <!-- Line chart last 30 days -->
            <div class="bg-white rounded shadow p-4">
            <div class="font-semibold mb-2">نمودار اجراها (۳۰ روز گذشته)</div>

            <!-- ظرف با ارتفاع ثابت -->
            <div class="relative h-64"> <!-- h-64 ≈ 256px، اگر خواستید h-80 -->
                <canvas id="runsChart" class="w-full h-full"></canvas>
            </div>

            @if(!config('app.assets_emergency'))
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            @endif
            <script>
                const chartData = @json($chart);
                const ctx = document.getElementById('runsChart').getContext('2d');

                // اگر صفحه چند بار رندر می‌شود، نمونه قبلی را نابود کن
                if (window._runsChart) { window._runsChart.destroy(); }
                if (!window.Chart) { return; }

                window._runsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                    label: 'تعداد اجرا',
                    data: chartData.data,
                    borderColor: 'rgba(37,99,235,0.9)',
                    backgroundColor: 'rgba(37,99,235,0.2)',
                    tension: 0.2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // چون ظرف‌مان ارتفاع دارد مشکلی نیست
                    resizeDelay: 150,           // جلوگیری از حلقه‌های ریز
                    animation: false,           // اختیاری: بی‌نیاز به انیمیشن
                    plugins: { legend: { rtl: true, position: 'top' } },
                    scales: { y: { beginAtZero: true } }
                }
                });
            </script>
            </div>
    </div>
</div>
@endsection
