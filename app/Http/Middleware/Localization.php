<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class Localization
{
    public function handle($request, Closure $next)
    {
        // Read session value, or fallback to default locale
        $locale = Session::get('lang', config('app.locale'));

        // Apply locale globally
        App::setLocale($locale);

        return $next($request);
    }
}
