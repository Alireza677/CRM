import './bootstrap';

import './plugins/jdp-global.js';

import Alpine from 'alpinejs';
import geoPicker from './components/geo-picker';
window.geoPicker = geoPicker;
window.Alpine = Alpine;

// Initialize Alpine.js store
Alpine.store('menu', {
    mainMenuOpen: false,
    subMenuOpen: false,
    activeMenu: null
});

Alpine.start();

if (document.getElementById('calendar')) {
    import('./calendar.js');
  }