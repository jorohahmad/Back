<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Schedule::command('rentals:check-expired')->daily();
Schedule::command('carts:clear-abandoned')->hourly();
// النسخة التجريبية: إعادة توليد البيانات الوهمية يومياً حتى تبقى إحصائيات "هذا الأسبوع" ممتلئة
Schedule::command('db:seed', ['--class' => 'DemoSeeder', '--force' => true])
    ->dailyAt('00:05')
    ->when(fn() => config('demo.enabled'));