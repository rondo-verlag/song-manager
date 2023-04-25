const mix = require('laravel-mix');

mix.js('src/frontend/assets/js/app.js', 'src/frontend/js/lib')
    .sass('src/frontend/assets/scss/app.scss', 'src/frontend/css/lib');
