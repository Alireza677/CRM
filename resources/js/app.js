import './bootstrap';


import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Initialize Alpine.js store
Alpine.store('menu', {
    mainMenuOpen: false,
    subMenuOpen: false,
    activeMenu: null
});

Alpine.start();
