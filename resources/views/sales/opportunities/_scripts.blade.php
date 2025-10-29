<script>
// Modal helpers
function toggleModal(modalId, open = true, focusInputId = null) {
  const el = document.getElementById(modalId);
  if (!el) return;
  if (open) {
    el.classList.remove('hidden');
    el.classList.add('flex');
    el.setAttribute('aria-hidden', 'false');
    if (focusInputId) setTimeout(() => { const t = document.getElementById(focusInputId); if (t) t.focus(); }, 10);
  } else {
    el.classList.add('hidden');
    el.classList.remove('flex');
    el.setAttribute('aria-hidden', 'true');
  }
}

function closeAllModals(){
  ['contactModal','organizationModal','createContactModal','createOrganizationModal']
    .forEach(id => toggleModal(id, false));
}

// Select modals
function openContactModal(){ closeAllModals(); toggleModal('contactModal', true, 'contactSearchInput'); }
function closeContactModal(){ toggleModal('contactModal', false); }

function openOrganizationModal(){ closeAllModals(); toggleModal('organizationModal', true, 'organizationSearchInput'); }
function closeOrganizationModal(){ toggleModal('organizationModal', false); }

// Create modals (via + buttons)
function openCreateContactModal(e){ if (e) { e.stopPropagation?.(); e.preventDefault?.(); } closeAllModals(); toggleModal('createContactModal', true); }
function closeCreateContactModal(){ toggleModal('createContactModal', false); }

function openCreateOrganizationModal(e){ if (e) { e.stopPropagation?.(); e.preventDefault?.(); } closeAllModals(); toggleModal('createOrganizationModal', true); }
function closeCreateOrganizationModal(){ toggleModal('createOrganizationModal', false); }

// Route selection between create forms
let modalRouteTarget = null; // 'contact' or 'organization' or null
function openOrganizationModalFor(target){ modalRouteTarget = target; openOrganizationModal(); }
function openContactModalFor(target){ modalRouteTarget = target; openContactModal(); }

function selectOrganization(id, name){
  if (modalRouteTarget === 'contact'){
    const f1 = document.getElementById('cc_org_id');
    const f2 = document.getElementById('cc_org_name');
    if (f1) f1.value = id ?? '';
    if (f2) f2.value = name ?? '';
    modalRouteTarget = null;
    closeOrganizationModal();
    return;
  }
  const orgId = document.getElementById('organization_id');
  const orgName = document.getElementById('organization_name');
  if (orgId) orgId.value = id ?? '';
  if (orgName) orgName.value = name ?? '';
  modalRouteTarget = null;
  closeOrganizationModal();
}

function selectContact(id, name){
  if (modalRouteTarget === 'organization'){
    const f1 = document.getElementById('co_contact_id');
    const f2 = document.getElementById('co_contact_name');
    if (f1) f1.value = id ?? '';
    if (f2) f2.value = name ?? '';
    modalRouteTarget = null;
    closeContactModal();
    return;
  }
  const cId = document.getElementById('contact_id');
  const cName = document.getElementById('contact_display');
  if (cId) cId.value = id ?? '';
  if (cName) cName.value = name ?? '';
  modalRouteTarget = null;
  closeContactModal();
}

// Close modals by clicking backdrop or pressing Escape
document.addEventListener('click', function(e){
  ['contactModal','organizationModal','createContactModal','createOrganizationModal'].forEach(mid => {
    const m = document.getElementById(mid);
    if (m && !m.classList.contains('hidden') && e.target === m) toggleModal(mid, false);
  });
});
document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeAllModals(); });

// Text/number normalization helpers
function normalizeDigits(str){
  if (!str) return '';
  // Replace Persian (\u06F0-\u06F9) and Arabic-Indic (\u0660-\u0669) digits with ASCII 0-9
  return String(str)
    .replace(/[\u06F0-\u06F9]/g, (d) => String(d.charCodeAt(0) - 0x06F0))
    .replace(/[\u0660-\u0669]/g, (d) => String(d.charCodeAt(0) - 0x0660));
}
function stripSeparators(str){
  return String(str)
    .replace(/[\u200C\u200B\u00A0\s]/g,'')
    .replace(/[\,\u060C]/g,'')
    .replace(/[\.\u066B\u066C]/g,'');
}
function normalizeQuery(raw){
  const lowered = String(raw || '').toLowerCase().trim();
  const digitsFixed = normalizeDigits(lowered);
  return { text: digitsFixed, numeric: stripSeparators(digitsFixed) };
}

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
      const name  = String(tr.getAttribute('data-name')  || '').toLowerCase();
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
  makeLiveFilter({ inputId:'contactSearchInput', tbodyId:'contactTableBody',       noResultId:'contactNoResults' });
  makeLiveFilter({ inputId:'organizationSearchInput', tbodyId:'organizationTableBody', noResultId:'organizationNoResults' });
});

// AJAX create helpers
async function submitAjaxForm(formId, url, onSuccess){
  const form = document.getElementById(formId);
  if (!form) return;
  // Clear previous errors
  form.querySelectorAll('[data-error]').forEach(el => el.textContent = '');
  const fd = new FormData(form);

  try{
    const res  = await fetch(url, { method: 'POST', headers: { 'Accept': 'application/json' }, body: fd });
    const data = await res.json().catch(() => ({}));
    if (!res.ok){
      if (data && data.errors){
        Object.entries(data.errors).forEach(([k, msgs]) => {
          const holder = form.querySelector(`[data-error="${k}"]`);
          if (holder) holder.textContent = Array.isArray(msgs) ? msgs[0] : String(msgs);
        });
      } else {
        alert('Request failed. Please try again.');
      }
      return;
    }
    onSuccess && onSuccess(data);
  } catch(e){
    console.error(e);
    alert('Network error. Please try again.');
  }
}

function submitCreateContact(){
  submitAjaxForm('createContactForm', '{{ route('sales.ajax.contacts.store') }}', function(data){
    if (data && data.id){
      const cId = document.getElementById('contact_id');
      const cName = document.getElementById('contact_display');
      if (cId) cId.value = data.id;
      if (cName) cName.value = data.full_name || ((data.first_name || '') + ' ' + (data.last_name || ''));
      if (data.organization){
        const oId = document.getElementById('organization_id');
        const oName = document.getElementById('organization_name');
        if (oId) oId.value = data.organization.id;
        if (oName) oName.value = data.organization.name;
      }
    }
    closeCreateContactModal();
  });
}

function submitCreateOrganization(){
  submitAjaxForm('createOrganizationForm', '{{ route('sales.ajax.organizations.store') }}', function(data){
    if (data && data.id){
      const oId = document.getElementById('organization_id');
      const oName = document.getElementById('organization_name');
      if (oId) oId.value = data.id;
      if (oName) oName.value = data.name;
    }
    closeCreateOrganizationModal();
  });
}
</script>

<script>
// Disable next_follow_up when stage is won
(function (){
  const stageEl   = document.getElementById('stage');
  const shamsiEl  = document.getElementById('next_follow_up_shamsi');
  const hiddenEl  = document.getElementById('next_follow_up');

  function isWon(val){
    const v = String(val || '').trim().toLowerCase();
    // English 'won' and Persian 'برنده' (unicode-escaped for safety)
    return v === 'won' || v === '\u0628\u0631\u0646\u062F\u0647';
  }

  function disableFollowup(){
    if (shamsiEl) shamsiEl.value = '';
    if (hiddenEl) hiddenEl.value = '';
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
    if (isWon(stageEl.value)) disableFollowup(); else enableFollowup();
  }

  document.addEventListener('DOMContentLoaded', toggleFollowup);
  if (stageEl) stageEl.addEventListener('change', toggleFollowup);

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

