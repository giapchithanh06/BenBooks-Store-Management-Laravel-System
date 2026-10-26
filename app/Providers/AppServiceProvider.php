<?php

namespace App\Providers;

use App\Models\Category;
use App\Services\CartService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

require_once __DIR__ . '/../helpers.php';

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        ini_set('pcre.jit', '0');
        $this->app->singleton(CartService::class, function () {
            return new CartService();
        });
    }

    public function boot()
    {
        // MySQL/MariaDB cũ của XAMPP: tránh lỗi "Specified key was too long"
        Schema::defaultStringLength(191);

        // Ngày giờ tiếng Việt ("3 phút trước")
        \Carbon\Carbon::setLocale(config('app.locale'));

        // Dữ liệu dùng chung cho layout khách hàng
        View::composer('layouts.shop', function ($view) {
            static $menu = null;
            if ($menu === null) {
                try {
                    $menu = Category::menuTree();
                } catch (\Throwable $e) {
                    $menu = collect();
                }
            }
            $view->with('menuCategories', $menu)
                 ->with('cartCount', app(CartService::class)->count());
        });
    }
}
