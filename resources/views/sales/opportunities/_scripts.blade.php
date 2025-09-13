<style>
    label.required::after { content: ' *'; color: red; }
    .form-field { @apply mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-400; }
</style>

<script>
function toggleModal(modalId, open = true, focusInputId = null) {
    const el = document.getElementById(modalId);
    if (!el) return;
    if (open) {
        el.classList.remove('hidden'); el.classList.add('flex'); el.setAttribute('aria-hidden','false');
        if (focusInputId) setTimeout(() => document.getElementById(focusInputId)?.focus(), 10);
    } else {
        el.classList.add('hidden'); el.classList.remove('flex'); el.setAttribute('aria-hidden','true');
    }
}
function openContactModal(){ toggleModal('contactModal', true, 'contactSearchInput'); }
function closeContactModal(){ toggleModal('contactModal', false); }
function openOrganizationModal(){ toggleModal('organizationModal', true, 'organizationSearchInput'); }
function closeOrganizationModal(){ toggleModal('organizationModal', false); }

function selectContact(id, name){
    document.getElementById('contact_id').value = id ?? '';
    document.getElementById('contact_display').value = name ?? '';
    closeContactModal();
}
function selectOrganization(id, name){
    document.getElementById('organization_id').value = id ?? '';
    document.getElementById('organization_name').value = name ?? '';
    closeOrganizationModal();
}

document.addEventListener('click', function(e){
    ['contactModal','organizationModal'].forEach(mid => {
        const m = document.getElementById(mid);
        if (m && !m.classList.contains('hidden') && e.target === m) toggleModal(mid, false);
    });
});
document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') { toggleModal('contactModal', false); toggleModal('organizationModal', false); }
});

// --- Live search helpers ---
function normalizeDigits(str){ if(!str) return ''; const fa='۰۱۲۳۴۵۶۷۸۹', ar='٠١٢٣٤٥٦٧٨٩';
  return String(str).split('').map(ch=>{const iFa=fa.indexOf(ch); if(iFa>-1) return String(iFa); const iAr=ar.indexOf(ch); if(iAr>-1) return String(iAr); return ch;}).join('');
}
function stripSeparators(str){ return String(str).replace(/[\u200C\u200B\u00A0\s]/g,'').replace(/[,\u060C]/g,'').replace(/[.\u066B\u066C]/g,''); }
function normalizeQuery(raw){ const lowered=String(raw||'').toLowerCase().trim(); const digitsFixed=normalizeDigits(lowered);
  return { text: digitsFixed, numeric: stripSeparators(digitsFixed) }; }

function makeLiveFilter({inputId, tbodyId, noResultId}) {
    const $input = document.getElementById(inputId);
    const $tbody = document.getElementById(tbodyId);
    const $noRes = document.getElementById(noResultId);
    if (!$input || !$tbody) return;

    let t = null;
    $input.addEventListener('input', () => { clearTimeout(t); t = setTimeout(applyFilter, 150); });

    function applyFilter() {
        const { text, numeric } = normalizeQuery($input.value);
        const rows = Array.from($tbody.querySelectorAll('tr'));

        if (!text) {
            rows.forEach(tr => tr.classList.remove('hidden'));
            if ($noRes) $noRes.classList.add('hidden');
            return;
        }

        let visible = 0;
        const isPureNumber = /^[0-9]+$/.test(numeric);
        rows.forEach(tr => {
            const name = String(tr.getAttribute('data-name') || '').toLowerCase();
            const phone = String(tr.getAttribute('data-phone') || '');
            const byName  = name.includes(text);
            const byPhone = isPureNumber ? phone.includes(numeric) : (numeric ? phone.includes(numeric) : false);
            const match = byName || byPhone;
            if (match) { tr.classList.remove('hidden'); visible++; } else { tr.classList.add('hidden'); }
        });

        if ($noRes) (visible === 0) ? $noRes.classList.remove('hidden') : $noRes.classList.add('hidden');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    makeLiveFilter({ inputId:'contactSearchInput', tbodyId:'contactTableBody', noResultId:'contactNoResults' });
    makeLiveFilter({ inputId:'organizationSearchInput', tbodyId:'organizationTableBody', noResultId:'organizationNoResults' });
});
</script>


<!-- کنترل غیرفعال/پاک کردن تاریخ پیگیری وقتی مرحله = برنده -->
<style>
  /* استایل ملایم برای وقتی غیرفعال می‌کنیم */
  .is-disabled {
    background-color: #f3f4f6; /* gray-100 */
    color: #9ca3af;           /* gray-400 */
    cursor: not-allowed;
    pointer-events: none;
  }
</style>

<script>
(function (){
  const stageEl   = document.getElementById('stage');
  const shamsiEl  = document.getElementById('next_follow_up_shamsi');
  const hiddenEl  = document.getElementById('next_follow_up');

  function isWon(val){
    const v = (val || '').trim();
    return v === 'برنده' || v.toLowerCase() === 'won';
  }

  function disableFollowup(){
    // پاک کردن هر دو ورودی
    if (shamsiEl) shamsiEl.value = '';
    if (hiddenEl) hiddenEl.value = '';
    // غیرفعال کردن ورودی نمایشی (hidden را فعال می‌گذاریم تا مقدار خالی ارسال شود)
    if (shamsiEl){
      shamsiEl.setAttribute('readonly','readonly');
      shamsiEl.classList.add('is-disabled');
      shamsiEl.setAttribute('aria-disabled','true');
    }
  }

  function enableFollowup(){
    if (shamsiEl){
      shamsiEl.removeAttribute('readonly');
      shamsiEl.classList.remove('is-disabled');
      shamsiEl.removeAttribute('aria-disabled');
    }
  }

  function toggleFollowup(){
    if (!stageEl) return;
    if (isWon(stageEl.value)){
      disableFollowup();
    } else {
      enableFollowup();
    }
  }

  // اجرا در لود اولیه و روی تغییر مرحله
  document.addEventListener('DOMContentLoaded', toggleFollowup);
  if (stageEl) stageEl.addEventListener('change', toggleFollowup);

  // امنیت بیشتر: هنگام سابمیت هم اگر برنده بود، مقدار را پاک کنیم
  const formEl = stageEl ? stageEl.form : null;
  if (formEl){
    formEl.addEventListener('submit', function(){
      if (isWon(stageEl.value)){
        if (shamsiEl) shamsiEl.value = '';
        if (hiddenEl) hiddenEl.value = '';
      }
    });
  }
})();
</script>
