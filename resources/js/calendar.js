

import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import faLocale from '@fullcalendar/core/locales/fa';



document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('calendar');
  if (!el) return;

  const calendar = new Calendar(el, {
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    locale: faLocale,
    direction: 'rtl',
    firstDay: 6, // شنبه
    height: 'auto',
    headerToolbar: {
      start: 'prev,next today',
      center: 'title',
      end: ''
    },
    events: [], // فعلاً خالی
  });

  calendar.render();
});
