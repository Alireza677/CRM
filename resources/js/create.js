/* global $, persianDate */

(function () {
    // --- Helpers ---
    const pad = n => ('0' + n).slice(-2);
  
    function toggleModal(id, open = true, focusId = null) {
      const el = document.getElementById(id);
      if (!el) return;
      if (open) {
        el.classList.remove('hidden');
        el.classList.add('flex');
        if (focusId) setTimeout(() => document.getElementById(focusId)?.focus(), 10);
      } else {
        el.classList.add('hidden');
        el.classList.remove('flex');
      }
    }
    window.openContactModal = () => toggleModal('contactModal', true, 'contactSearchInput');
    window.closeContactModal = () => toggleModal('contactModal', false);
    window.openOrganizationModal = () => toggleModal('organizationModal', true, 'organizationSearchInput');
    window.closeOrganizationModal = () => toggleModal('organizationModal', false);
  
    window.pickContact = (id, name) => {
      document.getElementById('related_type').value = 'contact';
      document.getElementById('related_id').value = id;
      document.getElementById('related_display').value = name;
      closeContactModal();
    };
    window.pickOrganization = (id, name) => {
      document.getElementById('related_type').value = 'organization';
      document.getElementById('related_id').value = id;
      document.getElementById('related_display').value = name;
      closeOrganizationModal();
    };
  
    // کلیک بیرون + Esc
    document.addEventListener('click', e => {
      ['contactModal', 'organizationModal'].forEach(mid => {
        const m = document.getElementById(mid);
        if (m && !m.classList.contains('hidden') && e.target === m) toggleModal(mid, false);
      });
    });
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') {
        toggleModal('contactModal', false);
        toggleModal('organizationModal', false);
      }
    });
  
    // --- Live filter (فارسی/انگلیسی/ارقام) ---
    function normalizeDigits(str) {
      if (!str) return '';
      const fa = '۰۱۲۳۴۵۶۷۸۹', ar = '٠١٢٣٤٥٦٧٨٩';
      return String(str).split('').map(ch => {
        const iFa = fa.indexOf(ch); if (iFa > -1) return String(iFa);
        const iAr = ar.indexOf(ch); if (iAr > -1) return String(iAr);
        return ch;
      }).join('');
    }
    function stripSep(str) {
      return String(str)
        .replace(/[\u200C\u200B\u00A0\s]/g, '')
        .replace(/[,\u060C.\u066B\u066C]/g, '');
    }
    function makeLiveFilter({ inputId, tbodyId, noResId }) {
      const $in = document.getElementById(inputId),
            $tb = document.getElementById(tbodyId),
            $no = document.getElementById(noResId);
      if (!$in || !$tb) return;
      let t = null;
      $in.addEventListener('input', () => { clearTimeout(t); t = setTimeout(apply, 150); });
      function apply() {
        const raw = (normalizeDigits($in.value || '')).toLowerCase();
        const num = stripSep(raw);
        const rows = [...$tb.querySelectorAll('tr')];
        if (!raw) { rows.forEach(tr => tr.classList.remove('hidden')); $no?.classList.add('hidden'); return; }
        const isNum = /^[0-9]+$/.test(num);
        let vis = 0;
        rows.forEach(tr => {
          const name = (tr.getAttribute('data-name') || '').toLowerCase();
          const phone = (tr.getAttribute('data-phone') || '');
          const ok = name.includes(raw) || (isNum ? phone.includes(num) : false);
          tr.classList.toggle('hidden', !ok);
          if (ok) vis++;
        });
        if ($no) $no.classList.toggle('hidden', vis !== 0);
      }
    }
  
    function initDateTimePicker(selector) {
        const $ui = $(selector);
        if (!$ui.length) return;
    
        const altId = $ui.data('alt-field');
        const $alt = altId ? $('#' + altId) : null;
        const prefill = String($ui.data('prefill') ?? '').trim();
        const allowInitial = prefill === '1' || ($alt && ($alt.val() || '').trim());
        let userSelected = false;
    
        try { $ui.persianDatepicker('destroy'); } catch (e) {}
    
        $ui.persianDatepicker({
          format: 'YYYY/MM/DD HH:mm',
          initialValue: false,
          autoClose: true,
          observer: false,
          calendar: {
            persian:   { locale: 'fa', leapYearMode: 'astronomical' },
            gregorian: { locale: 'en' }
          },
          timePicker: { enabled: true, step: 1, meridiem: { enabled: false } },
          formatter: function (unix) {
            if (!allowInitial && !userSelected) return '';
            return new persianDate(unix).format('YYYY/MM/DD HH:mm');
          },
          onSelect: function (unix) {
            userSelected = true;
            // onSelect happens before formatter gets a chance to show the value (first pick),
            // so we set the visible input here to avoid the "second pick" issue.
            try {
              const p = new persianDate(unix);
              const formatted = p.format('YYYY/MM/DD HH:mm');
              $ui.val(formatted);
              $ui.attr('value', formatted);
            } catch (e) {}
            if (!$alt) return;
            try {
              const g = new persianDate(unix).toCalendar('gregorian');
              const y = g.year(), m = pad(g.month()), d = pad(g.date());
              const hh = pad(g.hour()), mm = pad(g.minute());
              $alt.val(`${y}-${m}-${d} ${hh}:${mm}:00`);
            } catch (e) {
              const dt = new Date(unix);
              $alt.val(
                dt.getFullYear() + '-' + pad(dt.getMonth()+1) + '-' + pad(dt.getDate()) + ' ' +
                pad(dt.getHours()) + ':' + pad(dt.getMinutes()) + ':' + pad(dt.getSeconds())
              );
            }
          },
          onShow: function () {
            if (!allowInitial && !userSelected) {
              $ui.val('');
              $ui.attr('value', '');
            }
          }
        });

        // در حالت ایجاد، اگر مقدار اولیه نداریم، فیلد را خالی نگه دار (جلوگیری از نمایش تاریخ امروز)
        if (!allowInitial) {
          $ui.val('');
          $ui.attr('value', '');
          setTimeout(() => {
            if (!allowInitial && !userSelected) {
              $ui.val('');
              $ui.attr('value', '');
            }
          }, 0);
        }
    
        // اگر hidden مقدار گرگوریان دارد، UI را شمسی/ساعت‌دار پر کن (برای ویرایش یا old())
        if ($alt && ($alt.val() || '').trim()) {
            const g = ($alt.val() || '').trim(); // 2025-09-22 14:35:00 یا 2025-09-22T14:35:00
            const m = g.match(/^(\d{4})-(\d{2})-(\d{2})[T\s](\d{2}):(\d{2})(?::(\d{2}))?$/);
            if (m) {
              const y  = parseInt(m[1], 10);
              const mo = parseInt(m[2], 10);
              const d  = parseInt(m[3], 10);
              const hh = parseInt(m[4], 10);
              const mm = parseInt(m[5], 10);
              const ss = parseInt(m[6] || '0', 10);
          
              // تاریخ محلی گرگوریان → epoch ms
              const unixMs = new Date(y, mo - 1, d, hh, mm, ss).getTime();
          
              // به‌جای set کردن .val()، حالت خود تاریخ‌پیکر را ست کن
              try {
                $ui.persianDatepicker('setDate', unixMs);
              } catch (e) {
                // fallback خیلی بعیده لازم بشه:
                const p = new persianDate(unixMs).toCalendar('persian');
                $ui.val(p.format('YYYY/MM/DD HH:mm'));
              }
            }
          }
        }
    // --- Ready ---
  $(function () {
      makeLiveFilter({ inputId: 'contactSearchInput', tbodyId: 'contactTableBody', noResId: 'contactNoResults' });
      makeLiveFilter({ inputId: 'organizationSearchInput', tbodyId: 'organizationTableBody', noResId: 'organizationNoResults' });
  
      initDateTimePicker('#start_at_display');
      initDateTimePicker('#due_at_display');

      const progressRange = document.getElementById('progress_range');
      const progressInput = document.getElementById('progress_input');
      if (progressRange && progressInput) {
        const sync = (value) => {
          const n = Math.max(0, Math.min(100, parseInt(value || 0, 10) || 0));
          progressRange.value = n;
          progressInput.value = n;
        };
        progressRange.addEventListener('input', (e) => sync(e.target.value));
        progressInput.addEventListener('input', (e) => sync(e.target.value));
        sync(progressRange.value);
      }
  
    // نمایش انتخاب قبلی مربوط‌به
    const rt = document.getElementById('related_type')?.value;
    const rid = document.getElementById('related_id')?.value;
    const relatedDisplay = document.getElementById('related_display');
    const presetLabel = relatedDisplay?.value || '';
    if (rt && rid && relatedDisplay) {
      const prefixes = {
        contact: '(مخاطب) #',
        organization: '(سازمان) #',
        sales_lead: '(سرنخ) #',
        opportunity: '(فرصت) #',
      };
      if (!presetLabel) {
        relatedDisplay.value = (prefixes[rt] || '#') + rid;
      }
    }
 
     // موعد بعد از شروع
     $('#start_at_display').on('change', function () {
       try {
         const s = $('#start_at_display').persianDatepicker('getState').selected?.unixDate;
         if (s) $('#due_at_display').persianDatepicker('setMinDate', s);
       } catch (e) {}
     });

      // ------- Reminders (dynamic) -------
      (function initReminders() {
        const container = document.getElementById('remindersContainer');
        const btnAdd = document.getElementById('btnAddReminder');
        if (!container || !btnAdd) return;

        let idx = 0;

        function makeRow(i, preset) {
          const row = document.createElement('div');
          row.className = 'flex flex-col md:flex-row gap-2 items-start md:items-center';

          const typeSel = document.createElement('select');
          typeSel.name = `reminders[${i}][type]`;
          typeSel.className = 'rounded-md border p-2 text-sm';
          typeSel.innerHTML = `
            <option value="30m_before">نیم ساعت قبل</option>
            <option value="1h_before">یک ساعت قبل</option>
            <option value="1d_before">یک روز قبل</option>
            <option value="same_day">همان روز، ساعت مشخص</option>
            <option value="absolute">زمان مشخص</option>
          `;

          const timeWrap = document.createElement('div');
          timeWrap.className = 'flex items-center gap-2';
          const timeLbl = document.createElement('span');
          timeLbl.className = 'text-sm text-gray-600';
          timeLbl.textContent = 'ساعت:';
          const timeInp = document.createElement('input');
          timeInp.type = 'time';
          timeInp.className = 'rounded-md border p-2 text-sm';
          timeInp.name = `reminders[${i}][time]`;
          timeInp.placeholder = '08:00';
          timeWrap.appendChild(timeLbl);
          timeWrap.appendChild(timeInp);

          const dateWrap = document.createElement('div');
          dateWrap.className = 'flex items-center gap-2';
          const dateLbl = document.createElement('span');
          dateLbl.className = 'text-sm text-gray-600';
          dateLbl.textContent = 'تاریخ/ساعت:';
          const dateInp = document.createElement('input');
          dateInp.type = 'datetime-local';
          dateInp.className = 'rounded-md border p-2 text-sm';
          dateInp.name = `reminders[${i}][datetime]`;
          dateWrap.appendChild(dateLbl);
          dateWrap.appendChild(dateInp);

          const removeBtn = document.createElement('button');
          removeBtn.type = 'button';
          removeBtn.className = 'px-2 py-1 text-sm rounded-md bg-red-50 text-red-700 hover:bg-red-100';
          removeBtn.textContent = 'حذف';
          removeBtn.addEventListener('click', () => row.remove());

          function updateVisibility() {
            const v = typeSel.value;
            const showTime = (v === 'same_day');
            const showDate = (v === 'absolute');
            timeWrap.style.display = showTime ? 'flex' : 'none';
            dateWrap.style.display = showDate ? 'flex' : 'none';
          }
          typeSel.addEventListener('change', updateVisibility);

          // Apply preset (if any)
          if (preset?.type) typeSel.value = preset.type;
          if (preset?.time) timeInp.value = preset.time;
          if (preset?.datetime) {
            const dt = String(preset.datetime).trim().replace(' ', 'T');
            dateInp.value = dt;
          }
          updateVisibility();

          row.appendChild(typeSel);
          row.appendChild(timeWrap);
          row.appendChild(dateWrap);
          row.appendChild(removeBtn);
          return row;
        }

        btnAdd.addEventListener('click', () => {
          container.appendChild(makeRow(idx++));
        });

        const presets = Array.isArray(window.__activityReminders) ? window.__activityReminders : [];
        if (presets.length) {
          presets.forEach((preset) => container.appendChild(makeRow(idx++, preset)));
        }
      })();
   });
 })();
  
