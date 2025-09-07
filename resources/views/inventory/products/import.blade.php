{{-- resources/views/products/import.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="p-6 space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">ایمپورت محصولات</h1>

        {{-- بازگشت به لیست محصولات --}}
        @if (Route::has('inventory.products.index'))
            <a href="{{ route('inventory.products.index') }}"
               class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 19l-7-7 7-7" />
                </svg>
                بازگشت به محصولات
            </a>
        @endif
    </div>

    {{-- فرم آپلود فایل برای Dry Run --}}
    <form action="{{ route('inventory.products.import.dryrun') }}" method="POST" enctype="multipart/form-data"
          class="space-y-4 bg-white shadow-md rounded-2xl p-6">
        @csrf
        <div>
            <label class="block mb-2 font-semibold">فایل اکسل/CSV محصولات</label>
            <input type="file" name="file" required
                   class="block w-full rounded-lg border border-gray-300 p-2" />
            <p class="text-xs text-gray-500 mt-1">فرمت‌های مجاز: xlsx, csv, txt</p>
            @error('file')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            پیش‌نمایش اطلاعات (Dry-Run)
        </button>
    </form>

    {{-- نمایش گزارش Dry-Run اگر وجود داشت --}}
    @if(session('dry_run'))
        @php($r = session('dry_run'))
        <div class="bg-gray-50 p-4 rounded-2xl shadow">
            <h2 class="text-xl font-semibold mb-4">گزارش پیش‌نمایش</h2>

            {{-- هدرهای خوانده‌شده --}}
            <div class="mb-4">
                <h3 class="font-bold">هدرهای فایل:</h3>
                <pre class="bg-white p-2 rounded overflow-auto text-sm">{{ implode(' | ', $r['headers'] ?? []) }}</pre>
            </div>

            {{-- هدرهای اجباری که وجود ندارند --}}
            @if(!empty($r['missing_required_headers']))
                <div class="mb-4 text-red-600">
                    <h3 class="font-bold">❌ هدرهای ضروری که یافت نشدند:</h3>
                    <ul class="list-disc pr-5">
                        @foreach($r['missing_required_headers'] as $header)
                            <li>{{ $header }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- هدرهای اضافه --}}
            @if(!empty($r['extra_headers']))
                <div class="mb-4 text-yellow-600">
                    <h3 class="font-bold">⚠️ هدرهای اضافی:</h3>
                    <ul class="list-disc pr-5">
                        @foreach($r['extra_headers'] as $header)
                            <li>{{ $header }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ردیف‌های نمونه --}}
            @if(!empty($r['sample_rows']))
                <div class="mb-4">
                    <h3 class="font-bold">نمونه رکوردها (۱۰ ردیف اول):</h3>
                    <pre class="bg-white p-2 rounded text-sm overflow-auto">
{{ json_encode($r['sample_rows'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}
                    </pre>
                </div>
            @endif

            {{-- خطاهای اعتبارسنجی --}}
            @if(!empty($r['validation_errors']))
                <div class="mb-4 text-red-700">
                    <h3 class="font-bold">خطاهای یافت‌شده:</h3>
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
                    ✅ تمام ردیف‌های بررسی‌شده معتبر هستند.
                </div>
            @endif

            {{-- اگر خطا نداشت، دکمه تایید ایمپورت --}}
            @if(empty($r['missing_required_headers']) && empty($r['validation_errors']))
                <form action="{{ route('inventory.products.import.store') }}" method="POST" class="mt-4">
                    @csrf
                    <input type="hidden" name="uploaded_path" value="{{ session('uploaded_path') }}">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        تایید و ایمپورت نهایی
                    </button>
                </form>
            @endif
        </div>
    @endif

    {{-- نمایش نتیجه ایمپورت --}}
    @if(session('import_result'))
        @php($res = session('import_result'))
        <div class="bg-green-100 p-4 rounded-2xl">
            <h3 class="font-bold">نتیجه‌ی ایمپورت:</h3>
            <p>✅ {{ $res['inserted'] ?? 0 }} ردیف با موفقیت ایمپورت شد.</p>

            @if(!empty($res['failed_rows']))
                <div class="mt-4 text-red-700">
                    <h4 class="font-semibold">❌ ردیف‌های نامعتبر:</h4>
                    <pre class="bg-white p-2 rounded text-sm overflow-auto">
{{ json_encode($res['failed_rows'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}
                    </pre>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
