const mix = require('laravel-mix');

mix.postCss('resources/css/app.css', 'public/css', [
    require('tailwindcss'),
]);
mix.js('resources/js/datepickers.js', 'public/js');
