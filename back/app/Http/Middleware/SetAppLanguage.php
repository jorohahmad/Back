<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetAppLanguage
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->header('Accept-Language', 'en');

        if (in_array($locale, ['en', 'ar'])) {
            App::setLocale($locale);
        } else {
            App::setLocale('en'); 
        }

        return $next($request);
    }
}