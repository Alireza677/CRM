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
                        <button type="button" id="save-btn" class="btn btn-primary">ذخیره تغییرات</button>
                    </div>
                </form>

                @include('sales.proformas.partials.submit-mode-modal')
                <div class="modal fade" id="editReasonModal" tabindex="-1" aria-labelledby="editReasonModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editReasonModalLabel">دلیل ویرایش پیش‌فاکتور</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <label for="edit-reason-text" class="form-label">لطفاً دلیل ویرایش پیش‌فاکتور را وارد کنید</label>
                                <textarea class="form-control" id="edit-reason-text" rows="4" required></textarea>
                                <div class="invalid-feedback">وارد کردن دلیل ویرایش الزامی است.</div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
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
        const modalEl = document.getElementById("submitModeModal");
        const submitModeModal = modalEl ? new bootstrap.Modal(modalEl) : null;
        const editReasonModalEl = document.getElementById("editReasonModal");
        const editReasonModal = editReasonModalEl ? new bootstrap.Modal(editReasonModalEl) : null;
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
            submitModeModal?.hide();
            editReasonModal?.show();
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
            if (submitModeModal) {
                submitModeModal.show();
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

            editReasonModal?.hide();
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
