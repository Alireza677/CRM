import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import faLocale from '@fullcalendar/core/locales/fa';

document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('calendar');
  if (!el) return;

  const eventsUrl = el.dataset.eventsUrl; // ← آدرس فید از Blade

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

    // فید رویدادها
    events: {
      url: eventsUrl,
      method: 'GET'
      // می‌تونی اینجا extraParams هم بدی اگر لازم شد
      // extraParams: { foo: 'bar' }
    },

    // اختیاری: کلیک روی ایونت → رفتن به صفحه جزئیات
    eventClick: function(info) {
      if (info.event.url) {
        window.location.href = info.event.url;
        info.jsEvent.preventDefault();
      }
    }
  });

  calendar.render();
});
