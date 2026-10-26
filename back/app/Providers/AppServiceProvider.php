<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // خلف Cloudflare Tunnel يصل الطلب لـ Laravel كـ http، فنجبر الروابط (الصور مثلاً) على https
        // حتى لا يمنعها المتصفح كـ Mixed Content. محلياً APP_URL يبدأ بـ http فلا يتغير شيء.
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
