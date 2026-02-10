@extends('layouts.app')

@section('content')
    <div class="max-w-2xl mx-auto p-6 bg-white rounded-xl shadow border border-gray-200">
        <h1 class="text-lg font-semibold text-gray-900">تست Push Notification</h1>
        <p class="text-sm text-gray-600 mt-2">
            این صفحه فقط برای محیط توسعه است. ابتدا مجوز اعلان‌ها را فعال کنید و سپس دکمه ارسال را بزنید.
        </p>

        <div class="mt-4 flex flex-wrap gap-2">
            <button id="send-webpush-test"
                    class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-700">
                ارسال اعلان تست
            </button>
            <span id="send-webpush-test-status" class="text-sm text-gray-600"></span>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('send-webpush-test');
        const status = document.getElementById('send-webpush-test-status');
        if (!btn) return;

        btn.addEventListener('click', function () {
            status.textContent = 'در حال ارسال...';
            fetch(@json(route('dev.webpush.send')), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
                .then(resp => resp.ok ? resp.json() : Promise.reject(resp))
                .then(() => {
                    status.textContent = 'ارسال شد. اگر مجوز فعال است باید اعلان را ببینید.';
                })
                .catch(() => {
                    status.textContent = 'ارسال ناموفق بود.';
                });
        });
    });
</script>
@endpush
