@extends('layouts.app')
@php
    // بردکرامب صفحه ایجاد
    $breadcrumb = [
        ['title' => 'سرنخ‌های فروش', 'url' => route('marketing.leads.index')],
        ['title' => 'ایجاد سرنخ'],
    ];
@endphp
@section('content')
<div class="container py-6" dir="rtl">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-6">ایجاد سرنخ جدید</h2>
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <form action="{{ route('marketing.leads.store') }}" method="POST" class="space-y-6">
                    @csrf
                    @php if (!isset($lead)) $lead = new \App\Models\SalesLead(); @endphp
                    <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
                        @include('marketing.leads.partials.form-fields')
                    </div>
                    <div class="flex justify-end space-x-3 space-x-reverse mt-6">
                        <a href="{{ route('marketing.leads.index') }}" class="btn btn-secondary">انصراف</a>
                        <button type="submit" class="btn btn-primary">ذخیره</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
   $(function () {
    $('.persian-datepicker').each(function () {
        const shamsiInput = $(this);
        const altId = shamsiInput.data('alt-field');
        const gDateVal = $('#' + altId).val();

        // فقط اگر مقدار میلادی وجود داشت و فیلد شمسی خالی بود، مقدارگذاری کن
        if (gDateVal && altId && !shamsiInput.val()) {
            try {
                const jalali = new persianDate(new Date(gDateVal)).format('YYYY/MM/DD');
                shamsiInput.val(jalali);
            } catch (e) {
                console.warn('خطا در تبدیل تاریخ میلادی به شمسی:', e);
            }
        }

        shamsiInput.persianDatepicker({
            format: 'YYYY/MM/DD',
            initialValueType: 'persian', // این خیلی مهمه
            initialValue: true,
            autoClose: true,
            observer: true,
            altField: '#' + altId,
            altFormat: 'YYYY-MM-DD',
            onSelect: function (unix) {
                if (altId) {
                    const gDate = new persianDate(unix).toLocale('en').format('YYYY-MM-DD');
                    $('#' + altId).val(gDate);
                }
            }
        });
    });
});

</script>
@endpush

