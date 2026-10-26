const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Đồ án dùng CSS/JS tĩnh trong public/assets nên KHÔNG bắt buộc chạy npm.
 | File này giữ theo chuẩn Laravel 8 để có thể build thêm nếu cần.
 |
 */

mix.js('resources/js/app.js', 'public/js')
    .postCss('resources/css/app.css', 'public/css', [
        //
    ]);
