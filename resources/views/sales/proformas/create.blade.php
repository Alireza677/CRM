@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'پیش‌فاکتورها', 'url' => route('sales.proformas.index')],
        ['title' => 'ایجاد پیش‌فاکتور']
    ];
@endphp

<link rel="stylesheet" href="{{ asset('css/proforma-style.css') }}">

<div class="container py-6 proforma-card" dir="rtl">
    <div class="w-full px-4">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-6">
                    ایجاد پیش‌فاکتور جدید
                </h2>

                <form action="{{ route('sales.proformas.store') }}" method="POST" class="space-y-6" id="proforma-form">
                    @csrf

                    @include('sales.proformas.partials.form', ['proforma' => null])

                    <div class="flex items-center justify-end mt-6">
                        <input type="hidden" name="total_amount" id="total_amount_input" value="0">
                        <a href="{{ route('sales.proformas.index') }}" class="btn btn-secondary ml-4">انصراف</a>
                        <button type="button" id="save-btn" class="btn btn-primary">ذخیره پیش‌فاکتور</button>
                    </div>

                    @if (request('opportunity_id'))
                        <input type="hidden" name="opportunity_id" value="{{ request('opportunity_id') }}">
                    @endif
                </form>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const saveBtn = document.getElementById("save-btn");
        const form = document.getElementById("proforma-form");
        const stageField = document.getElementById("proforma_stage");

        saveBtn.addEventListener("click", function () {
            if (stageField.value === 'send_for_approval') {
                const modal = new bootstrap.Modal(document.getElementById('automationConfirmModal'));
                modal.show();
            } else {
                calculateInvoiceTotal();
                form.submit();
            }
        });

        document.getElementById("confirm-save")?.addEventListener("click", function () {
            calculateInvoiceTotal();
            form.submit();
        });
    });
</script>
@endpush
