@extends('layouts.app')

@php
    $dry = session('dry_run');
    $result = session('import_result');
    $uploaded = session('uploaded_path');
    $matchBy = old('match_by', session('match_by'));
@endphp

@section('content')
<h1>ایمپورت فرصت‌های فروش</h1>

@if ($result)
    <div class="max-w-3xl mx-auto mt-6 p-4 bg-green-50 text-green-800 border border-green-300 rounded">
        <div class="font-semibold mb-1">نتیجه نهایی واردسازی:</div>
        <div>ایجاد شده: <b>{{ $result['created'] ?? 0 }}</b> | بروزرسانی شده: <b>{{ $result['updated'] ?? 0 }}</b> | ناموفق: <b>{{ $result['failed'] ?? 0 }}</b></div>
        @if (!empty($result['failed_rows']))
            <details class="mt-2">
                <summary>نمونه خطاها</summary>
                <pre class="text-xs bg-white p-2 rounded border">{{ json_encode(array_slice($result['failed_rows'], 0, 10), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre>
            </details>
        @endif
    </div>
@endif

<div class="max-w-3xl mx-auto mt-10 p-6 bg-white shadow rounded">
    <h2 class="text-xl font-bold mb-4">ایمپورت فرصت‌های فروش از فایل Excel/CSV</h2>

    <div class="bg-yellow-50 border border-yellow-300 p-4 rounded mb-6 text-sm text-right leading-relaxed">
        <p class="font-semibold text-yellow-800 mb-2">راهنمای ایمپورت و تنظیم ستون‌ها:</p>
        <ul class="list-disc list-inside text-gray-800 space-y-1">
            <li>«نام فرصت فروش» → الزامی → فیلد <code>name</code></li>
            <li>«نام سازمان» → الزامی → ایجاد/یافتن سازمان و ست‌کردن <code>organization_id</code></li>
            <li>«نام مخاطب» → اختیاری → ایجاد/یافتن مخاطب زیر همان سازمان و ست‌کردن <code>contact_id</code></li>
            <li>«نوع» → <code>type</code></li>
            <li>«منبع/منبع سرنخ» → <code>source</code> (کلید یا برچسب فارسی پذیرفته می‌شود)</li>
            <li>«ارجاع به» → ایمیل یا نام‌کاربری کاربر برای <code>assigned_to</code></li>
            <li>«مرحله فروش» → <code>stage</code> (کلید یا برچسب فارسی)</li>
            <li>«کاربری» → <code>building_usage</code></li>
            <li>«درصد پیشرفت» → ۰ تا ۱۰۰ → <code>success_rate</code></li>
            <li>«مقدار» → عدد → <code>amount</code></li>
            <li>«تاریخ پیگیری بعدی» → تاریخ میلادی یا جلالی → <code>next_follow_up</code></li>
            <li>«زمان ایجاد» → تاریخ/زمان میلادی یا جلالی → <code>created_at</code></li>
            <li>«توضیحات» → <code>description</code></li>
            <li>«استان» و «شهر» → روی «سازمان» ثبت/به‌روزرسانی می‌شود.</li>
        </ul>
        <p class="mt-2 text-xs text-gray-600">گزینه جلوگیری از تکرار (Match By): «همیشه ایجاد رکورد جدید»، یا «بر اساس نام»، یا «نام + سازمان» برای بروزرسانی رکوردهای موجود.</p>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-300 text-red-800 rounded p-3 mb-4 text-sm">
            <ul class="list-disc pr-6">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('sales.opportunities.import.dryrun') }}" enctype="multipart/form-data">
        @csrf
        <label class="block mb-2 font-medium">فایل ورودی (XLSX/CSV):</label>
        <input type="file" name="file" class="border p-2 w-full mb-4" required accept=".xlsx,.csv,.txt">

        <label class="block mb-2 font-medium">سیاست جلوگیری از تکرار (اختیاری):</label>
        <select name="match_by" class="border p-2 w-full mb-4">
            <option value="">همیشه ایجاد رکورد جدید</option>
            <option value="name" {{ $matchBy==='name' ? 'selected' : '' }}>بر اساس نام فرصت</option>
            <option value="name+organization" {{ $matchBy==='name+organization' ? 'selected' : '' }}>نام فرصت + سازمان</option>
        </select>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            پیش‌نمایش و اعتبارسنجی
        </button>
    </form>
</div>

@if ($dry)
    <div class="max-w-7xl mx-auto mt-8 p-6 bg-white shadow rounded">
        <h3 class="text-lg font-semibold mb-3">نتیجه پیش‌نمایش (Dry Run)</h3>
        @if (!empty($dry['missing_required_headers']))
            <div class="bg-yellow-50 border border-yellow-300 p-2 rounded mb-3 text-sm">
                ستون‌های الزامی یافت نشد: {{ implode('، ', $dry['missing_required_headers']) }}
            </div>
        @endif

        <div class="mb-3">
            <div class="font-semibold mb-1">ستون‌ها:</div>
            <div class="text-sm bg-gray-50 p-2 rounded border">{{ implode(' | ', $dry['headers'] ?? []) }}</div>
        </div>

        <div class="overflow-auto">
            <table class="min-w-full text-sm border">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border p-2">اقدام</th>
                        <th class="border p-2">سازمان</th>
                        <th class="border p-2">مخاطب</th>
                        <th class="border p-2">ارجاع به</th>
                        <th class="border p-2">نام فرصت</th>
                        <th class="border p-2">مرحله</th>
                        <th class="border p-2">منبع</th>
                        <th class="border p-2">کاربری</th>
                        <th class="border p-2">درصد</th>
                        <th class="border p-2">مقدار</th>
                        <th class="border p-2">پیگیری بعدی</th>
                        <th class="border p-2">ایجاد</th>
                        <th class="border p-2">خطاها</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dry['sample_rows'] as $r)
                        <tr>
                            <td class="border p-2">{{ $r['action'] ?? '' }}</td>
                            <td class="border p-2">{{ $r['organization']['name'] ?? '' }}</td>
                            <td class="border p-2">{{ $r['contact']['full_name'] ?? '' }}</td>
                            <td class="border p-2">{{ $r['assigned_to']['ref'] ?? '' }}</td>
                            <td class="border p-2">{{ $r['opportunity']['name'] ?? '' }}</td>
                            <td class="border p-2">{{ $r['opportunity']['stage'] ?? '' }}</td>
                            <td class="border p-2">{{ $r['opportunity']['source'] ?? '' }}</td>
                            <td class="border p-2">{{ $r['opportunity']['building_usage'] ?? '' }}</td>
                            <td class="border p-2">{{ $r['opportunity']['success_rate'] ?? '' }}</td>
                            <td class="border p-2">{{ $r['opportunity']['amount'] ?? '' }}</td>
                            <td class="border p-2">{{ $r['opportunity']['next_follow_up'] ?? '' }}</td>
                            <td class="border p-2">{{ $r['opportunity']['created_at'] ?? '' }}</td>
                            <td class="border p-2">
                                @if (!empty($r['errors']))
                                    <pre class="text-xs">{{ json_encode($r['errors'], JSON_UNESCAPED_UNICODE) }}</pre>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="border p-2 text-center" colspan="13">داده‌ای برای نمایش نیست.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (!empty($dry['validation_errors']))
            <details class="mt-3">
                <summary>خطاهای اعتبارسنجی (نمونه)</summary>
                <pre class="text-xs bg-white p-2 rounded border">{{ json_encode(array_slice($dry['validation_errors'], 0, 20), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre>
            </details>
        @endif
    </div>

    @if ($uploaded)
        <div class="max-w-3xl mx-auto mt-6 p-4 bg-white shadow rounded">
            <form action="{{ route('sales.opportunities.import.store') }}" method="post">
                @csrf
                <input type="hidden" name="uploaded_path" value="{{ $uploaded }}" />
                <input type="hidden" name="match_by" value="{{ $matchBy }}" />
                <button class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700" type="submit">تایید و واردسازی نهایی</button>
            </form>
        </div>
    @endif
@endif

@endsection

