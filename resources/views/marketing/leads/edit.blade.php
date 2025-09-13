@extends('layouts.app')

@section('content')
<div class="container py-6" dir="rtl">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-6">ویرایش سرنخ</h2>
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <form action="{{ route('marketing.leads.update', $lead) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1  gap-6">
                        @include('marketing.leads.partials.form-fields', ['lead' => $lead])
                    </div>
                    <div class="flex justify-end space-x-3 space-x-reverse mt-6">
                        <a href="{{ route('marketing.leads.show', $lead) }}" class="btn btn-secondary">بازگشت</a>
                        <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
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

        // اگر مقدار میلادی موجود بود، تبدیل کن به شمسی و مقدار input رو مقداردهی کن
        if (gDateVal && altId && !shamsiInput.val()) {
            try {
                const jalali = new persianDate(new Date(gDateVal)).format('YYYY/MM/DD');
                shamsiInput.val(jalali);
            } catch (e) {
                console.warn('خطا در تبدیل تاریخ میلادی به شمسی:', e);
            }
        }

        // مقداردهی نهایی تقویم شمسی
        shamsiInput.persianDatepicker({
            format: 'YYYY/MM/DD',
            initialValueType: 'persian',
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
