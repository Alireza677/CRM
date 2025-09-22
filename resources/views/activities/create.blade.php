@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-4" dir="rtl">
  <h1 class="text-xl font-semibold mb-4">ایجاد وظیفه</h1>
  <form method="POST" action="{{ route('activities.store') }}" class="space-y-4">
    @csrf

    <div>
      <label class="block text-sm mb-1">موضوع</label>
      <input name="subject" class="w-full rounded-md border p-2" required>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div>
    <label class="block text-sm font-medium mb-1">تاریخ شروع</label>
    <input type="hidden" id="start_at" name="start_at" value="{{ old('start_at') }}">
    <input
      type="text"
      id="start_at_display"
      class="persian-datepicker w-full rounded-md border p-2"
      data-alt-field="start_at"
      data-prefill="{{ old('start_at') ? '1' : '0' }}"
      autocomplete="off"
      value="">
    @error('start_at')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
  </div>

  <div>
    <label class="block text-sm font-medium mb-1">موعد/پایان</label>
    <input type="hidden" id="due_at" name="due_at" value="{{ old('due_at') }}">
    <input
      type="text"
      id="due_at_display"
      class="persian-datepicker w-full rounded-md border p-2"
      data-alt-field="due_at"
      data-prefill="{{ old('due_at') ? '1' : '0' }}"
      autocomplete="off"
      value="">
    @error('due_at')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
  </div>
</div>



    <div>
      <label class="block text-sm mb-1">ارجاع به </label>
      <select name="assigned_to_id" class="w-full rounded-md border p-2" required>
        @foreach($users as $u)
          <option value="{{ $u->id }}">{{ $u->name }}</option>
        @endforeach
      </select>
    </div>

    {{-- نمایش انتخاب‌شده + دکمه‌های انتخاب --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm mb-1">مربوط به</label>
        <div class="flex gap-2">
          <button type="button" onclick="openContactModal()" class="px-3 py-2 rounded-md bg-slate-100 hover:bg-slate-200"> مخاطب +</button>
          <button type="button" onclick="openOrganizationModal()" class="px-3 py-2 rounded-md bg-slate-100 hover:bg-slate-200"> سازمان +</button>
        </div>
      </div>

      <div>
        <label class="block text-sm mb-1">آیتم انتخاب‌شده</label>
        <input id="related_display" type="text" class="w-full rounded-md border p-2 bg-gray-50" placeholder="— انتخاب نشده —" readonly>
      </div>
    </div>

    {{-- فیلدهای واقعی فرم --}}
    <input type="hidden" name="related_type" id="related_type" value="{{ old('related_type') }}">
    <input type="hidden" name="related_id"   id="related_id"   value="{{ old('related_id') }}">
    @error('related_type')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    @error('related_id')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror


    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm mb-1">وضعیت</label>
        <select name="status" class="w-full rounded-md border p-2" required>
          <option value="not_started">شروع نشده</option>
          <option value="in_progress">در حال انجام</option>
          <option value="completed">تکمیل شده</option>
          <option value="scheduled">برنامه‌ریزی شده</option>
        </select>
      </div>
      <div>
        <label class="block text-sm mb-1">اولویت</label>
        <select name="priority" class="w-full rounded-md border p-2" required>
          <option value="normal">معمولی</option>
          <option value="medium">متوسط</option>
          <option value="high">زیاد</option>
        </select>
      </div>
    </div>

    <div>
      <label class="block text-sm mb-1">توضیحات</label>
      <textarea name="description" rows="4" class="w-full rounded-md border p-2"></textarea>
    </div>

    <label class="inline-flex items-center gap-2">
      <input type="checkbox" name="is_private" value="1">
      <span>خصوصی (عدم نمایش برای سایر کاربران)</span>
    </label>

    <div class="pt-2 flex gap-2">
  <button type="submit"
          class="bg-blue-600 hover:bg-blue-700 text-white rounded-md px-4 py-2">
    ثبت
  </button>

  <button type="button"
          onclick="window.history.back()"
          class="bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-md px-4 py-2">
    انصراف
  </button>
</div>

  </form>
</div>



{{-- مودال انتخاب مخاطب --}}
<div id="contactModal" class="fixed inset-0 bg-black/50 z-[60] hidden items-center justify-center">
  <div class="bg-white w-11/12 md:w-3/4 max-h-[80vh] overflow-y-auto p-4 rounded shadow">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">انتخاب مخاطب</h3>
      <button type="button" onclick="closeContactModal()" class="text-gray-500 hover:text-red-500 text-lg">&times;</button>
    </div>

    <div class="mb-3">
      <input id="contactSearchInput" type="text" placeholder="جستجوی نام یا موبایل…"
             class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" autocomplete="off">
      <div class="mt-1 text-xs text-gray-500">با تایپ، فهرست فیلتر می‌شود.</div>
    </div>

    <div class="border border-gray-200 rounded overflow-hidden">
      <table class="w-full text-sm text-right">
        <thead class="bg-gray-100 text-gray-700 sticky top-0">
          <tr>
            <th class="px-4 py-2 border-b">نام مخاطب</th>
            <th class="px-4 py-2 border-b">شماره موبایل</th>
          </tr>
        </thead>
        <tbody id="contactTableBody">
          @foreach($contacts as $c)
            <tr class="cursor-pointer hover:bg-gray-50"
                data-name="{{ $c->full_name }}"
                data-phone="{{ preg_replace('/\D+/', '', (string)($c->mobile ?? '')) }}"
                onclick="pickContact({{ $c->id }}, @js($c->full_name))">
              <td class="px-4 py-2 border-b">{{ $c->full_name }}</td>
              <td class="px-4 py-2 border-b text-gray-500">{{ $c->mobile ?? '—' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
      <div id="contactNoResults" class="hidden p-4 text-center text-sm text-gray-500">موردی یافت نشد.</div>
    </div>
  </div>
</div>

{{-- مودال انتخاب سازمان --}}
<div id="organizationModal" class="fixed inset-0 bg-black/50 z-[60] hidden items-center justify-center">
  <div class="bg-white w-11/12 md:w-3/4 max-h-[80vh] overflow-y-auto p-4 rounded shadow">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">انتخاب سازمان</h3>
      <button type="button" onclick="closeOrganizationModal()" class="text-gray-500 hover:text-red-500 text-lg">&times;</button>
    </div>

    <div class="mb-3">
      <input id="organizationSearchInput" type="text" placeholder="جستجوی نام سازمان یا شماره تماس…"
             class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" autocomplete="off">
      <div class="mt-1 text-xs text-gray-500">با تایپ، فهرست فیلتر می‌شود.</div>
    </div>

    <div class="border border-gray-200 rounded overflow-hidden">
      <table class="w-full text-sm text-right">
        <thead class="bg-gray-100 text-gray-700 sticky top-0">
          <tr>
            <th class="px-4 py-2 border-b">نام سازمان</th>
            <th class="px-4 py-2 border-b">شماره تماس</th>
          </tr>
        </thead>
        <tbody id="organizationTableBody">
          @foreach($organizations as $org)
            <tr class="cursor-pointer hover:bg-gray-50"
                data-name="{{ $org->name }}"
                data-phone="{{ preg_replace('/\D+/', '', (string)($org->phone ?? '')) }}"
                onclick="pickOrganization({{ $org->id }}, @js($org->name))">
              <td class="px-4 py-2 border-b">{{ $org->name }}</td>
              <td class="px-4 py-2 border-b text-gray-500">{{ $org->phone ?? '—' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
      <div id="organizationNoResults" class="hidden p-4 text-center text-sm text-gray-500">موردی یافت نشد.</div>
    </div>
  </div>
</div>



<script>
function toggleModal(id, open=true, focusId=null){
  const el = document.getElementById(id);
  if(!el) return;
  if(open){ el.classList.remove('hidden'); el.classList.add('flex'); if(focusId) setTimeout(()=>document.getElementById(focusId)?.focus(),10); }
  else{ el.classList.add('hidden'); el.classList.remove('flex'); }
}
function openContactModal(){ toggleModal('contactModal', true, 'contactSearchInput'); }
function closeContactModal(){ toggleModal('contactModal', false); }
function openOrganizationModal(){ toggleModal('organizationModal', true, 'organizationSearchInput'); }
function closeOrganizationModal(){ toggleModal('organizationModal', false); }

function pickContact(id, name){
  document.getElementById('related_type').value = 'contact';
  document.getElementById('related_id').value   = id;
  document.getElementById('related_display').value = name;
  closeContactModal();
}
function pickOrganization(id, name){
  document.getElementById('related_type').value = 'organization';
  document.getElementById('related_id').value   = id;
  document.getElementById('related_display').value = name;
  closeOrganizationModal();
}

// کلیک بیرون + Esc
document.addEventListener('click', e=>{
  ['contactModal','organizationModal'].forEach(mid=>{
    const m = document.getElementById(mid);
    if(m && !m.classList.contains('hidden') && e.target === m) toggleModal(mid,false);
  });
});
document.addEventListener('keydown', e=>{
  if(e.key==='Escape'){ toggleModal('contactModal',false); toggleModal('organizationModal',false); }
});

// --- Live filter (فارسی/انگلیسی/ارقام) ---
function normalizeDigits(str){ if(!str) return ''; const fa='۰۱۲۳۴۵۶۷۸۹', ar='٠١٢٣٤٥٦٧٨٩';
  return String(str).split('').map(ch=>{const iFa=fa.indexOf(ch); if(iFa>-1) return String(iFa); const iAr=ar.indexOf(ch); if(iAr>-1) return String(iAr); return ch;}).join('');
}
function stripSep(str){ return String(str).replace(/[\u200C\u200B\u00A0\s]/g,'').replace(/[,\u060C.\u066B\u066C]/g,''); }
function makeLiveFilter({inputId, tbodyId, noResId}){
  const $in=document.getElementById(inputId), $tb=document.getElementById(tbodyId), $no=document.getElementById(noResId);
  if(!$in||!$tb) return;
  let t=null;
  $in.addEventListener('input', ()=>{ clearTimeout(t); t=setTimeout(apply,150); });
  function apply(){
    const raw=(normalizeDigits($in.value||'')).toLowerCase();
    const num=stripSep(raw);
    const rows=[...$tb.querySelectorAll('tr')];
    if(!raw){ rows.forEach(tr=>tr.classList.remove('hidden')); $no?.classList.add('hidden'); return; }
    const isNum=/^[0-9]+$/.test(num);
    let vis=0;
    rows.forEach(tr=>{
      const name=(tr.getAttribute('data-name')||'').toLowerCase();
      const phone=(tr.getAttribute('data-phone')||'');
      const ok = name.includes(raw) || (isNum ? phone.includes(num) : false);
      tr.classList.toggle('hidden', !ok); if(ok) vis++;
    });
    if($no) $no.classList.toggle('hidden', vis!==0);
  }
}
document.addEventListener('DOMContentLoaded', ()=>{
  makeLiveFilter({inputId:'contactSearchInput', tbodyId:'contactTableBody', noResId:'contactNoResults'});
  makeLiveFilter({inputId:'organizationSearchInput', tbodyId:'organizationTableBody', noResId:'organizationNoResults'});

  // اگر old() داشتیم، نمایش را هم پر کن
  const rt=document.getElementById('related_type').value;
  const rid=document.getElementById('related_id').value;
  if(rt && rid){
    // یک نمایش ساده (بدون کوئری اضافه): فقط نوع را نشان بده
    document.getElementById('related_display').value = (rt==='contact'?'(مخاطب) ':'(سازمان) ') + rid;
    // اگر نیاز داری نام دقیق را هم پر کنی، می‌توانیم با یک endpoint کوچک ajax مقدار دقیق را بگیریم.
  }
});
</script>
@push('scripts')
<script>
(function(){
  // کمکی: اگر خواستی صفر اضافه کنی
  const pad = n => ('0'+n).slice(-2);

  function initDateTimePicker(sel){
    const $ui   = $(sel);
    if (!$ui.length) return;

    const altId  = $ui.data('alt-field');
    const $alt   = altId ? $('#'+altId) : null;

    try { $ui.persianDatepicker('destroy'); } catch(e){}

    $ui.persianDatepicker({
      format: 'YYYY/MM/DD HH:mm',
      initialValue: false,          // ورودی‌ها موقع لود خالی
      autoClose: true,
      observer: true,
      calendar: {
        persian:   { locale: 'fa', leapYearMode: 'astronomical' },
        gregorian: { locale: 'en' }
      },
      timePicker: {
        enabled: true,
        step: 1,                    // دقیقه‌ها 1 واحدی (هر زمان دلخواه)
        meridiem: { enabled: false } // 24 ساعته
      },

      // ✅ مقدار hidden را بی‌واسطه با persianDate → gregorian پر می‌کنیم
      onSelect: function(unix){
        if (!$alt) return;
        try {
          const g = new persianDate(unix)
                      .toCalendar('gregorian');  // تبدیل مستقیم، بدون Date و TZ
          const y = g.year();
          const m = pad(g.month());
          const d = pad(g.date());
          const hh = pad(g.hour());
          const mm = pad(g.minute());
          const ss = '00';
          $alt.val(`${y}-${m}-${d} ${hh}:${mm}:${ss}`);
        } catch(e){
          // فالس‌بک (نادر)
          const d = new Date(unix);
          $alt.val(
            d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate())+' '+
            pad(d.getHours())+':'+pad(d.getMinutes())+':'+pad(d.getSeconds())
          );
        }
      }
    });

    // پاک‌کردن دستی ورودی → hidden هم خالی شود
    if ($alt) {
      $ui.on('input blur', function(){
        if (!($ui.val()||'').trim()) $alt.val('');
      });
    }
  }

  $(function(){
    initDateTimePicker('#start_at_display');
    initDateTimePicker('#due_at_display');

    // (اختیاری) موعد باید بعد از شروع باشد
    $('#start_at_display').on('change', function(){
      try {
        const s = $('#start_at_display').persianDatepicker('getState').selected?.unixDate;
        if (s) $('#due_at_display').persianDatepicker('setMinDate', s);
      } catch(e){}
    });
  });
})();
</script>
@endpush


@endsection
