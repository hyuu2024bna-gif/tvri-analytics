<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request and set application locale from session.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', config('app.locale', 'id'));

        if (! in_array($locale, ['id', 'en'], true)) {
            $locale = 'id';
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
