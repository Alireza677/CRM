@extends('layouts.app')

@section('content')
<div class="p-6 space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">ایمپورت تأمین‌کنندگان</h1>

        @if (Route::has('inventory.suppliers.index'))
            <a href="{{ route('inventory.suppliers.index') }}"
               class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                بازگشت به لیست تأمین‌کنندگان
            </a>
        @endif
    </div>

    <form action="{{ route('inventory.suppliers.import.dryrun') }}" method="POST" enctype="multipart/form-data"
          class="space-y-4 bg-white shadow-md rounded-2xl p-6">
        @csrf
        <div>
            <label class="block mb-2 font-semibold">فایل Excel/CSV تأمین‌کنندگان</label>
            <input type="file" name="file" required class="block w-full rounded-lg border border-gray-300 p-2" />
            <p class="text-xs text-gray-500 mt-1">پسوندهای مجاز: xlsx, csv, txt</p>
            @error('file')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            پیش‌نمایش (Dry-Run)
        </button>
    </form>

    @if(session('dry_run'))
        @php($r = session('dry_run'))
        <div class="bg-gray-50 p-4 rounded-2xl shadow">
            <h2 class="text-xl font-semibold mb-4">نتیجه پیش‌نمایش</h2>

            <div class="mb-4">
                <h3 class="font-bold">هدرهای فایل:</h3>
                <pre class="bg-white p-2 rounded overflow-auto text-sm">{{ implode(' | ', $r['headers'] ?? []) }}</pre>
            </div>

            @if(!empty($r['missing_required_headers']))
                <div class="mb-4 text-red-600">
                    <h3 class="font-bold">هدرهای اجباریِ مفقود:</h3>
                    <ul class="list-disc pr-5">
                        @foreach($r['missing_required_headers'] as $header)
                            <li>{{ $header }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(!empty($r['extra_headers']))
                <div class="mb-4 text-yellow-600">
                    <h3 class="font-bold">هدرهای اضافه (نادیده گرفته می‌شود):</h3>
                    <ul class="list-disc pr-5">
                        @foreach($r['extra_headers'] as $header)
                            <li>{{ $header }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(!empty($r['sample_rows']))
                <div class="mb-4">
                    <h3 class="font-bold">نمونه ردیف‌ها (تا ۱۰ ردیف):</h3>
                    <pre class="bg-white p-2 rounded text-sm overflow-auto">{{ json_encode($r['sample_rows'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @endif

            @if(!empty($r['validation_errors']))
                <div class="mb-4 text-red-700">
                    <h3 class="font-bold">خطاهای اعتبارسنجی:</h3>
                    @foreach($r['validation_errors'] as $row => $errs)
                        <p class="font-semibold">ردیف {{ $row }}:</p>
                        <ul class="list-disc pr-5 mb-2">
                            @foreach($errs as $field => $messages)
                                <li>{{ $field }}: {{ implode(' , ', $messages) }}</li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            @else
                <div class="mb-4 text-green-700">
                    عالی! ساختار فایل درست است و می‌توانید ایمپورت را انجام دهید.
                </div>
            @endif

            @if(empty($r['missing_required_headers']) && empty($r['validation_errors']))
                <form action="{{ route('inventory.suppliers.import.store') }}" method="POST" class="mt-4">
                    @csrf
                    <input type="hidden" name="uploaded_path" value="{{ session('uploaded_path') }}">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        تایید و آغاز ایمپورت
                    </button>
                </form>
            @endif
        </div>
    @endif

    @if(session('import_result'))
        @php($res = session('import_result'))
        <div class="bg-green-100 p-4 rounded-2xl">
            <h3 class="font-bold">نتیجه ایمپورت:</h3>
            <p>تعداد ردیف‌های ثبت‌شده: {{ $res['inserted'] ?? 0 }}</p>

            @if(!empty($res['failed_rows']))
                <div class="mt-4 text-red-700">
                    <h4 class="font-semibold">ردیف‌های ناموفق:</h4>
                    <pre class="bg-white p-2 rounded text-sm overflow-auto">{{ json_encode($res['failed_rows'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @endif
        </div>
    @endif

    <div class="text-sm text-gray-600">
        <p class="font-semibold mb-2">هدرهای پیشنهادی (ستون‌ها):</p>
        <p>کد پستی | آدرس | استان | شهر | ارجاع به | منبع | دسته‌بندی | زمان ویرایش | زمان ایجاد | تلفن | نام تأمین‌کننده</p>
        <p class="mt-2">ستون‌های «استان، شهر، کد پستی» نیز اکنون پشتیبانی می‌شوند و در پروفایل تأمین‌کننده ذخیره می‌گردند.</p>
        <p>برای «ارجاع به»، می‌توانید نام یا ایمیل کاربر را وارد کنید تا به شناسه کاربر تبدیل شود.</p>
        <p>برای «دسته‌بندی»، می‌توانید نام دسته را وارد کنید تا به شناسه دسته تبدیل شود.</p>
        <p>«منبع» به‌صورت پیش‌فرض در توضیحات ذخیره می‌شود.</p>
    </div>

</div>
@endsection
