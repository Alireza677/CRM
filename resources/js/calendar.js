import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import faLocale from '@fullcalendar/core/locales/fa';
import { TableDateProfileGenerator } from '@fullcalendar/daygrid/internal';
import { createDuration } from '@fullcalendar/core/internal';
import jalaali from 'jalaali-js';

document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('calendar');
  if (!el) return;

  const eventsUrl = el.dataset.eventsUrl;
  let currentScope = el.dataset.defaultScope || 'personal';

  // Toggle buttons (shared/personal)
  const filterWrap = document.getElementById('calendar-filter');
  const scopeButtons = filterWrap ? filterWrap.querySelectorAll('[data-scope]') : [];

  const setActiveScopeButton = (scope) => {
    if (!scopeButtons) return;
    scopeButtons.forEach(btn => {
      const isActive = btn.getAttribute('data-scope') === scope;
      btn.classList.toggle('bg-blue-600', isActive);
      btn.classList.toggle('text-white', isActive);
      btn.classList.toggle('hover:bg-blue-700', isActive);
      btn.classList.toggle('border', !isActive);
      btn.classList.toggle('border-gray-300', !isActive);
      btn.classList.toggle('text-gray-700', !isActive);
      btn.classList.toggle('hover:bg-gray-100', !isActive);
    });
  };

  const palette = [
    '#3b82f6', // blue-500
    '#10b981', // emerald-500
    '#f59e0b', // amber-500
    '#ef4444', // red-500
    '#8b5cf6', // violet-500
    '#06b6d4', // cyan-500
    '#f97316', // orange-500
    '#22c55e', // green-500
    '#e11d48', // rose-600
    '#0ea5e9', // sky-500
  ];

  const colorForUser = (userId) => {
    if (userId == null) return '#3b82f6';
    let n = parseInt(userId, 10);
    if (Number.isNaN(n)) {
      const s = String(userId);
      n = 0;
      for (let i = 0; i < s.length; i++) n = (n * 31 + s.charCodeAt(i)) >>> 0;
    }
    return palette[n % palette.length];
  };

  const jalaliMonthNames = [
    'فروردین',
    'اردیبهشت',
    'خرداد',
    'تیر',
    'مرداد',
    'شهریور',
    'مهر',
    'آبان',
    'آذر',
    'دی',
    'بهمن',
    'اسفند',
  ];

  const toJalali = (dateObj) =>
    jalaali.toJalaali(
      dateObj.getUTCFullYear(),
      dateObj.getUTCMonth() + 1,
      dateObj.getUTCDate(),
    );

  const jalaliToUtcDate = (jy, jm, jd) => {
    const g = jalaali.toGregorian(jy, jm, jd);
    return new Date(Date.UTC(g.gy, g.gm - 1, g.gd));
  };

  const addJalaliMonths = (jy, jm, delta) => {
    const total = (jy * 12) + (jm - 1) + delta;
    const newJy = Math.floor(total / 12);
    let newJm = total % 12;
    if (newJm < 0) newJm += 12;
    return { jy: newJy, jm: newJm + 1 };
  };

  const formatJalaliMonthYear = (dateObj) => {
    try {
      return new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
        year: 'numeric',
        month: 'long',
      }).format(dateObj);
    } catch (e) {
      const j = toJalali(dateObj);
      return `${j.jy} ${jalaliMonthNames[j.jm - 1]}`;
    }
  };

  const formatJalaliDayNumber = (dateObj) => {
    try {
      return new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
        day: 'numeric',
      }).format(dateObj);
    } catch (e) {
      return String(toJalali(dateObj).jd);
    }
  };

  class JalaliMonthDateProfileGenerator extends TableDateProfileGenerator {
    buildCurrentRangeInfo(date, direction) {
      const { props } = this;
      if (props.durationUnit === 'month') {
        const j = toJalali(date);
        const start = jalaliToUtcDate(j.jy, j.jm, 1);
        const next = addJalaliMonths(j.jy, j.jm, 1);
        const end = jalaliToUtcDate(next.jy, next.jm, 1);
        return {
          duration: createDuration({ months: 1 }),
          unit: 'month',
          range: { start, end },
        };
      }
      return super.buildCurrentRangeInfo(date, direction);
    }

    buildPrev(currentDateProfile, currentDate, forceToValid) {
      if (currentDateProfile.currentRangeUnit === 'month') {
        const currentStart = currentDateProfile.currentRange.start;
        const j = toJalali(currentStart);
        const prev = addJalaliMonths(j.jy, j.jm, -1);
        const prevStart = jalaliToUtcDate(prev.jy, prev.jm, 1);
        return this.build(prevStart, -1, forceToValid);
      }
      return super.buildPrev(currentDateProfile, currentDate, forceToValid);
    }

    buildNext(currentDateProfile, currentDate, forceToValid) {
      if (currentDateProfile.currentRangeUnit === 'month') {
        const currentStart = currentDateProfile.currentRange.start;
        const j = toJalali(currentStart);
        const next = addJalaliMonths(j.jy, j.jm, 1);
        const nextStart = jalaliToUtcDate(next.jy, next.jm, 1);
        return this.build(nextStart, 1, forceToValid);
      }
      return super.buildNext(currentDateProfile, currentDate, forceToValid);
    }
  }

  const calendar = new Calendar(el, {
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',

    locale: faLocale,
    direction: 'rtl',
    firstDay: 6,
    height: 'auto',

    headerToolbar: {
      start: 'prev,next today',
      center: 'title',
      end: '',
    },

    views: {
      dayGridMonth: {
        dateProfileGeneratorClass: JalaliMonthDateProfileGenerator,
        titleFormat: (arg) => {
          const marker = arg.start?.marker || arg.date?.marker;
          return marker ? formatJalaliMonthYear(marker) : '';
        },
        dayCellContent: (arg) => ({
          html: formatJalaliDayNumber(arg.date),
        }),
      },
    },

    events: {
      url: eventsUrl,
      method: 'GET',
      extraParams: () => ({ scope: currentScope }),
    },

    eventClick: function (info) {
      if (info.event.url) {
        window.location.href = info.event.url;
        info.jsEvent.preventDefault();
      }
    },

    eventDidMount: function (info) {
      // Keep holidays red regardless of scope coloring
      if (info.event.extendedProps && info.event.extendedProps.kind === 'holiday') {
        const red = '#ef4444';
        info.el.style.backgroundColor = red;
        info.el.style.borderColor = red;
        info.el.style.color = '#fff';
        return;
      }

      // رنگ‌بندی برای حالت مشترک
      if (currentScope === 'shared') {
        const uid = info.event.extendedProps?.assigned_to;
        const c = colorForUser(uid);
        info.el.style.backgroundColor = c;
        info.el.style.borderColor = c;
        info.el.style.color = '#fff';
      }
    },
  });

  calendar.render();

  if (scopeButtons && scopeButtons.length) {
    setActiveScopeButton(currentScope);

    scopeButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const next = btn.getAttribute('data-scope');
        if (!next || next === currentScope) return;

        currentScope = next;
        setActiveScopeButton(currentScope);
        calendar.refetchEvents();
      });
    });
  }
});
