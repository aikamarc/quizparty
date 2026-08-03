<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const AVAILABLE_LOCALES = ['en', 'fr'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', config('app.locale'));

        if (! in_array($locale, self::AVAILABLE_LOCALES, true)) {
            $locale = config('app.fallback_locale');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
