import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import faLocale from '@fullcalendar/core/locales/fa';

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
      n = 0; for (let i = 0; i < s.length; i++) n = (n * 31 + s.charCodeAt(i)) >>> 0;
    }
    return palette[n % palette.length];
  };

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
      end: ''
    },

    events: {
      url: eventsUrl,
      method: 'GET',
      extraParams: () => ({ scope: currentScope })
    },

    eventClick: function(info) {
      if (info.event.url) {
        window.location.href = info.event.url;
        info.jsEvent.preventDefault();
      }
    },

    eventDidMount: function(info) {
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
    }
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
