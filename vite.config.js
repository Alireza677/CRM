import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/css/calendar.css',
      ],
      refresh: true,
    }),
  ],
  optimizeDeps: {
    // فقط چیزهایی که واقعاً لازم است را pre-bundle کن
    include: [
      'jquery',
      '@fullcalendar/core',
      '@fullcalendar/daygrid',
      '@fullcalendar/interaction',
    ],
    // مشکل‌سازها را صراحتاً exclude کن
    exclude: [
      'persian-datepicker',
      'persian-date',
    ],
  },
});
