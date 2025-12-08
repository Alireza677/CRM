@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'فرصت‌های فروش', 'url' => route('sales.opportunities.index')],
        ['title' => 'ویرایش فرصت']
    ];
@endphp

<div class="py-6">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">ویرایش فرصت</h2>

        <form method="POST" action="{{ route('sales.opportunities.update', $opportunity) }}" id="opportunityEditForm">
            @csrf
            @method('PUT')

            @include('sales.opportunities._form')
            <input type="hidden" name="activity_override" id="opportunity_activity_override" value="{{ old('activity_override', 0) }}">
            <input type="hidden" name="quick_note_body" id="opportunity_quick_note_body" value="{{ old('quick_note_body', '') }}">
            <input type="hidden" name="loss_reason_body" id="opportunity_loss_reason_body" value="{{ old('loss_reason_body', '') }}">
            <div id="opportunity_loss_reasons_container">
                @foreach(old('loss_reasons', []) as $reason)
                    <input type="hidden" name="loss_reasons[]" value="{{ $reason }}">
                @endforeach
            </div>

            <div class="mt-6 flex gap-2">
                <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                    بروزرسانی
                </button>
                <a href="{{ route('sales.opportunities.index') }}"
                   class="px-6 py-2 rounded border border-gray-300 hover:bg-gray-50">انصراف</a>
            </div>
        </form>
    </div>
</div>

<div id="opportunityActivityModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50 px-4">
    <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">ثبت فعالیت قبل از تغییر مرحله</h3>
            <button type="button" class="text-2xl text-gray-500 hover:text-gray-700" id="opportunityActivityClose">&times;</button>
        </div>
        <p class="text-sm text-gray-600 mb-3">برای تغییر مرحله این فرصت، یک یادداشت کوتاه ثبت کنید یا مستقیماً پیش‌فاکتور بسازید.</p>
        <textarea id="opportunityActivityNote" rows="4" class="w-full rounded-md border border-gray-300 p-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="یادداشت سریع..."></textarea>
        <p id="opportunityActivityError" class="mt-2 text-sm text-red-600 hidden">لطفاً متن یادداشت را وارد کنید.</p>
        <div class="mt-5 flex flex-wrap justify-end gap-3">
            <button type="button" class="rounded-md bg-gray-200 px-4 py-2 text-sm text-gray-700 hover:bg-gray-300" id="opportunityActivityCancel">انصراف</button>
            <button type="button" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" id="opportunityActivitySubmit">ثبت یادداشت و ادامه</button>
            <button type="button" class="rounded-md bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600" id="opportunityActivityProforma">ایجاد پیش‌فاکتور</button>
        </div>
    </div>
</div>
<div id="opportunityLossReasonModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50 px-4">
    <div class="w-full max-w-xl rounded-lg bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">دلیل از دست رفتن فرصت فروش</h3>
            <button type="button"
                    class="text-2xl text-gray-500 hover:text-gray-700"
                    id="opportunityLossReasonClose">&times;</button>
        </div>

        <p class="text-sm text-gray-700 mb-3">
            لطفاً دلیل از دست رفتن این فرصت فروش را انتخاب کنید و در صورت نیاز توضیح بیشتری بنویسید.
        </p>

        @php
            $lossReasonOptions = ['قیمت', 'تاخیر در تصمیم‌گیری', 'عدم تأمین بودجه', 'رقبا', 'عدم اعتماد'];
            $lossReasonOld = old('loss_reasons', []);
        @endphp

        <div class="mb-4">
            <p class="text-sm font-medium text-gray-700 mb-2">
                دلیل از دست رفتن فرصت فروش
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach($lossReasonOptions as $reason)
                    <label class="flex items-start space-x-2 space-x-reverse text-sm text-gray-700">
                        <input type="checkbox"
                               class="loss-reason-checkbox mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                               name="loss_reasons[]"
                               value="{{ $reason }}"
                               {{ in_array($reason, $lossReasonOld, true) ? 'checked' : '' }}>
                        <span>{{ $reason }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <textarea id="opportunityLossReasonTextarea"
                  name="loss_reason_textarea"
                  rows="4"
                  class="w-full rounded-md border border-gray-300 p-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                  placeholder="توضیحات تکمیلی درباره دلیل از دست رفتن فرصت را اینجا بنویسید...">{{ old('loss_reason_body') }}</textarea>

        <p id="opportunityLossReasonError"
           class="mt-2 text-sm text-red-600 hidden">
            لطفاً حداقل یک دلیل را انتخاب کنید یا توضیحی بنویسید.
        </p>

        <div class="mt-5 flex flex-wrap justify-end gap-3">
            <button type="button"
                    class="rounded-md bg-gray-200 px-4 py-2 text-sm text-gray-700 hover:bg-gray-300"
                    id="opportunityLossReasonCancel">
                انصراف
            </button>
            <button type="button"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                    id="opportunityLossReasonSubmit">
                ثبت دلیل از دست رفتن
            </button>
        </div>
    </div>
</div>


@include('sales.opportunities._modals')
@include('sales.opportunities._scripts')
@endsection


@push('scripts')
<script>
(function () {
  const form = document.getElementById('opportunityEditForm');
  const modal = document.getElementById('opportunityActivityModal');
  const noteInput = document.getElementById('opportunityActivityNote');
  const errorEl = document.getElementById('opportunityActivityError');
  const stageSelect = document.getElementById('stage'); // from _form.blade.php
  const overrideInput = document.getElementById('opportunity_activity_override');
  const quickNoteHidden = document.getElementById('opportunity_quick_note_body');
  const proformaBtn = document.getElementById('opportunityActivityProforma');
  const lossReasonModal = document.getElementById('opportunityLossReasonModal');
  const lossReasonTextarea = document.getElementById('opportunityLossReasonTextarea');
  const lossReasonHidden = document.getElementById('opportunity_loss_reason_body');
  const lossReasonError = document.getElementById('opportunityLossReasonError');
  const lossReasonChecks = document.querySelectorAll('.loss-reason-checkbox');
  const lossReasonHiddenContainer = document.getElementById('opportunity_loss_reasons_container');
  const preselectedLossReasons = @json(old('loss_reasons', []));
  const hasRecentActivity = Boolean(@json($hasRecentActivity ?? false));
  const originalStage = (stageSelect ? (stageSelect.dataset.originalValue || '') : '').toLowerCase();
  const proformaUrl = @json(route('sales.proformas.create', ['opportunity_id' => $opportunity->id, 'return_to' => request()->fullUrl()]));
  const lostStages = ['lost', 'dead'];

  function openModal() {
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    errorEl?.classList.add('hidden');
    setTimeout(() => noteInput?.focus(), 10);
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }

  function shouldRequireNote() {
    if (!form || !stageSelect) return false;
    if (overrideInput && overrideInput.value === '1') return false;
    if (hasRecentActivity) return false;
    const current = (stageSelect.value || '').toLowerCase();
    if (lostStages.includes(current) && current !== originalStage) return false;
    return current !== originalStage;
  }

  function submitWithNote() {
    const note = (noteInput?.value || '').trim();
    if (!note) {
      if (errorEl) {
        errorEl.textContent = 'لطفاً توضیح فعالیت را قبل از ادامه وارد کنید.';
        errorEl.classList.remove('hidden');
      }
      return;
    }
    if (quickNoteHidden) quickNoteHidden.value = note;
    if (overrideInput) overrideInput.value = '1';
    closeModal();
    if (form?.requestSubmit) { form.requestSubmit(); } else { form?.submit(); }
  }

  function extractUserNote(body) {
    if (!body) return '';
    const trimmed = String(body).trim();
    const parts = trimmed.split('\n');
    if (parts.length && parts[0].trim().startsWith('دلایل انتخاب‌شده:')) {
      return parts.slice(1).join('\n').trim();
    }
    return trimmed;
  }

  function getSelectedLossReasons() {
    return Array.from(lossReasonChecks || [])
      .filter(cb => cb.checked)
      .map(cb => cb.value)
      .filter(Boolean);
  }

  function syncLossReasonsHidden() {
    if (!lossReasonHiddenContainer) return;
    lossReasonHiddenContainer.innerHTML = '';
    getSelectedLossReasons().forEach(reason => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'loss_reasons[]';
      input.value = reason;
      lossReasonHiddenContainer.appendChild(input);
    });
  }

  function buildLossReasonNote() {
    const selectedReasons = getSelectedLossReasons();
    const freeText = (lossReasonTextarea?.value || '').trim() || extractUserNote(lossReasonHidden?.value || '');
    const reasonsLine = selectedReasons.length ? `دلایل انتخاب‌شده: ${selectedReasons.join('، ')}` : '';
    if (reasonsLine && freeText) {
      return `${reasonsLine}\n${freeText}`;
    }
    return (reasonsLine || freeText).trim();
  }

  function openLossModal() {
    if (!lossReasonModal) return;
    lossReasonModal.classList.remove('hidden');
    lossReasonModal.classList.add('flex');
    lossReasonError?.classList.add('hidden');
    if (lossReasonTextarea) {
      const baseText = extractUserNote(lossReasonHidden?.value || lossReasonTextarea.value || '');
      lossReasonTextarea.value = baseText;
    }
    setTimeout(() => lossReasonTextarea?.focus(), 10);
  }

  function closeLossModal() {
    if (!lossReasonModal) return;
    lossReasonModal.classList.add('hidden');
    lossReasonModal.classList.remove('flex');
  }

  function needsLossReason() {
    if (!stageSelect) return false;
    const current = (stageSelect.value || '').toLowerCase();
    return lostStages.includes(current) && current !== originalStage;
  }

  function submitLossReason() {
    const combined = buildLossReasonNote();
    if (!combined) {
      lossReasonError?.classList.remove('hidden');
      lossReasonTextarea?.focus();
      return;
    }
    if (lossReasonHidden) lossReasonHidden.value = combined;
    syncLossReasonsHidden();
    closeLossModal();
    if (form?.requestSubmit) { form.requestSubmit(); } else { form?.submit(); }
  }

  if (preselectedLossReasons?.length) {
    lossReasonChecks.forEach(cb => { cb.checked = preselectedLossReasons.includes(cb.value); });
  }
  syncLossReasonsHidden();

  if (form) {
    form.addEventListener('submit', function (e) {
      if (needsLossReason()) {
        const combined = buildLossReasonNote();
        if (!combined) {
          e.preventDefault();
          openLossModal();
          return;
        }
        if (lossReasonHidden) lossReasonHidden.value = combined;
        syncLossReasonsHidden();
      }
      if (!shouldRequireNote()) return;
      e.preventDefault();
      openModal();
    });
  }

  document.getElementById('opportunityActivitySubmit')?.addEventListener('click', submitWithNote);
  document.getElementById('opportunityActivityCancel')?.addEventListener('click', closeModal);
  document.getElementById('opportunityActivityClose')?.addEventListener('click', closeModal);
  document.getElementById('opportunityLossReasonSubmit')?.addEventListener('click', submitLossReason);
  document.getElementById('opportunityLossReasonCancel')?.addEventListener('click', closeLossModal);
  document.getElementById('opportunityLossReasonClose')?.addEventListener('click', closeLossModal);
  lossReasonTextarea?.addEventListener('input', () => lossReasonError?.classList.add('hidden'));
  lossReasonChecks.forEach(cb => cb.addEventListener('change', () => {
    lossReasonError?.classList.add('hidden');
    syncLossReasonsHidden();
  }));
  stageSelect?.addEventListener('change', function () {
    if (needsLossReason()) {
      if (lossReasonHidden) lossReasonHidden.value = '';
      openLossModal();
    }
  });
  proformaBtn?.addEventListener('click', function(){ if (proformaUrl) { window.location.href = proformaUrl; } });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeModal();
      closeLossModal();
    }
  });
  modal?.addEventListener('click', function (e) {
    if (e.target === modal) closeModal();
  });
  lossReasonModal?.addEventListener('click', function (e) {
    if (e.target === lossReasonModal) closeLossModal();
  });
})();
</script>
@endpush


