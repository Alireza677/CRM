@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'فرصت‌های فروش', 'url' => route('sales.opportunities.index')],
        ['title' => 'ایجاد فرصت جدید']
    ];
@endphp

<div class="py-6">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">فرصت جدید</h2>

        <form method="POST" action="{{ route('sales.opportunities.store') }}">
            @csrf

            @include('sales.opportunities._form')

            <div class="mt-6 flex items-center space-x-4 space-x-reverse">
                <button type="submit" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
                    ذخیره
                </button>

                <a href="{{ route('sales.opportunities.index') }}" 
                class="bg-gray-300 text-gray-800 px-6 py-2 rounded hover:bg-gray-400">
                    انصراف
                </a>
            </div>

        </form>
    </div>
</div>

@include('sales.opportunities._modals')
@include('sales.opportunities._scripts')
@endsection

@push('scripts')
<script>
(function () {
    const sourceSelect = document.getElementById('source');
    const acquirerSelect = document.getElementById('acquirer_user_id');
    const acquirerLocked = document.getElementById('acquirer_user_id_locked');
    const acquirerNote = document.getElementById('acquirer_locked_note');

    const companyAcquirerId = @json($companyAcquirerUserId ?? null);
    const companyAcquirerName = @json($companyAcquirerUserName ?? null);
    const companyOwnedSources = @json($companyOwnedSources ?? []);
    const settingsUrl = @json(route('settings.sales.leads.edit'));

    function setNote(message, isHtml = false) {
        if (!acquirerNote) return;
        if (isHtml) {
            acquirerNote.innerHTML = message;
        } else {
            acquirerNote.textContent = message;
        }
        acquirerNote.classList.remove('hidden');
    }

    function clearNote() {
        if (!acquirerNote) return;
        acquirerNote.textContent = '';
        acquirerNote.classList.add('hidden');
    }

    function lockAcquirer(value) {
        if (acquirerSelect) {
            acquirerSelect.value = value || '';
            acquirerSelect.disabled = true;
        }
        if (acquirerLocked) {
            acquirerLocked.value = value || '';
            acquirerLocked.disabled = false;
        }
    }

    function unlockAcquirer() {
        if (acquirerSelect) {
            acquirerSelect.disabled = false;
        }
        if (acquirerLocked) {
            acquirerLocked.value = '';
            acquirerLocked.disabled = true;
        }
        clearNote();
    }

    function handleSourceChange() {
        const sourceValue = (sourceSelect?.value || '').trim();
        if (!sourceValue) {
            unlockAcquirer();
            return;
        }

        if (companyOwnedSources.includes(sourceValue)) {
            if (!companyAcquirerId) {
                lockAcquirer('');
                setNote(`کاربر جذب‌کننده شرکتی تنظیم نشده است. <a class="underline" href="${settingsUrl}">تنظیمات</a>`, true);
                return;
            }

            lockAcquirer(String(companyAcquirerId));
            const label = companyAcquirerName || 'شرکت';
            setNote(`منبع سازمانی است؛ جذب‌کننده به ${label} اختصاص داده می‌شود.`);
            return;
        }

        unlockAcquirer();
    }

    if (sourceSelect) {
        sourceSelect.addEventListener('change', handleSourceChange);
        handleSourceChange();
    }
})();
</script>
@endpush
