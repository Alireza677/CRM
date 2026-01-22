@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'پیش‌فاکتورها', 'url' => route('sales.proformas.index')],
        ['title' => 'ویرایش پیش‌فاکتور']
    ];
@endphp

<link rel="stylesheet" href="{{ asset('css/proforma-style.css') }}">

<div class="container py-6 proforma-card" dir="rtl">
    <div class="w-full px-4">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-6">
                    ویرایش پیش‌فاکتور
                </h2>

                <form action="{{ route('sales.proformas.update', $proforma->id) }}" method="POST" class="space-y-6" id="proforma-form">
                    @csrf
                    @method('PUT')

                    @include('sales.proformas.partials.form', ['proforma' => $proforma])

                    <div class="flex items-center justify-end mt-6">
                        <input type="hidden" name="submit_mode" id="submit_mode" value="draft">
                        <input type="hidden" name="edit_reason" id="edit_reason_input" value="">
                        <input type="hidden" name="total_amount" id="total_amount_input" value="{{ $proforma->total_amount ?? 0 }}">
                        <a href="{{ route('sales.proformas.index') }}" class="btn btn-secondary ml-4">انصراف</a>
                        <button type="button" id="save-btn" class="btn btn-primary" data-modal-open="submitModeModal">ذخیره تغییرات</button>
                    </div>
                </form>

                @include('sales.proformas.partials.submit-mode-modal')
                <div id="editReasonModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50" data-modal-root aria-labelledby="editReasonModalLabel" aria-hidden="true" aria-modal="true" role="dialog" hidden>
                    <div class="w-full max-w-xl mx-4">
                        <div class="bg-white rounded-lg shadow-lg">
                            <div class="flex items-center justify-between px-4 py-3 border-b">
                                <h5 class="modal-title" id="editReasonModalLabel">دلیل ویرایش پیش‌فاکتور</h5>
                                <button type="button" class="btn-close text-gray-500 hover:text-gray-700 text-xl leading-none" data-modal-close aria-label="Close">&times;</button>
                            </div>
                            <div class="modal-body px-4 py-3">
                                <label for="edit-reason-text" class="form-label">لطفاً دلیل ویرایش پیش‌فاکتور را وارد کنید</label>
                                <textarea class="form-control" id="edit-reason-text" rows="4" required></textarea>
                                <div class="invalid-feedback">وارد کردن دلیل ویرایش الزامی است.</div>
                            </div>
                            <div class="modal-footer px-4 py-3 border-t">
                                <button type="button" class="btn btn-secondary" data-modal-close>انصراف</button>
                                <button type="button" class="btn btn-primary" id="confirm-edit-reason">تایید و ادامه</button>
                            </div>
                        </div>
                    </div>
                </div>

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
        const submitModeInput = document.getElementById("submit_mode");
        const editReasonInput = document.getElementById("edit_reason_input");
        const submitModeModalId = "submitModeModal";
        const editReasonModalId = "editReasonModal";
        const editReasonTextarea = document.getElementById("edit-reason-text");
        const confirmEditReasonBtn = document.getElementById("confirm-edit-reason");
        let pendingSubmitMode = null;

        function submitWithMode(mode) {
            if (!form || !submitModeInput) return;
            submitModeInput.value = mode;
            calculateInvoiceTotal();
            form.submit();
        }

        function openEditReasonModal(mode) {
            pendingSubmitMode = mode;
            if (editReasonTextarea) {
                editReasonTextarea.value = '';
                editReasonTextarea.classList.remove('is-invalid');
            }
            if (typeof window.closeModal === 'function') {
                window.closeModal(submitModeModalId);
            }
            if (typeof window.openModal === 'function') {
                window.openModal(editReasonModalId);
            }
        }

        form?.addEventListener('submit', function (event) {
            if (submitModeInput && !submitModeInput.value) {
                submitModeInput.value = 'draft';
            }
            if (!editReasonInput?.value.trim()) {
                event.preventDefault();
                openEditReasonModal(submitModeInput?.value || 'draft');
            }
        });

        saveBtn?.addEventListener("click", function () {
            if (typeof window.openModal === 'function') {
                window.openModal(submitModeModalId);
            } else {
                openEditReasonModal('draft');
            }
        });

        document.getElementById('submit-as-draft')?.addEventListener('click', function () {
            openEditReasonModal('draft');
        });

        document.getElementById('submit-send-for-approval')?.addEventListener('click', function () {
            openEditReasonModal('send_for_approval');
        });

        confirmEditReasonBtn?.addEventListener('click', function () {
            const reason = editReasonTextarea?.value.trim() ?? '';
            if (!reason) {
                editReasonTextarea?.classList.add('is-invalid');
                editReasonTextarea?.focus();
                return;
            }

            if (editReasonInput) {
                editReasonInput.value = reason;
            }

            if (typeof window.closeModal === 'function') {
                window.closeModal(editReasonModalId);
            }
            submitWithMode(pendingSubmitMode || 'draft');
        });

        editReasonTextarea?.addEventListener('input', function () {
            if (editReasonTextarea.value.trim()) {
                editReasonTextarea.classList.remove('is-invalid');
            }
        });
    });
</script>
@endpush
